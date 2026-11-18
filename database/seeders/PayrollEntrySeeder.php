<?php

namespace Database\Seeders;

use App\Models\PayrollEntry;
use Illuminate\Database\Seeder;

class PayrollEntrySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        PayrollEntry::factory()->create();
    }
}
