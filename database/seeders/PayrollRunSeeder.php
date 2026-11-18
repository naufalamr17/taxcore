<?php

namespace Database\Seeders;

use App\Models\PayrollRun;
use Illuminate\Database\Seeder;

class PayrollRunSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        PayrollRun::factory()->create();
    }
}
