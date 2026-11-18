<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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

    /** @param array<string, mixed> $data */
    public function addBranch(User $user, Company $company, array $data): Branch
    {
        Gate::forUser($user)->authorize('company.manage');
        $this->access->company($user, $company->id);

        return DB::transaction(function () use ($user, $company, $data): Branch {
            $branch = $company->branches()->create(['name' => $data['name'], 'code' => $data['code'], 'nitku' => $company->npwp.$data['code']]);
            $this->access->audit($user, $company->id, 'branch.created', 'branch:'.$branch->id);

            return $branch;
        });
    }
}
