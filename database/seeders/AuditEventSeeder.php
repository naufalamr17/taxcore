<?php

namespace Database\Seeders;

use App\Models\AuditEvent;
use Illuminate\Database\Seeder;

class AuditEventSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        AuditEvent::factory()->create();
    }
}
