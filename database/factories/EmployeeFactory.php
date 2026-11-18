<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Employee> */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return ['company_id' => Company::factory(), 'name' => fake()->name(), 'nik' => fake()->unique()->numerify('################'), 'position' => 'Staff', 'ptkp' => 'TK/0', 'joined_at' => '2026-01-01', 'is_active' => true];
    }
}
