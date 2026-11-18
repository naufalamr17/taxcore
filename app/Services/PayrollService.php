<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(private AccessService $access, private TaxService $tax) {}

    /** @param array<string, mixed> $data */
    public function saveEmployee(User $user, Company $company, array $data, ?Employee $employee = null): Employee
    {
        Gate::forUser($user)->authorize('payroll.edit');
        $this->access->company($user, $company->id);

        return DB::transaction(function () use ($user, $company, $data, $employee): Employee {
            if ($employee) {
                abort_unless($employee->company_id === $company->id, 404);
            }
            $employee ??= new Employee(['company_id' => $company->id]);
            $employee->fill(collect($data)->only(['name', 'nik', 'position', 'ptkp', 'joined_at', 'left_at', 'is_active'])->all())->save();
            $this->access->audit($user, $company->id, 'employee.saved', 'employee:'.$employee->id);

            return $employee;
        });
    }

    /** @param array<string, mixed> $data */
    public function saveEntry(User $user, Company $company, array $data, ?PayrollEntry $entry = null): PayrollEntry
    {
        Gate::forUser($user)->authorize('payroll.edit');
        $this->access->company($user, $company->id);

        return DB::transaction(function () use ($user, $company, $data, $entry): PayrollEntry {
            Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            $branch = $company->branches()->findOrFail($data['branch_id']);
            if (empty($data['employee_id']) && ! empty($data['new_employee'])) {
                $identity = Validator::make($data, [
                    'name' => ['required', 'string', 'max:150'], 'nik' => ['required', 'digits:16', Rule::unique('employees')->where('company_id', $company->id)],
                    'position' => ['required', 'string', 'max:150'], 'joined_at' => ['required', 'date_format:Y-m-d'], 'ptkp' => ['required', Rule::in(array_keys(config('tax.ptkp')))],
                ])->validate();
                $data['employee_id'] = $this->saveEmployee($user, $company, $identity + ['is_active' => true])->id;
            }
            $employee = $company->employees()->findOrFail($data['employee_id']);
            if ($entry) {
                $entry = PayrollEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
                $originalRun = $company->payrollRuns()->whereKey($entry->payroll_run_id)->lockForUpdate()->firstOrFail();
                $this->editable($originalRun);
                if ($entry->employee_id !== $employee->id) {
                    throw ValidationException::withMessages(['employee_id' => 'Penerima tidak dapat diganti pada baris yang sudah tersimpan.']);
                }
                if ($entry->version != (int) ($data['version'] ?? 0)) {
                    throw ValidationException::withMessages(['version' => 'Data berubah sejak halaman dibuka. Muat ulang sebelum menyimpan.']);
                }
            }
            // shortcut: One consolidated payment per recipient/module/period; add payment references before supporting separate payments in one month.
            $run = PayrollRun::firstOrCreate(['branch_id' => $branch->id, 'year' => $data['year'], 'month' => $data['month'], 'module' => $data['module']], ['company_id' => $company->id]);
            if ($run->entries()->where('employee_id', $employee->id)->when($entry, fn ($query) => $query->whereKeyNot($entry->id))->exists()) {
                throw ValidationException::withMessages(['employee_id' => 'Pegawai sudah ada pada modul dan periode ini.']);
            }
            if ($entry && $entry->payroll_run_id !== $run->id) {
                $entry->payroll_run_id = $run->id;
                $originalRun->update(['status' => 'Draft', 'reviewed_by' => null, 'reviewed_at' => null]);
                $this->access->audit($user, $company->id, 'payroll.entry.moved', 'entry:'.$entry->id, ['from_run' => $originalRun->id, 'to_run' => $run->id]);
            }
            $this->editable($run);
            $elsewhere = PayrollEntry::where('employee_id', $employee->id)->where('payroll_run_id', '!=', $run->id)->whereHas('payrollRun', fn ($query) => $query->where('company_id', $company->id)->where('year', $data['year'])->where('month', $data['month'])->where('module', $data['module']))->exists();
            if ($elsewhere) {
                throw ValidationException::withMessages(['employee_id' => 'Penghasilan penerima pada masa ini sudah dicatat di cabang lain. Gabungkan penghasilan dalam satu payroll perusahaan.']);
            }
            if (isset($data['employee_name'], $data['employee_nik'], $data['employee_position'])) {
                $identity = Validator::make(['name' => $data['employee_name'], 'nik' => $data['employee_nik'], 'position' => $data['employee_position']], [
                    'name' => ['required', 'string', 'max:150'], 'nik' => ['required', 'digits:16', Rule::unique('employees')->where('company_id', $company->id)->ignore($employee)], 'position' => ['required', 'string', 'max:150'],
                ])->validate();
                $employee = $this->saveEmployee($user, $company, $identity, $employee);
            }
            if ($entry && ! empty($entry->input['is_demo'])) {
                $data['is_demo'] = true;
            }
            $worksheet = array_filter($data['worksheet'] ?? [], fn ($value) => $value !== null && $value !== '');
            if (! array_diff(array_keys($worksheet), ['reason'])) {
                $worksheet = [];
                unset($data['worksheet']);
            } else {
                $data['worksheet'] = $worksheet;
            }
            if ($worksheet || ! empty($entry?->input['worksheet'])) {
                Gate::forUser($user)->authorize('payroll.correct');
                $this->access->audit($user, $company->id, 'payroll.worksheet.changed', 'employee:'.$employee->id, ['before' => $entry?->input['worksheet'] ?? [], 'after' => $worksheet]);
            }
            $calculated = $this->calculateForEmployee($company, $employee, $data, $entry?->id);
            $data['tax_object_code'] = $this->tax->objectCode($data, $calculated['result']);
            $input = array_merge(collect($data)->except(['version', 'return_to', 'new_employee', 'employee_name', 'employee_nik', 'employee_position', 'entry_id'])->all(), ['name' => $employee->name, 'nik' => $employee->nik, 'position' => $employee->position, 'joined_at' => $employee->joined_at->toDateString()]);
            $input = array_merge($input, $calculated['context']);
            $entry ??= new PayrollEntry(['payroll_run_id' => $run->id, 'employee_id' => $employee->id]);
            $entry->fill(['input' => $input, 'result' => $calculated['result'], 'gross' => $calculated['result']['gross'], 'tax' => $calculated['result']['tax'], 'take_home' => $calculated['result']['take_home'], 'version' => $entry->exists ? $entry->version + 1 : 1])->save();
            $run->update(['status' => 'Draft', 'reviewed_by' => null, 'reviewed_at' => null]);
            $this->access->audit($user, $company->id, 'payroll.entry.saved', 'entry:'.$entry->id, ['run_id' => $run->id, 'version' => $entry->version]);

            return $entry;
        });
    }

    /** @param array<string, mixed> $data @return array{context: array<string, mixed>, result: array<string, mixed>} */
    public function preview(User $user, Company $company, Employee $employee, array $data): array
    {
        $this->access->company($user, $company->id);
        abort_unless($employee->company_id === $company->id, 404);

        return $this->calculateForEmployee($company, $employee, $data);
    }

    /** @param array<string, mixed> $data @return array{context: array<string, mixed>, result: array<string, mixed>} */
    private function calculateForEmployee(Company $company, Employee $employee, array $data, ?int $entryId = null): array
    {
        $period = CarbonImmutable::create((int) $data['year'], (int) $data['month'], 1);
        if (isset($data['withholding_date'])) {
            $withholding = CarbonImmutable::parse($data['withholding_date']);
            if ($withholding->year !== (int) $data['year'] || $withholding->month !== (int) $data['month']) {
                throw ValidationException::withMessages(['withholding_date' => 'Tanggal pemotongan harus berada dalam masa pajak.']);
            }
        }
        if ($data['module'] === 'severance' && (! isset($data['final_start_year']) || (int) $data['year'] - (int) $data['final_start_year'] < 0 || (int) $data['year'] - (int) $data['final_start_year'] > 1)) {
            throw ValidationException::withMessages(['final_start_year' => 'Modul final hanya untuk pembayaran dalam dua tahun kalender pertama.']);
        }
        if ($employee->joined_at->startOfMonth()->gt($period) || ($employee->left_at && $employee->left_at->endOfMonth()->lt($period))) {
            throw ValidationException::withMessages(['employee_id' => 'Masa pajak berada di luar masa kerja penerima penghasilan.']);
        }
        $annual = $data['module'] === 'annual' || ($data['module'] === 'pension' && ($data['month'] == 12 || ($data['is_final_period'] ?? false)));
        if ($annual && (int) $data['month'] < 12 && (! $employee->left_at || $employee->left_at->month !== (int) $data['month'] || $employee->left_at->year !== (int) $data['year'])) {
            throw ValidationException::withMessages(['employee_id' => 'Masa terakhir sebelum Desember harus sesuai tanggal selesai pada database penerima.']);
        }
        $other = PayrollEntry::where('employee_id', $employee->id)->when($entryId, fn ($query) => $query->whereKeyNot($entryId))->whereHas('payrollRun', fn ($q) => $q->where('company_id', $company->id)->where('year', $data['year'])->where('month', $data['month'])->whereIn('module', ['monthly', 'annual']));
        if (in_array($data['module'], ['monthly', 'annual'], true) && $other->whereHas('payrollRun', fn ($q) => $q->where('module', '!=', $data['module']))->exists()) {
            throw ValidationException::withMessages(['module' => 'Satu masa hanya boleh menggunakan TER bulanan atau rekonsiliasi terakhir.']);
        }
        $context = [];
        if ($annual) {
            $startMonth = $employee->joined_at->year < $data['year'] ? 1 : $employee->joined_at->month;
            $context['months_worked'] = (int) $data['month'] - $startMonth + 1;
            $context['annual_components'] = [];
            foreach (['salary', 'allowance', 'overtime', 'bonus', 'thr', 'bpjs_company', 'pension_employee', 'jht_employee'] as $component) {
                $context['annual_components'][$component] = (int) ($data[$component] ?? 0);
            }
            $context['annual_components']['pension_employee'] += (int) ($data['opening_pension'] ?? 0);
            $context['annual_components']['salary'] += (int) ($data['opening_gross'] ?? 0);
            $context['annual_components']['prior_tax_allowance'] = 0;
            $prior = PayrollEntry::with('payrollRun')->where('employee_id', $employee->id)->when($entryId, fn ($query) => $query->whereKeyNot($entryId))->whereHas('payrollRun', fn ($q) => $q->where('company_id', $company->id)->where('year', $data['year'])->where('month', '<', $data['month'])->where('module', $data['module'] === 'pension' ? 'pension' : 'monthly'))->get();
            if ($prior->contains(fn ($row) => $row->payrollRun->status !== 'Finalized')) {
                throw ValidationException::withMessages(['opening_reason' => 'Finalisasi seluruh masa sebelumnya sebelum rekonsiliasi.']);
            }
            $recordedMonths = $prior->pluck('payrollRun.month')->unique()->count();
            if ($recordedMonths < $context['months_worked'] - 1 && empty($data['opening_reason'])) {
                throw ValidationException::withMessages(['opening_reason' => 'Riwayat masa sebelumnya belum lengkap. Masukkan saldo awal dan alasan, tanpa estimasi otomatis.']);
            }
            foreach (['gross', 'tax', 'pension', 'zakat'] as $field) {
                $context['prior_'.$field] = (int) ($data['opening_'.$field] ?? 0);
            }
            foreach ($prior as $previous) {
                $context['prior_gross'] += $previous->gross;
                $context['prior_tax'] += $previous->tax;
                $context['prior_pension'] += (int) ($previous->input['pension_employee'] ?? 0) + (int) ($previous->input['jht_employee'] ?? 0);
                $context['prior_zakat'] += (int) ($previous->input['zakat'] ?? 0);
                foreach (['salary', 'allowance', 'overtime', 'bonus', 'thr', 'bpjs_company', 'pension_employee', 'jht_employee'] as $component) {
                    $context['annual_components'][$component] += (int) ($previous->input[$component] ?? 0);
                }
                $context['annual_components']['prior_tax_allowance'] += (int) ($previous->result['tax_allowance'] ?? 0);
            }
            if ($recordedMonths >= $context['months_worked'] - 1 && array_sum(array_map(fn ($field) => (int) ($data['opening_'.$field] ?? 0), ['gross', 'tax', 'pension', 'zakat'])) > 0) {
                throw ValidationException::withMessages(['opening_gross' => 'Riwayat lengkap; saldo awal akan menggandakan penghasilan.']);
            }
        }

        if ($annual && ! empty($data['worksheet'])) {
            foreach (['salary', 'allowance', 'overtime', 'bonus', 'thr', 'bpjs_company', 'pension_employee', 'jht_employee'] as $component) {
                if (isset($data['worksheet'][$component])) {
                    $context['annual_components'][$component] = (int) $data['worksheet'][$component];
                }
            }
            if (isset($data['worksheet']['months_worked'])) {
                if ((int) $data['worksheet']['months_worked'] > $context['months_worked']) {
                    throw ValidationException::withMessages(['worksheet.months_worked' => 'Jumlah bulan kerja tidak boleh melebihi masa kerja pada database penerima.']);
                }
                $context['months_worked'] = (int) $data['worksheet']['months_worked'];
            }
        }

        return ['context' => $context, 'result' => $this->tax->calculate(array_merge($data, $context), $context['annual_components'] ?? [])];
    }

    public function remove(User $user, Company $company, PayrollEntry $entry): void
    {
        Gate::forUser($user)->authorize('payroll.edit');
        $this->access->company($user, $company->id);
        DB::transaction(function () use ($user, $company, $entry): void {
            Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            $run = $company->payrollRuns()->whereKey($entry->payroll_run_id)->lockForUpdate()->firstOrFail();
            $this->editable($run);
            $this->access->audit($user, $company->id, 'payroll.entry.deleted', 'entry:'.$entry->id, ['input' => $entry->input, 'result' => $entry->result]);
            $entry->delete();
            $run->update(['status' => 'Draft', 'reviewed_by' => null, 'reviewed_at' => null]);
        });
    }

    private function editable(PayrollRun $run): void
    {
        if ($run->status === 'Finalized') {
            throw ValidationException::withMessages(['run' => 'Payroll sudah final. Buka koreksi dengan alasan terlebih dahulu.']);
        }
    }

    public function transition(User $user, Company $company, PayrollRun $run, string $action, ?string $reason = null): void
    {
        $permission = ['review' => 'payroll.review', 'finalize' => 'payroll.finalize', 'correct' => 'payroll.correct'][$action] ?? abort(422);
        Gate::forUser($user)->authorize($permission);
        $this->access->company($user, $company->id);
        DB::transaction(function () use ($user, $company, $run, $action, $reason): void {
            $company = Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            $run = $company->payrollRuns()->with(['entries', 'branch'])->whereKey($run->id)->lockForUpdate()->firstOrFail();
            if (! $run->entries->count()) {
                throw ValidationException::withMessages(['run' => 'Payroll kosong tidak dapat diproses.']);
            }
            if ($action === 'correct') {
                if ($run->status !== 'Finalized' || ! $reason) {
                    throw ValidationException::withMessages(['reason' => 'Koreksi memerlukan payroll final dan alasan.']);
                }
                $dependent = PayrollEntry::whereIn('employee_id', $run->entries->pluck('employee_id'))->whereHas('payrollRun', fn ($q) => $q->where('company_id', $company->id)->where('year', $run->year)->where('month', '>', $run->month)->where('status', 'Finalized')->whereIn('module', ['annual', 'pension']))->exists();
                if ($dependent) {
                    throw ValidationException::withMessages(['run' => 'Buka koreksi rekonsiliasi akhir yang terkait terlebih dahulu.']);
                }
                $this->access->audit($user, $company->id, 'payroll.revision.archived', 'run:'.$run->id, ['reason' => $reason, 'revision' => $run->revision, 'company' => $run->company_snapshot, 'entries' => $run->entries->map(fn ($entry) => ['employee_id' => $entry->employee_id, 'input' => $entry->input, 'result' => $entry->result])->all()]);
                $run->update(['status' => 'Draft', 'revision' => $run->revision + 1, 'reviewed_by' => null, 'reviewed_at' => null, 'finalized_at' => null, 'company_snapshot' => null]);

                return;
            }
            $expected = $action === 'review' ? 'Draft' : 'Reviewed';
            if ($run->status !== $expected) {
                throw ValidationException::withMessages(['run' => 'Urutan proses: Draft, Reviewed, Finalized.']);
            }
            $employees = $company->employees()->whereIn('id', $run->entries->pluck('employee_id'))->get()->keyBy('id');
            foreach ($run->entries as $entry) {
                $calculated = $this->calculateForEmployee($company, $employees[$entry->employee_id], $entry->input);
                if ($calculated['result'] !== $entry->result) {
                    throw ValidationException::withMessages(['run' => 'Riwayat atau aturan berubah. Simpan ulang baris sebelum review/finalisasi.']);
                }
            }
            $run->update($action === 'review'
                ? ['status' => 'Reviewed', 'reviewed_by' => $user->id, 'reviewed_at' => now()]
                : ['status' => 'Finalized', 'finalized_at' => now(), 'company_snapshot' => array_merge($company->only(['name', 'npwp', 'address', 'city', 'signer_name', 'signer_npwp']), ['branch_name' => $run->branch->name, 'nitku' => $run->branch->nitku])]);
            $this->access->audit($user, $company->id, 'payroll.'.$action, 'run:'.$run->id, ['revision' => $run->revision]);
        });
    }

    /** @param array<string, mixed> $selection */
    public function bulk(User $user, Company $company, array $selection, string $action, int $value = 0): int
    {
        Gate::forUser($user)->authorize('payroll.edit');
        $this->access->company($user, $company->id);

        return DB::transaction(function () use ($user, $company, $selection, $action, $value): int {
            Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            $run = $company->payrollRuns()->where(collect($selection)->only(['branch_id', 'year', 'month', 'module'])->all())->first();
            if (! $run) {
                throw ValidationException::withMessages(['run' => 'Tidak ada payroll pada periode terpilih.']);
            }
            $this->editable($run);
            $count = 0;
            foreach ($run->entries()->lazyById(100) as $entry) {
                $data = $entry->input;
                if ($action === 'thr') {
                    $data['thr'] = (int) $data['salary'];
                } elseif ($action === 'reset_thr') {
                    $data['thr'] = 0;
                } elseif ($action === 'bonus') {
                    $data['bonus'] = intdiv((int) $data['salary'] * $value, 100);
                } else {
                    $data['method'] = $action;
                }
                $data['version'] = $entry->version;
                $this->saveEntry($user, $company, $data, $entry);
                $count++;
            }

            return $count;
        });
    }

    /** @param array<string, mixed> $selection */
    public function pullMonthly(User $user, Company $company, array $selection): int
    {
        Gate::forUser($user)->authorize('payroll.edit');
        $this->access->company($user, $company->id);

        return DB::transaction(function () use ($user, $company, $selection): int {
            Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            $company->branches()->findOrFail($selection['branch_id']);
            $source = PayrollEntry::with(['payrollRun', 'employee'])->whereHas('payrollRun', fn ($query) => $query->where('company_id', $company->id)->where('branch_id', $selection['branch_id'])->where('year', $selection['year'])->where('month', '<', $selection['month'])->where('module', 'monthly'))->get();
            if ($source->contains(fn ($entry) => $entry->payrollRun->status !== 'Finalized')) {
                throw ValidationException::withMessages(['run' => 'Finalisasi seluruh data bulanan sumber sebelum menarik data tahunan.']);
            }
            if ($source->isEmpty()) {
                throw ValidationException::withMessages(['run' => 'Tidak ada data bulanan pada cabang dan tahun terpilih.']);
            }
            $count = 0;
            foreach ($source->groupBy('employee_id') as $records) {
                $latest = $records->sortByDesc(fn ($entry) => $entry->payrollRun->month)->first();
                if ($latest->employee->left_at && $latest->employee->left_at->month < (int) $selection['month']) {
                    continue;
                }
                $existing = PayrollEntry::where('employee_id', $latest->employee_id)->whereHas('payrollRun', fn ($query) => $query->where('company_id', $company->id)->where('branch_id', $selection['branch_id'])->where('year', $selection['year'])->where('month', $selection['month'])->where('module', 'annual'))->first();
                $data = array_merge($latest->input, $existing?->input ?? [], $selection, ['module' => 'annual', 'withholding_date' => CarbonImmutable::create((int) $selection['year'], (int) $selection['month'], 1)->endOfMonth()->toDateString(), 'version' => $existing?->version]);
                foreach (['salary', 'allowance', 'overtime', 'bpjs_company', 'pension_employee', 'jht_employee', 'ptkp', 'method'] as $field) {
                    $data[$field] = $latest->input[$field] ?? 0;
                }
                if (! $existing) {
                    $data['bonus'] = 0;
                    $data['thr'] = 0;
                }
                $this->saveEntry($user, $company, $data, $existing);
                $count++;
            }
            $this->access->audit($user, $company->id, 'payroll.annual.synced', 'company:'.$company->id, ['rows' => $count, 'period' => $selection]);

            return $count;
        });
    }

    /** @param array<string, mixed> $selection */
    public function resetDemo(User $user, Company $company, array $selection): int
    {
        Gate::forUser($user)->authorize('payroll.edit');
        $this->access->company($user, $company->id);

        return DB::transaction(function () use ($user, $company, $selection): int {
            Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            $run = $company->payrollRuns()->where(collect($selection)->only(['branch_id', 'year', 'month', 'module'])->all())->first();
            if (! $run) {
                return 0;
            }
            $this->editable($run);
            $count = 0;
            foreach ($run->entries()->where('input->is_demo', true)->get() as $entry) {
                $employee = $entry->employee;
                $this->remove($user, $company, $entry);
                if (! PayrollEntry::where('employee_id', $employee->id)->exists()) {
                    $employee->delete();
                }
                $count++;
            }

            return $count;
        });
    }

    /** @param array<string, mixed> $selection */
    public function copyPrevious(User $user, Company $company, array $selection): int
    {
        Gate::forUser($user)->authorize('payroll.edit');
        $this->access->company($user, $company->id);
        $date = CarbonImmutable::create((int) $selection['year'], (int) $selection['month'], 1)->subMonth();
        $sourceModule = in_array($selection['module'], ['monthly', 'annual'], true) ? ($date->month === 12 ? 'annual' : 'monthly') : $selection['module'];
        $source = $company->payrollRuns()->with('entries')->where('branch_id', $selection['branch_id'])->where('year', $date->year)->where('month', $date->month)->where('module', $sourceModule)->where('status', 'Finalized')->first();
        if (! $source) {
            throw ValidationException::withMessages(['run' => 'Tidak ada payroll final sumber pada modul dan bulan sebelumnya.']);
        }

        return DB::transaction(function () use ($user, $company, $selection, $source): int {
            $count = 0;
            foreach ($source->entries as $entry) {
                $data = array_merge($entry->input, $selection, ['bonus' => 0, 'thr' => 0, 'overtime' => 0, 'opening_gross' => 0, 'opening_tax' => 0, 'opening_pension' => 0, 'opening_zakat' => 0, 'opening_reason' => null, 'months_worked' => (int) $selection['month'], 'is_final_period' => false, 'withholding_date' => CarbonImmutable::create((int) $selection['year'], (int) $selection['month'], 1)->endOfMonth()->toDateString()]);
                unset($data['worksheet'], $data['document_number'], $data['document_date']);
                $this->saveEntry($user, $company, $data);
                $count++;
            }

            return $count;
        });
    }
}
