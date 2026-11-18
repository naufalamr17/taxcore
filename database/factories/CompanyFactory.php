<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->company(), 'npwp' => fake()->unique()->numerify('0###############'), 'city' => 'Jakarta', 'address' => 'Alamat contoh', 'signer_name' => 'Penandatangan Contoh', 'signer_npwp' => fake()->numerify('################')];
    }
}
