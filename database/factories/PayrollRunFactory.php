<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollRun> */
class PayrollRunFactory extends Factory
{
    public function definition(): array
    {
        return ['branch_id' => Branch::factory(), 'company_id' => fn (array $attributes) => Branch::findOrFail($attributes['branch_id'])->company_id, 'year' => 2026, 'month' => 11, 'module' => 'monthly', 'status' => 'Draft'];
    }
}
