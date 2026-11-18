<?php

namespace Database\Factories;

use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditEvent> */
class AuditEventFactory extends Factory
{
    public function definition(): array
    {
        return ['company_id' => Company::factory(), 'user_id' => User::factory(), 'action' => 'fixture.created', 'subject' => 'fixture', 'details' => ['source' => 'factory']];
    }
}
