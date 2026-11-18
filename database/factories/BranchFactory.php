<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Branch> */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return ['company_id' => Company::factory(), 'name' => 'Kantor Pusat', 'code' => fake()->unique()->numerify('######'), 'nitku' => fn (array $attributes) => Company::findOrFail($attributes['company_id'])->npwp.$attributes['code']];
    }
}
