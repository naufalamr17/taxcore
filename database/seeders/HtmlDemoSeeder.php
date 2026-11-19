<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\PayrollEntry;
use App\Models\User;
use App\Services\HtmlWorkspaceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HtmlDemoSeeder extends Seeder
{
    public function run(HtmlWorkspaceService $workspace): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $created = DB::transaction(function () use ($workspace): array {
            $newUsers = [];
            foreach ($this->users() as $data) {
                $user = User::firstOrCreate(['username' => $data['username']], [
                    'name' => $data['name'], 'email' => $data['username'].'@taxcore.test',
                    'password' => $data['password'], 'role' => $data['role'], 'is_active' => true,
                ]);
                if ($user->wasRecentlyCreated) {
                    $newUsers[] = $user;
                }
            }
            $actor = User::where('role', 'SUPER_ADMIN')->where('is_active', true)->first();
            if (! $actor) {
                throw new RuntimeException('An active Super Admin is required; existing accounts were preserved.');
            }

            $company = null;
            $headOffice = null;
            foreach ($this->companies() as $data) {
                $company = Company::firstOrCreate(['npwp' => $data['npwp']], [
                    'name' => $data['name'], 'city' => $data['city'], 'address' => $data['address'],
                    'signer_name' => $data['signerName'], 'signer_npwp' => $data['signerNpwp'],
                ]);
                $branch = Branch::firstOrCreate(['company_id' => $company->id, 'code' => $data['nitkuSuffix']], [
                    'name' => $data['branchName'], 'nitku' => $data['npwp'].$data['nitkuSuffix'],
                    'profile' => collect($data)->only(['name', 'city', 'address', 'signerName', 'signerNpwp'])->all(),
                ]);
                if ($data['nitkuSuffix'] === '000000') {
                    $headOffice = $branch;
                }
            }
            foreach ($newUsers as $user) {
                $user->companies()->syncWithoutDetaching([$company->id]);
            }

            $existingNiks = $company->employees()->pluck('nik');
            $existingIds = PayrollEntry::whereHas('payrollRun', fn ($query) => $query->where('branch_id', $headOffice->id))
                ->get(['id', 'input'])->map(fn (PayrollEntry $entry): string => $entry->input['html_record']['id'] ?? 'entry_'.$entry->id);
            $records = array_values(array_filter($this->payrollRecords(), fn (array $record): bool => ! $existingNiks->contains($record['nik']) && ! $existingIds->contains($record['id'])));
            if ($records) {
                $workspace->saveEmployees($actor, $company, $headOffice, ['upserts' => $records, 'deletes' => []]);
            }

            return ['users' => count($newUsers), 'payroll' => count($records)];
        });

        $this->command?->info("HTML demo: {$created['users']} new users, {$created['payroll']} new payroll rows. Existing records preserved.");
    }

    /** @return array<int, array<string, string>> */
    private function users(): array
    {
        return [
            ['username' => 'admin', 'name' => 'Super Administrator', 'password' => 'admin123', 'role' => 'SUPER_ADMIN'],
            ['username' => 'taxmanager', 'name' => 'Budi Santoso, S.E.', 'password' => 'pajak123', 'role' => 'TAX_MANAGER'],
            ['username' => 'payroll', 'name' => 'Siti Nurhaliza, S.Psi.', 'password' => 'gaji1234', 'role' => 'PAYROLL_STAFF'],
            ['username' => 'auditor', 'name' => 'Rina Wijaya, S.Ak.', 'password' => 'audit123', 'role' => 'AUDITOR'],
        ];
    }

    /** @return array<int, array<string, string>> */
    private function companies(): array
    {
        return [
            ['name' => 'PT Nusantara Teknologi Mandiri', 'branchName' => 'Kantor Pusat (000000)', 'npwp' => '0127543973027000', 'nitkuSuffix' => '000000', 'signerName' => 'Ir. Hendra Wijaya, M.B.A.', 'signerNpwp' => '3175021405800001', 'city' => 'Jakarta', 'address' => 'Jl. HR Rasuna Said, Jakarta Selatan'],
            ['name' => 'PT Nusantara Teknologi Mandiri', 'branchName' => 'Cabang Surabaya (000001)', 'npwp' => '0127543973027000', 'nitkuSuffix' => '000001', 'signerName' => 'Budi Santoso, S.E.', 'signerNpwp' => '3174051807890002', 'city' => 'Surabaya', 'address' => 'Jl. Basuki Rahmat No. 88, Surabaya'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function payrollRecords(): array
    {
        $defaults = ['month' => 11, 'year' => 2026, 'subjectType' => 'pegawai_bulanan', 'taxMethod' => 'GROSS', 'tunjangan' => 0, 'bonus' => 0, 'thr' => 0, 'kompensasi' => 0, 'bpjs' => 0, 'iuranPensiun' => 0, 'iuranJht' => 0, 'workMonths' => 12, 'hasNpwp' => true];

        return array_map(fn (array $record): array => $record + $defaults, [
            ['id' => 'html_demo_emp_1', 'name' => 'Budi Santoso, S.E.', 'nik' => '3174051807890002', 'position' => 'Finance & Tax Manager', 'ptkp' => 'K/1', 'gajiPokok' => 22000000, 'tunjangan' => 3500000, 'bpjs' => 598800],
            ['id' => 'html_demo_emp_2', 'name' => 'Rina Wijaya, S.Ak.', 'nik' => '3171041209920004', 'position' => 'Senior Tax Specialist', 'ptkp' => 'TK/0', 'taxMethod' => 'GROSS_UP', 'gajiPokok' => 14500000, 'tunjangan' => 2000000, 'bonus' => 3000000, 'kompensasi' => 500000, 'bpjs' => 558300],
            ['id' => 'html_demo_emp_3', 'name' => 'Dimas Prakoso, S.Kom.', 'nik' => '3275012204950008', 'position' => 'Lead Software Engineer', 'ptkp' => 'K/2', 'gajiPokok' => 28000000, 'tunjangan' => 4000000, 'bonus' => 5000000, 'thr' => 28000000, 'kompensasi' => 1200000, 'bpjs' => 631200],
            ['id' => 'html_demo_emp_4', 'name' => 'Siti Nurhaliza, S.Psi.', 'nik' => '3173085511960003', 'position' => 'HR Supervisor', 'ptkp' => 'TK/1', 'taxMethod' => 'NETT', 'gajiPokok' => 11000000, 'tunjangan' => 1500000, 'kompensasi' => 750000, 'bpjs' => 499400],
            ['id' => 'html_demo_emp_5', 'name' => 'Ir. Hendra Wijaya, M.B.A.', 'nik' => '3175021405800001', 'position' => 'Direktur Operasional', 'ptkp' => 'K/3', 'taxMethod' => 'GROSS_UP', 'gajiPokok' => 55000000, 'tunjangan' => 10000000, 'bonus' => 15000000, 'kompensasi' => 2500000, 'bpjs' => 777000],
            ['id' => 'html_demo_emp_6', 'name' => 'dr. Nadia Kusuma, Sp.PD', 'nik' => '3172021903840005', 'position' => 'Konsultan Medis', 'subjectType' => 'bukan_pegawai', 'ptkp' => 'K/1', 'gajiPokok' => 45000000],
            ['id' => 'html_demo_emp_7', 'month' => 12, 'name' => 'Budi Santoso, S.E.', 'nik' => '3174051807890002', 'position' => 'Finance & Tax Manager', 'subjectType' => 'pegawai_tahunan', 'ptkp' => 'K/1', 'gajiPokok' => 22000000, 'tunjangan' => 3500000, 'bonus' => 10000000, 'thr' => 22000000, 'bpjs' => 598800, 'iuranPensiun' => 100000, 'iuranJht' => 440000, 'syncedFromMonthly' => true],
            ['id' => 'html_demo_emp_8', 'month' => 12, 'name' => 'Rina Wijaya, S.Ak.', 'nik' => '3171041209920004', 'position' => 'Senior Tax Specialist', 'subjectType' => 'pegawai_tahunan', 'ptkp' => 'TK/0', 'taxMethod' => 'GROSS_UP', 'gajiPokok' => 14500000, 'tunjangan' => 2000000, 'bonus' => 5000000, 'thr' => 14500000, 'kompensasi' => 500000, 'bpjs' => 558300, 'iuranPensiun' => 100000, 'iuranJht' => 290000, 'syncedFromMonthly' => true],
        ]);
    }
}
