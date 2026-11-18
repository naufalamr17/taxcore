<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ImportBatch> */
class ImportBatchFactory extends Factory
{
    public function definition(): array
    {
        return ['company_id' => Company::factory(), 'branch_id' => fn (array $attributes) => Branch::factory()->create(['company_id' => $attributes['company_id']])->id, 'user_id' => User::factory(), 'module' => 'monthly', 'status' => 'Preview', 'records' => [], 'errors' => [], 'row_count' => 0];
    }
}
