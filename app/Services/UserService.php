<?php

namespace App\Services;

use App\Models\ImportBatch;
use App\Models\PayrollRun;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(private AccessService $access) {}

    /** @param array<string, mixed> $data */
    public function initialize(array $data): User
    {
        return Cache::lock('taxcore-first-admin', 15)->block(5, function () use ($data): User {
            abort_if(User::exists(), 404);

            return User::create(['name' => $data['name'], 'email' => $data['email'], 'username' => $data['username'], 'password' => $data['password'], 'role' => 'SUPER_ADMIN', 'is_active' => true]);
        });
    }

    /** @param array<string, mixed> $data */
    public function save(User $actor, array $data, ?User $user = null): User
    {
        Gate::forUser($actor)->authorize('access.manage');

        return DB::transaction(function () use ($actor, $data, $user): User {
            $admins = User::where('role', 'SUPER_ADMIN')->where('is_active', true)->lockForUpdate()->get();
            if ($user) {
                $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            }
            if ($user?->role === 'SUPER_ADMIN' && $user->is_active && ($data['role'] !== 'SUPER_ADMIN' || ! $data['is_active']) && $admins->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'Super Admin aktif terakhir tidak dapat dinonaktifkan atau diturunkan.']);
            }
            $user ??= new User;
            $attributes = collect($data)->only(['name', 'email', 'username', 'role', 'is_active'])->all();
            if (! empty($data['password'])) {
                $attributes['password'] = $data['password'];
            }
            $user->fill($attributes)->save();
            $user->companies()->sync($data['company_ids'] ?? []);
            $this->access->audit($actor, null, 'user.saved', 'user:'.$user->id, ['role' => $user->role, 'active' => $user->is_active, 'companies' => $data['company_ids'] ?? []]);

            return $user;
        });
    }

    public function remove(User $actor, User $user): void
    {
        Gate::forUser($actor)->authorize('access.manage');
        DB::transaction(function () use ($actor, $user): void {
            $admins = User::where('role', 'SUPER_ADMIN')->where('is_active', true)->lockForUpdate()->get();
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($actor->id === $user->id || ($user->role === 'SUPER_ADMIN' && $user->is_active && $admins->count() <= 1)) {
                throw ValidationException::withMessages(['user' => 'Akun sendiri dan Super Admin aktif terakhir tidak dapat dihapus.']);
            }
            if (ImportBatch::where('user_id', $user->id)->exists() || PayrollRun::where('reviewed_by', $user->id)->exists()) {
                throw ValidationException::withMessages(['user' => 'Akun memiliki riwayat impor atau review. Nonaktifkan akun untuk mempertahankan riwayat.']);
            }
            $this->access->audit($actor, null, 'user.deleted', 'user:'.$user->id, $user->only(['name', 'username', 'email', 'role']));
            $user->delete();
        });
    }

    /** @param array<string, array<string, bool|int|string>> $matrix */
    public function permissions(User $actor, array $matrix): void
    {
        Gate::forUser($actor)->authorize('access.manage');
        DB::transaction(function () use ($actor, $matrix): void {
            foreach (array_keys(config('taxcore.roles')) as $role) {
                if ($role === 'SUPER_ADMIN') {
                    continue;
                }
                foreach (config('taxcore.permissions') as $permission) {
                    RolePermission::updateOrCreate(['role' => $role, 'permission' => $permission], ['allowed' => (bool) ($matrix[$role][$permission] ?? false)]);
                }
            }
            $this->access->audit($actor, null, 'permissions.updated', 'roles');
            $this->access->forgetPermissions();
        });
    }
}
