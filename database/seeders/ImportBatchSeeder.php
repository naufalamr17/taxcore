<?php

namespace Database\Seeders;

use App\Models\ImportBatch;
use Illuminate\Database\Seeder;

class ImportBatchSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        ImportBatch::factory()->create();
    }
}
