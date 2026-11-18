<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Services\TaxService;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollEntry> */
class PayrollEntryFactory extends Factory
{
    public function definition(): array
    {
        return ['payroll_run_id' => PayrollRun::factory(), 'employee_id' => fn (array $attributes) => Employee::factory()->create(['company_id' => PayrollRun::findOrFail($attributes['payroll_run_id'])->company_id])->id, 'input' => function (array $attributes): array {
            $run = PayrollRun::findOrFail($attributes['payroll_run_id']);
            $employee = Employee::findOrFail($attributes['employee_id']);

            return ['module' => 'monthly', 'year' => $run->year, 'month' => $run->month, 'branch_id' => $run->branch_id, 'employee_id' => $employee->id, 'name' => $employee->name, 'nik' => $employee->nik, 'position' => $employee->position, 'joined_at' => '2026-01-01', 'ptkp' => 'TK/0', 'method' => 'GROSS', 'salary' => 10000000, 'tax_object_code' => '21-100-01', 'withholding_date' => '2026-11-30'];
        }, 'result' => fn (array $attributes) => app(TaxService::class)->calculate($attributes['input']), 'gross' => fn (array $attributes) => $attributes['result']['gross'], 'tax' => fn (array $attributes) => $attributes['result']['tax'], 'take_home' => fn (array $attributes) => $attributes['result']['take_home']];
    }
}
