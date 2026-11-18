<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class AccessService
{
    /** @var array<string, bool> */
    private array $permissions = [];

    public function forgetPermissions(): void
    {
        $this->permissions = [];
    }

    public function allows(User $user, string $permission): bool
    {
        if (! $user->is_active || ! array_key_exists($user->role, config('taxcore.roles'))) {
            return false;
        }
        if ($user->role === 'SUPER_ADMIN') {
            return true;
        }
        $key = $user->role.':'.$permission;

        return $this->permissions[$key] ??= (RolePermission::where('role', $user->role)->where('permission', $permission)->value('allowed') ?? in_array($permission, config('taxcore.defaults.'.$user->role, []), true));
    }

    public function companies(User $user): Builder
    {
        return Company::query()->when($user->role !== 'SUPER_ADMIN', fn (Builder $query) => $query->whereIn('id', $user->companies()->select('companies.id')));
    }

    public function company(User $user, int $id): Company
    {
        Gate::forUser($user)->authorize('payroll.view');

        return $this->companies($user)->findOrFail($id);
    }

    /** @param array<string, mixed> $details */
    public function audit(User $user, ?int $companyId, string $action, string $subject, array $details = []): void
    {
        AuditEvent::create(['user_id' => $user->id, 'company_id' => $companyId, 'action' => $action, 'subject' => $subject, 'details' => $details]);
    }
}
