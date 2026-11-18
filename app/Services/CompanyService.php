<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CompanyService
{
    public function __construct(private AccessService $access) {}

    /** @param array<string, mixed> $data */
    public function save(User $user, array $data, ?Company $company = null): Company
    {
        Gate::forUser($user)->authorize('company.manage');

        return DB::transaction(function () use ($user, $data, $company): Company {
            if ($company) {
                $this->access->company($user, $company->id);
            }
            $company ??= new Company;
            $company->fill(collect($data)->only(['name', 'npwp', 'address', 'city', 'signer_name', 'signer_npwp'])->all())->save();
            foreach ($company->branches()->get() as $branch) {
                $branch->update(['nitku' => $company->npwp.$branch->code]);
            }
            if (! $company->branches()->exists()) {
                $company->branches()->create(['name' => 'Kantor Pusat', 'code' => '000000', 'nitku' => $company->npwp.'000000']);
            }
            $user->companies()->syncWithoutDetaching([$company->id]);
            $this->access->audit($user, $company->id, 'company.saved', 'company:'.$company->id);

            return $company;
        });
    }

    public function remove(User $user, Company $company): void
    {
        Gate::forUser($user)->authorize('company.manage');
        $this->access->company($user, $company->id);
        DB::transaction(function () use ($user, $company): void {
            Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            if ($company->payrollRuns()->exists() || $company->employees()->exists() || ImportBatch::where('company_id', $company->id)->exists()) {
                throw ValidationException::withMessages(['company' => 'Perusahaan dengan riwayat payroll, penerima, atau impor tidak dapat dihapus.']);
            }
            $this->access->audit($user, null, 'company.deleted', 'company:'.$company->id, $company->only(['name', 'npwp', 'city']));
            AuditEvent::where('company_id', $company->id)->update(['company_id' => null]);
            $company->branches()->delete();
            $company->delete();
        });
    }

    /** @param array<string, mixed> $data */
    public function saveBranch(User $user, Company $company, array $data, ?Branch $branch = null): Branch
    {
        Gate::forUser($user)->authorize('company.manage');
        $this->access->company($user, $company->id);

        return DB::transaction(function () use ($user, $company, $data, $branch): Branch {
            $company = Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            if ($branch) {
                abort_unless($branch->company_id === $company->id, 404);
            }
            $branch ??= new Branch(['company_id' => $company->id]);
            $branch->fill(['name' => $data['name'], 'code' => $data['code'], 'nitku' => $company->npwp.$data['code']])->save();
            $this->access->audit($user, $company->id, 'branch.saved', 'branch:'.$branch->id);

            return $branch;
        });
    }
}
