<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ImportBatch;
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
        'Gaji_Pokok' => 'salary', 'Tunjangan' => 'allowance', 'Bonus' => 'bonus', 'THR' => 'thr', 'Kompensasi_Lembur' => 'overtime', 'BPJS_Perusahaan' => 'bpjs_company',
        'Iuran_Pensiun' => 'pension_employee', 'Iuran_JHT' => 'jht_employee', 'BPJS_Kesehatan_Pegawai' => 'health_employee', 'Potongan_Lain' => 'other_deductions', 'Zakat' => 'zakat',
        'Hari_Kerja' => 'work_days', 'Skema_Upah' => 'daily_scheme', 'Jenis_Final' => 'final_kind', 'Masa_Terakhir' => 'is_final_period',
        'Saldo_Awal_Bruto' => 'opening_gross', 'Saldo_Awal_Pajak' => 'opening_tax', 'Saldo_Awal_Iuran' => 'opening_pension', 'Saldo_Awal_Zakat' => 'opening_zakat', 'Alasan_Saldo_Awal' => 'opening_reason',
        'Kode_Objek_Pajak' => 'tax_object_code', 'Nomor_Dokumen' => 'document_number', 'Tanggal_Dokumen' => 'document_date', 'NITKU_Penerima' => 'recipient_nitku', 'Tahun_Awal_Final' => 'final_start_year', 'Saldo_Awal_Hanya_Gaji' => 'opening_salary_only',
    ];

    public function __construct(private AccessService $access, private TaxService $tax, private PayrollService $payroll) {}

    /** @param array<int, array<int, string>> $rows */
    public function preview(User $user, Company $company, int $branchId, string $module, array $rows): ImportBatch
    {
        Gate::forUser($user)->authorize('payroll.import');
        Gate::forUser($user)->authorize('payroll.edit');
        $this->access->company($user, $company->id);
        $company->branches()->findOrFail($branchId);
        $map = [];
        foreach (self::HEADERS as $header => $field) {
            $map[strtolower($header)] = $field;
            $map[$field] = $field;
        }
        $headers = array_shift($rows) ?? [];
        $fields = array_map(fn ($header) => $map[strtolower(trim($header))] ?? null, $headers);
        if (count(array_filter($fields)) !== count(array_unique(array_filter($fields)))) {
            throw ValidationException::withMessages(['file' => 'Header impor berulang. Gunakan template yang disediakan.']);
        }
        foreach (['month', 'year', 'nik', 'name', 'position', 'ptkp', 'method', 'salary', 'joined_at'] as $required) {
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
            $records[] = $validated;
        }
        if (! $rows) {
            throw ValidationException::withMessages(['file' => 'File tidak berisi baris data.']);
        }

        return ImportBatch::create(['company_id' => $company->id, 'branch_id' => $branchId, 'user_id' => $user->id, 'module' => $module, 'records' => $records, 'errors' => $errors, 'row_count' => count($rows), 'status' => 'Preview']);
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
            $this->access->audit($actor, $company->id, 'import.completed', 'import:'.$batch->id, ['rows' => $batch->row_count]);
        });
    }

    /** @return array<int, array<int, string|int>> */
    public function template(string $module, int $year, int $month): array
    {
        $sample = ['month' => $month, 'year' => $year, 'nik' => '0000000000000001', 'name' => 'Contoh Pegawai', 'position' => 'Staff', 'ptkp' => 'TK/0', 'method' => 'GROSS', 'joined_at' => $year.'-01-01', 'salary' => 10000000, 'tax_object_code' => match ($module) {
            'pension' => '21-100-02','daily' => '21-100-30','severance' => '21-401-01','contractor' => '21-100-07',default => '21-100-01'
        }, 'work_days' => 1, 'daily_scheme' => 'DAILY', 'final_kind' => 'SEVERANCE', 'is_final_period' => 0];

        return [array_keys(self::HEADERS), array_map(fn ($field) => $sample[$field] ?? '', array_values(self::HEADERS))];
    }
}
