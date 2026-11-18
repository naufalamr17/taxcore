<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ImportBatch;
use App\Models\PayrollEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PayrollImportService
{
    public const HEADERS = [
        'Masa_Pajak' => 'month', 'Tahun_Pajak' => 'year', 'NIK' => 'nik', 'Nama' => 'name', 'Jabatan' => 'position', 'PTKP' => 'ptkp', 'Metode_Pajak' => 'method', 'Tanggal_Masuk' => 'joined_at',
        'Gaji_Pokok' => 'salary', 'Tunjangan' => 'allowance', 'Biaya_Non_Jasa' => 'contractor_expenses', 'Referensi_Biaya_Non_Jasa' => 'contractor_expense_reference', 'Bonus' => 'bonus', 'THR' => 'thr', 'Kompensasi_Lembur' => 'overtime', 'BPJS_Perusahaan' => 'bpjs_company',
        'Iuran_Pensiun' => 'pension_employee', 'Iuran_JHT' => 'jht_employee', 'BPJS_Kesehatan_Pegawai' => 'health_employee', 'Potongan_Lain' => 'other_deductions', 'Zakat' => 'zakat',
        'Hari_Kerja' => 'work_days', 'Skema_Upah' => 'daily_scheme', 'Jenis_Final' => 'final_kind', 'Masa_Terakhir' => 'is_final_period',
        'Saldo_Awal_Bruto' => 'opening_gross', 'Saldo_Awal_Pajak' => 'opening_tax', 'Saldo_Awal_Iuran' => 'opening_pension', 'Saldo_Awal_Zakat' => 'opening_zakat', 'Alasan_Saldo_Awal' => 'opening_reason',
        'Kode_Objek_Pajak' => 'tax_object_code', 'Nomor_Dokumen' => 'document_number', 'Tanggal_Dokumen' => 'document_date', 'NITKU_Penerima' => 'recipient_nitku', 'Tahun_Awal_Final' => 'final_start_year', 'Saldo_Awal_Hanya_Gaji' => 'opening_salary_only',
    ];

    public function __construct(private AccessService $access, private TaxService $tax, private PayrollService $payroll) {}

    /** @param array<int, array<int, string>> $rows */
    public function preview(User $user, Company $company, int $branchId, string $module, array $rows, array $options = []): ImportBatch
    {
        Gate::forUser($user)->authorize('payroll.import');
        Gate::forUser($user)->authorize('payroll.edit');
        $this->access->company($user, $company->id);
        $company->branches()->findOrFail($branchId);
        $options += ['mode' => 'APPEND', 'period_source' => 'FILE'];
        Validator::make($options, [
            'mode' => ['in:APPEND,REPLACE'], 'period_source' => ['in:FILE,ACTIVE'],
            'year' => ['required_if:period_source,ACTIVE', 'nullable', 'integer', 'between:2024,2026'],
            'month' => ['required_if:period_source,ACTIVE', 'nullable', 'integer', 'between:1,12'],
        ])->validate();
        $map = [];
        foreach (self::HEADERS as $header => $field) {
            $map[preg_replace('/[^a-z0-9]/', '', strtolower($header))] = $field;
            $map[str_replace('_', '', $field)] = $field;
        }
        foreach (['Nama_Pegawai' => 'name', 'Nama_Penerima_Pensiun' => 'name', 'Nama_Pekerja' => 'name', 'Nama_Tenaga_Ahli' => 'name', 'Nama_Penerima' => 'name', 'NIK_NPWP' => 'nik', 'Status_PTKP' => 'ptkp', 'Tunjangan_Tetap' => 'allowance', 'Bonus_Jasa_Produksi' => 'bonus', 'Premi_BPJS_Perusahaan' => 'bpjs_company'] as $header => $field) {
            $map[preg_replace('/[^a-z0-9]/', '', strtolower($header))] = $field;
        }
        $headers = array_shift($rows) ?? [];
        $fields = array_map(fn ($header) => $map[preg_replace('/[^a-z0-9]/', '', strtolower(trim($header)))] ?? null, $headers);
        if (count(array_filter($fields)) !== count(array_unique(array_filter($fields)))) {
            throw ValidationException::withMessages(['file' => 'Header impor berulang. Gunakan template yang disediakan.']);
        }
        foreach (array_merge(['nik', 'name', 'position', 'ptkp', 'method', 'salary', 'joined_at'], $options['period_source'] === 'ACTIVE' ? [] : ['month', 'year']) as $required) {
            if (! in_array($required, $fields, true)) {
                throw ValidationException::withMessages(['file' => 'Kolom wajib tidak ditemukan: '.$required]);
            }
        }
        $records = [];
        $errors = [];
        $seen = [];
        foreach ($rows as $index => $row) {
            $data = ['branch_id' => $branchId, 'module' => $module];
            foreach ($fields as $column => $field) {
                if ($field !== null && isset($row[$column]) && trim($row[$column]) !== '') {
                    $data[$field] = trim($row[$column]);
                }
            }
            if ($options['period_source'] === 'ACTIVE') {
                $data['year'] = $options['year'];
                $data['month'] = $options['month'];
            }
            $data['nik'] = ltrim($data['nik'] ?? '', "'");
            foreach (array_merge(TaxService::MONEY_FIELDS, ['opening_gross', 'opening_tax', 'opening_pension', 'opening_zakat']) as $moneyField) {
                $raw = (string) ($data[$moneyField] ?? '0');
                if (preg_match('/^[0-9]{1,3}(\.[0-9]{3})+$/', $raw)) {
                    $raw = str_replace('.', '', $raw);
                } elseif (preg_match('/^[0-9]+\.0+$/', $raw)) {
                    $raw = strstr($raw, '.', true);
                }
                $data[$moneyField] = $raw;
            }
            if (isset($data['is_final_period'])) {
                $boolean = strtolower((string) $data['is_final_period']);
                if (in_array($boolean, ['1', 'true', 'ya', 'yes'], true)) {
                    $data['is_final_period'] = true;
                } elseif (in_array($boolean, ['0', 'false', 'tidak', 'no'], true)) {
                    $data['is_final_period'] = false;
                }
            }
            if (isset($data['month'],$data['year']) && ctype_digit((string) $data['month']) && ctype_digit((string) $data['year']) && (int) $data['month'] >= 1 && (int) $data['month'] <= 12 && (int) $data['year'] >= 2024 && (int) $data['year'] <= 2026) {
                $data['withholding_date'] = CarbonImmutable::create((int) $data['year'], (int) $data['month'], 1)->endOfMonth()->toDateString();
            }
            $rules = $this->tax->rules() + [
                'nik' => ['required', 'digits:16'], 'name' => ['required', 'string', 'max:150'], 'position' => ['required', 'string', 'max:150'], 'joined_at' => ['required', 'date_format:Y-m-d'],
                'tax_object_code' => ['required', 'regex:/^21-[0-9]{3}-[0-9]{2}$/'], 'document_number' => ['nullable', 'string', 'max:100'], 'document_date' => ['nullable', 'date_format:Y-m-d'], 'recipient_nitku' => ['nullable', 'digits:22'],
                'final_start_year' => ['nullable', 'integer', 'between:2020,2026'], 'withholding_date' => ['required', 'date_format:Y-m-d'], 'opening_salary_only' => ['nullable', 'boolean'],
            ];
            foreach (['gross', 'tax', 'pension', 'zakat'] as $field) {
                $rules['opening_'.$field] = ['nullable', 'integer', 'between:0,1000000000000'];
            }
            $validator = Validator::make($data, $rules);
            $key = ($data['nik'] ?? '').':'.($data['year'] ?? '').':'.($data['month'] ?? '');
            if (isset($seen[$key])) {
                $validator->after(fn ($validation) => $validation->errors()->add('nik', 'NIK dan masa pajak berulang dalam file ini.'));
            }
            $seen[$key] = true;
            if ($validator->fails()) {
                $errors[] = ['row' => $index + 2, 'messages' => $validator->errors()->all()];

                continue;
            }
            $validated = $validator->validated() + ['branch_id' => $branchId];
            $records[] = $validated + (! empty($options['demo']) ? ['is_demo' => true] : []);
        }
        if (! $rows) {
            throw ValidationException::withMessages(['file' => 'File tidak berisi baris data.']);
        }

        $options['periods'] = collect($records)->map(fn ($record) => ['year' => (int) $record['year'], 'month' => (int) $record['month']])->unique(fn ($period) => $period['year'].':'.$period['month'])->values()->all();
        if ($options['mode'] === 'REPLACE' && ! $errors) {
            $options['replacement_snapshot'] = $this->replacementSnapshot($company, $branchId, $module, $options['periods']);
        }

        return ImportBatch::create(['options' => $options, 'company_id' => $company->id, 'branch_id' => $branchId, 'user_id' => $user->id, 'module' => $module, 'records' => $records, 'errors' => $errors, 'row_count' => count($rows), 'status' => 'Preview']);
    }

    public function commit(ImportBatch $batch): void
    {
        DB::transaction(function () use ($batch): void {
            $batch = ImportBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            if ($batch->status === 'Completed') {
                return;
            }
            if ($batch->status !== 'Queued' || $batch->errors) {
                throw ValidationException::withMessages(['import' => 'Impor belum siap atau memiliki kesalahan.']);
            }
            $actor = User::findOrFail($batch->user_id);
            Gate::forUser($actor)->authorize('payroll.import');
            Gate::forUser($actor)->authorize('payroll.edit');
            $company = $this->access->company($actor, $batch->company_id);
            Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            $company->branches()->findOrFail($batch->branch_id);
            $options = $batch->options ?? [];
            if (($options['mode'] ?? 'APPEND') === 'REPLACE') {
                $snapshot = $this->replacementSnapshot($company, $batch->branch_id, $batch->module, $options['periods']);
                if ($snapshot !== $options['replacement_snapshot']) {
                    throw ValidationException::withMessages(['import' => 'Data berubah sejak pratinjau. Buat pratinjau baru sebelum mengganti data.']);
                }
                foreach ($snapshot as $row) {
                    $this->payroll->remove($actor, $company, PayrollEntry::findOrFail($row['id']));
                }
            }
            $employees = $company->employees()->whereIn('nik', array_column($batch->records, 'nik'))->get()->keyBy('nik');
            foreach ($batch->records as $record) {
                $employee = $employees->get($record['nik']);
                if (! $employee) {
                    $employee = $this->payroll->saveEmployee($actor, $company, ['name' => $record['name'], 'nik' => $record['nik'], 'position' => $record['position'], 'ptkp' => $record['ptkp'], 'joined_at' => $record['joined_at'], 'is_active' => true]);
                    $employees->put($employee->nik, $employee);
                }
                $this->payroll->saveEntry($actor, $company, $record + ['employee_id' => $employee->id]);
            }
            $batch->update(['status' => 'Completed']);
            $this->access->audit($actor, $company->id, 'import.completed', 'import:'.$batch->id, ['rows' => $batch->row_count, 'mode' => $options['mode'] ?? 'APPEND']);
        });
    }

    /** @param array<int, array{year: int, month: int}> $periods @return array<int, array<string, int|string>> */
    private function replacementSnapshot(Company $company, int $branchId, string $module, array $periods): array
    {
        $runs = $company->payrollRuns()->where('branch_id', $branchId)->where('module', $module)->where(function ($query) use ($periods): void {
            foreach ($periods as $period) {
                $query->orWhere(fn ($query) => $query->where('year', $period['year'])->where('month', $period['month']));
            }
        })->with('entries')->orderBy('id')->get();
        if ($runs->contains('status', 'Finalized')) {
            throw ValidationException::withMessages(['mode' => 'Ganti data hanya tersedia untuk periode draft/review. Data final tetap terkunci.']);
        }

        return $runs->flatMap(fn ($run) => $run->entries->map(fn ($entry) => ['id' => $entry->id, 'version' => $entry->version, 'status' => $run->status]))->sortBy('id')->values()->all();
    }

    /** @return array<int, array<int, string|int>> */
    public function template(string $module, int $year, int $month): array
    {
        $common = ['month', 'year', 'nik', 'name', 'position', 'ptkp', 'method', 'joined_at', 'salary'];
        $specific = match ($module) {
            'annual' => ['allowance', 'bonus', 'thr', 'overtime', 'bpjs_company', 'pension_employee', 'jht_employee', 'opening_gross', 'opening_tax', 'opening_pension', 'opening_zakat', 'opening_reason', 'opening_salary_only'],
            'pension' => ['bonus', 'thr', 'is_final_period', 'opening_gross', 'opening_tax', 'opening_reason', 'opening_salary_only'],
            'daily' => ['allowance', 'work_days', 'daily_scheme'],
            'contractor' => ['allowance', 'contractor_expenses', 'contractor_expense_reference'],
            'severance' => ['final_kind', 'final_prior_gross', 'final_start_year'],
            default => ['allowance', 'bonus', 'thr', 'overtime', 'bpjs_company', 'pension_employee', 'jht_employee', 'health_employee', 'other_deductions', 'zakat'],
        };
        $fields = array_merge($common, $specific, ['tax_object_code', 'document_number', 'document_date', 'recipient_nitku']);
        $headers = array_filter(self::HEADERS, fn ($field) => in_array($field, $fields, true));
        $sample = ['month' => $month, 'year' => $year, 'nik' => '0000000000000001', 'name' => 'Contoh Penerima', 'position' => 'Staff', 'ptkp' => 'TK/0', 'method' => 'GROSS', 'joined_at' => $year.'-01-01', 'salary' => $module === 'daily' ? 500000 : 10000000, 'tax_object_code' => match ($module) {
            'pension' => '21-100-02', 'daily' => '21-100-24', 'severance' => '21-401-01', 'contractor' => '21-100-07', default => '21-100-01'
        }, 'work_days' => 1, 'daily_scheme' => 'DAILY', 'final_kind' => 'SEVERANCE', 'final_start_year' => $year, 'is_final_period' => 0];

        return [array_keys($headers), array_map(fn ($field) => $sample[$field] ?? '', array_values($headers))];
    }

    /** @param array<string, mixed> $selection */
    public function demo(User $user, Company $company, array $selection, int $count): ImportBatch
    {
        $rows = $this->template($selection['module'], (int) $selection['year'], (int) $selection['month']);
        $sample = array_combine($rows[0], $rows[1]);
        $rows = [$rows[0]];
        $token = random_int(1000000, 9999999);
        for ($index = 1; $index <= $count; $index++) {
            $record = $sample;
            $record['NIK'] = '00000'.$token.str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            $record['Nama'] = '[CONTOH] Penerima '.$token.'-'.$index;
            $record['Tanggal_Masuk'] = $selection['year'].'-'.str_pad((string) $selection['month'], 2, '0', STR_PAD_LEFT).'-01';
            $record['Gaji_Pokok'] = $selection['module'] === 'daily' ? 500000 : (7 + $index % 35) * 1000000;
            $rows[] = array_values($record);
        }

        return $this->preview($user, $company, (int) $selection['branch_id'], $selection['module'], $rows, ['demo' => true]);
    }
}
