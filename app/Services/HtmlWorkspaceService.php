<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\ImportBatch;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HtmlWorkspaceService
{
    public const SUBJECTS = ['pegawai_bulanan' => 'monthly', 'pegawai_tahunan' => 'annual', 'pensiunan' => 'pension', 'tidak_tetap' => 'daily', 'bukan_pegawai' => 'contractor', 'pesangon_final' => 'severance'];

    public const AMOUNTS = ['gajiPokok', 'tunjangan', 'bonus', 'thr', 'kompensasi', 'bpjs', 'iuranPensiun', 'iuranJht', 'a1ManualRegBruto', 'a1ManualIrregBruto', 'a1ManualBpjsComp', 'a1ManualBiayaJabatan', 'a1ManualIuranPensiun', 'a1ManualIuranJht', 'a1ManualPriorTer'];

    public function __construct(private AccessService $access, private TaxService $tax, private CompanyService $companies) {}

    /** @return array<string, mixed> */
    public function state(User $user): array
    {
        Gate::forUser($user)->authorize('payroll.view');
        $companies = $this->access->companies($user)->with('branches')->orderBy('name')->get();
        $branches = $companies->flatMap(fn (Company $company) => $company->branches->map(function (Branch $branch) use ($company): Branch {
            $branch->setRelation('company', $company);

            return $branch;
        }));
        $records = PayrollEntry::with('payrollRun')->whereHas('payrollRun', fn ($query) => $query->whereIn('company_id', $companies->modelKeys()))->orderByDesc('id')->get();
        $active = $branches->firstWhere('company_id', (int) session('active_company_id')) ?? $branches->first();

        return [
            'companies' => $branches->map(fn (Branch $branch): array => $this->companyRecord($branch))->values()->all(),
            'employees' => $records->map(fn (PayrollEntry $entry): array => $this->employeeRecord($entry))->all(),
            'activeCompanyId' => $active ? 'branch_'.$active->id : null,
            'user' => ['name' => $user->name, 'username' => $user->username, 'role' => $user->role],
            'permissions' => array_values(array_filter(config('taxcore.permissions'), fn (string $permission): bool => $user->can($permission))),
            'urls' => ['state' => route('workspace.state'), 'saveCompany' => route('workspace.companies.save'), 'access' => route('access.index'), 'logout' => route('logout'), 'profile' => route('profile.edit')],
        ];
    }

    /** @return array<string, mixed> */
    public function companyRecord(Branch $branch): array
    {
        $company = $branch->company;
        $profile = $branch->profile ?? [];

        return [
            'id' => 'branch_'.$branch->id, 'companyId' => $company->id, 'branchId' => $branch->id,
            'name' => $profile['name'] ?? $company->name, 'branchName' => $branch->name,
            'npwp' => $company->npwp, 'nitkuSuffix' => $branch->code, 'idTkuFull' => $branch->nitku,
            'signerName' => $profile['signerName'] ?? $company->signer_name,
            'signerNpwp' => $profile['signerNpwp'] ?? $company->signer_npwp,
            'city' => $profile['city'] ?? $company->city, 'address' => $profile['address'] ?? $company->address ?? '',
            'saveUrl' => route('workspace.employees', [$company, $branch]),
            'exportUrl' => route('workspace.export', [$company, $branch]),
            'deleteUrl' => route('workspace.companies.destroy', $branch),
            'auditUrl' => route('audit.index', [$company, '_dialog' => 1]),
        ];
    }

    /** @return array<string, mixed> */
    private function employeeRecord(PayrollEntry $entry): array
    {
        $input = $entry->input;
        $run = $entry->payrollRun;
        $record = $input['html_record'] ?? [
            'id' => 'entry_'.$entry->id, 'month' => $run->month, 'year' => $run->year,
            'name' => $input['name'] ?? '', 'nik' => $input['nik'] ?? '', 'position' => $input['position'] ?? 'Staff',
            'subjectType' => array_search($run->module, self::SUBJECTS, true) ?: 'pegawai_bulanan', 'ptkp' => $input['ptkp'] ?? 'TK/0', 'taxMethod' => $input['method'] ?? 'GROSS',
            'gajiPokok' => $input['salary'] ?? 0, 'tunjangan' => $input['allowance'] ?? 0, 'bonus' => $input['bonus'] ?? 0, 'thr' => $input['thr'] ?? 0,
            'kompensasi' => $input['overtime'] ?? 0, 'bpjs' => $input['bpjs_company'] ?? 0, 'iuranPensiun' => $input['pension_employee'] ?? 0, 'iuranJht' => $input['jht_employee'] ?? 0,
            'workMonths' => $input['months_worked'] ?? 12, 'workDays' => $input['work_days'] ?? 1, 'dailyScheme' => ($input['daily_scheme'] ?? 'DAILY') === 'MONTHLY' ? 'BULANAN' : 'HARIAN',
            'finalSubType' => ($input['final_kind'] ?? 'SEVERANCE') === 'PENSION' ? 'MANFAAT_PENSIUN' : 'PESANGON', 'hasNpwp' => true, 'syncedFromMonthly' => false,
        ];

        return array_merge($record, ['companyId' => 'branch_'.$run->branch_id, '_version' => $entry->version]);
    }

    /** @param array<string, mixed> $data @return array<int, array<string, mixed>> */
    public function saveEmployees(User $user, Company $company, Branch $branch, array $data): array
    {
        Gate::forUser($user)->authorize('payroll.edit');
        $this->access->company($user, $company->id);
        abort_unless($branch->company_id === $company->id, 404);

        return DB::transaction(function () use ($user, $company, $branch, $data): array {
            Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            $entries = PayrollEntry::with('payrollRun')->whereHas('payrollRun', fn ($query) => $query->where('company_id', $company->id)->where('branch_id', $branch->id))->lockForUpdate()->get();
            $byClientId = $entries->keyBy(fn (PayrollEntry $entry): string => $entry->input['html_record']['id'] ?? 'entry_'.$entry->id);
            foreach ($data['deletes'] as $deleted) {
                $entry = $byClientId->get($deleted['id']);
                abort_unless($entry, 404);
                $this->checkVersion($entry, $deleted);
                $this->access->audit($user, $company->id, 'payroll.entry.deleted', 'entry:'.$entry->id, ['record' => $this->employeeRecord($entry)]);
                $entry->delete();
            }
            foreach ($data['upserts'] as $record) {
                if (isset($record['companyId']) && $record['companyId'] !== 'branch_'.$branch->id) {
                    abort(404);
                }
                $entry = $byClientId->get($record['id']);
                if ($entry) {
                    $this->checkVersion($entry, $record);
                } elseif (! empty($record['_version']) || str_starts_with($record['id'], 'entry_')) {
                    abort(404);
                }
                $overrides = array_filter(self::AMOUNTS, fn (string $field): bool => str_starts_with($field, 'a1Manual'));
                $before = array_intersect_key($entry?->input['html_record'] ?? [], array_flip($overrides));
                $after = array_intersect_key($record, array_flip($overrides));
                if (array_filter($before, fn ($value): bool => $value !== null) !== array_filter($after, fn ($value): bool => $value !== null)) {
                    Gate::forUser($user)->authorize('payroll.correct');
                    $this->access->audit($user, $company->id, 'payroll.worksheet.changed', 'record:'.$record['id'], ['before' => $before, 'after' => $after]);
                }
                if (in_array($record['id'], array_column($data['deletes'], 'id'), true)) {
                    throw ValidationException::withMessages(['upserts' => 'Baris yang sama tidak dapat disimpan dan dihapus sekaligus.']);
                }
                $employee = $company->employees()->firstOrNew(['nik' => $record['nik']]);
                $employee->fill(['name' => $record['name'], 'position' => $record['position'], 'ptkp' => $record['ptkp']]);
                if (! $employee->exists) {
                    $employee->fill(['joined_at' => $record['year'].'-01-01', 'is_active' => true]);
                }
                $employee->save();
                $module = self::SUBJECTS[$record['subjectType']];
                $run = PayrollRun::firstOrCreate(['branch_id' => $branch->id, 'year' => $record['year'], 'month' => $record['month'], 'module' => $module], ['company_id' => $company->id]);
                if ($run->entries()->where('employee_id', $employee->id)->when($entry, fn ($query) => $query->whereKeyNot($entry->id))->exists()) {
                    throw ValidationException::withMessages(['upserts' => 'NIK sudah tercatat pada modul dan periode yang sama.']);
                }
                $input = $this->taxInput($record, $module) + ['branch_id' => $branch->id, 'employee_id' => $employee->id, 'name' => $employee->name, 'nik' => $employee->nik, 'position' => $employee->position, 'joined_at' => $employee->joined_at->toDateString()];
                $calculationInput = $input;
                if ($module === 'monthly' && $record['month'] === 12) {
                    $calculationInput['module'] = 'annual';
                }
                $result = $this->tax->calculate($calculationInput);
                $input['tax_object_code'] = $this->tax->objectCode($input, $result);
                $input['withholding_date'] = sprintf('%04d-%02d-01', $record['year'], $record['month']);
                unset($record['_version']);
                $input['html_record'] = $record;
                $entry ??= new PayrollEntry;
                $entry->fill(['payroll_run_id' => $run->id, 'employee_id' => $employee->id, 'input' => $input, 'result' => $result, 'gross' => $result['gross'], 'tax' => $result['tax'], 'take_home' => $result['take_home'], 'version' => $entry->exists ? $entry->version + 1 : 1])->save();
                $this->access->audit($user, $company->id, 'payroll.entry.saved', 'entry:'.$entry->id, ['run_id' => $run->id, 'version' => $entry->version]);
            }

            return PayrollEntry::with('payrollRun')->whereHas('payrollRun', fn ($query) => $query->where('company_id', $company->id)->where('branch_id', $branch->id))->orderByDesc('id')->get()->map(fn (PayrollEntry $entry): array => $this->employeeRecord($entry))->all();
        });
    }

    /** @param array<string, mixed> $record */
    private function checkVersion(PayrollEntry $entry, array $record): void
    {
        if ($entry->version !== (int) ($record['_version'] ?? 0)) {
            throw ValidationException::withMessages(['version' => 'Data berubah sejak halaman dibuka. Muat ulang sebelum menyimpan.']);
        }
    }

    /** @param array<string, mixed> $record @return array<string, mixed> */
    private function taxInput(array $record, string $module): array
    {
        $input = ['module' => $module, 'year' => $record['year'], 'month' => $record['month'], 'ptkp' => $record['ptkp'], 'method' => $record['taxMethod'], 'months_worked' => min($record['workMonths'] ?? 12, $record['month']), 'work_days' => $record['workDays'] ?? 1, 'daily_scheme' => ($record['dailyScheme'] ?? 'HARIAN') === 'BULANAN' ? 'MONTHLY' : 'DAILY', 'final_kind' => ($record['finalSubType'] ?? 'PESANGON') === 'MANFAAT_PENSIUN' ? 'PENSION' : 'SEVERANCE', 'tax_object_code' => '21-100-07'];
        foreach (['gajiPokok' => 'salary', 'tunjangan' => 'allowance', 'bonus' => 'bonus', 'thr' => 'thr', 'kompensasi' => 'overtime', 'bpjs' => 'bpjs_company', 'iuranPensiun' => 'pension_employee', 'iuranJht' => 'jht_employee'] as $source => $target) {
            $input[$target] = (int) round($record[$source] ?? 0);
        }

        return $input;
    }

    /** @param array<string, mixed> $data */
    public function saveCompany(User $user, array $data): Branch
    {
        Gate::forUser($user)->authorize('company.manage');

        return DB::transaction(function () use ($user, $data): Branch {
            $branch = ! empty($data['id']) && str_starts_with($data['id'], 'branch_') ? Branch::findOrFail(substr($data['id'], 7)) : null;
            $company = $branch?->company ?? Company::where('npwp', $data['npwp'])->first();
            $isNewCompany = ! $company;
            if ($company) {
                $this->access->company($user, $company->id);
            }
            Validator::make($data, ['npwp' => [Rule::unique('companies')->ignore($company)], 'nitkuSuffix' => [Rule::unique('branches', 'code')->where('company_id', $company?->id)->ignore($branch)]])->validate();
            $profileFields = ['name', 'signerName', 'signerNpwp', 'city', 'address'];
            if ($company) {
                foreach ($company->branches()->whereNull('profile')->get() as $legacyBranch) {
                    $legacyBranch->setRelation('company', $company);
                    $legacyBranch->update(['profile' => collect($this->companyRecord($legacyBranch))->only($profileFields)->all()]);
                }
            }
            $company = $this->companies->save($user, ['name' => $data['name'], 'npwp' => $data['npwp'], 'city' => $data['city'], 'address' => $data['address'] ?? '', 'signer_name' => $data['signerName'] ?? '', 'signer_npwp' => $data['signerNpwp'] ?? ''], $company);
            $branch ??= $isNewCompany ? $company->branches()->first() : $company->branches()->where('code', $data['nitkuSuffix'])->first();

            $branch = $this->companies->saveBranch($user, $company, ['name' => $data['branchName'], 'code' => $data['nitkuSuffix']], $branch);
            $branch->update(['profile' => collect($data + ['signerName' => '', 'signerNpwp' => '', 'address' => ''])->only($profileFields)->all()]);

            return $branch->setRelation('company', $company);
        });
    }

    public function deleteCompany(User $user, Branch $branch): void
    {
        Gate::forUser($user)->authorize('company.manage');
        $company = $this->access->company($user, $branch->company_id);
        DB::transaction(function () use ($user, $branch, $company): void {
            Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            if (PayrollEntry::whereHas('payrollRun', fn ($query) => $query->where('branch_id', $branch->id))->exists() || ImportBatch::where('branch_id', $branch->id)->exists()) {
                throw ValidationException::withMessages(['company' => 'Perusahaan / TKU dengan riwayat payroll atau impor tidak dapat dihapus.']);
            }
            PayrollRun::where('branch_id', $branch->id)->delete();
            if ($company->branches()->count() === 1) {
                $this->companies->remove($user, $company);
            } else {
                $this->access->audit($user, $company->id, 'branch.deleted', 'branch:'.$branch->id, $branch->only(['name', 'code']));
                $branch->delete();
            }
        });
    }
}
