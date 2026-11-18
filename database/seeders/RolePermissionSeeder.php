<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('taxcore.defaults') as $role => $permissions) {
            foreach (config('taxcore.permissions') as $permission) {
                RolePermission::firstOrCreate(['role' => $role, 'permission' => $permission], ['allowed' => in_array($permission, $permissions, true)]);
            }
        }
    }
}
