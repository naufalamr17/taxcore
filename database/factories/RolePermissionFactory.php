<?php

namespace Database\Factories;

use App\Models\RolePermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RolePermission> */
class RolePermissionFactory extends Factory
{
    public function definition(): array
    {
        return ['role' => 'PAYROLL_STAFF', 'permission' => 'payroll.edit', 'allowed' => true];
    }
}
