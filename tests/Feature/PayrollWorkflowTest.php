<?php

use App\Models\AuditEvent;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);
function payrollInput(Branch $branch, Employee $employee, array $changes = []): array
{
    return array_merge(['branch_id' => $branch->id, 'employee_id' => $employee->id, 'module' => 'monthly', 'year' => 2026, 'month' => 11, 'ptkp' => 'TK/0', 'method' => 'GROSS', 'salary' => 10000000, 'tax_object_code' => '21-100-01', 'withholding_date' => '2026-11-30'], $changes);
}
test('payroll draft review finalize correction and snapshot lifecycle', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $payroll = app(PayrollService::class);

    $this->actingAs($admin)->post(route('payroll.store', $company), payrollInput($branch, $employee))->assertRedirect();
    $entry = PayrollEntry::firstOrFail();
    $run = $entry->payrollRun;
    expect($entry->tax)->toBe(200000);
    $this->get(route('payroll.show', [$company, $entry]))->assertOk()->assertSee($employee->name);
    $this->get(route('payroll.index', ['company' => $company, 'year' => 2026, 'month' => 11, 'branch_id' => $branch->id]))->assertOk()->assertSee('10.000.000');
    $this->post(route('payroll.transition', [$company, $run]), ['action' => 'review'])->assertRedirect();
    $this->post(route('payroll.transition', [$company, $run]), ['action' => 'finalize'])->assertRedirect();
    expect($run->fresh()->status)->toBe('Finalized');
    expect(fn () => $payroll->saveEntry($admin, $company, payrollInput($branch, $employee, ['version' => $entry->version]), $entry))->toThrow(ValidationException::class);
    $oldName = $company->name;
    $company->update(['name' => 'Perusahaan berubah']);
    $this->get(route('payroll.show', [$company, $entry]))->assertOk()->assertSee($oldName);
    $this->post(route('payroll.transition', [$company, $run]), ['action' => 'correct', 'reason' => 'Koreksi komponen gaji'])->assertRedirect();
    expect($run->fresh()->status)->toBe('Draft');
    expect(AuditEvent::where('action', 'payroll.revision.archived')->firstOrFail()->details['entries'][0]['result']['tax'])->toBe(200000);
    $this->put(route('payroll.update', [$company, $entry]), payrollInput($branch, $employee, ['version' => $entry->version, 'salary' => 11000000]))->assertRedirect();
    expect($entry->fresh()->version)->toBe(2);
});
test('stale updates and duplicate employees do not overwrite payroll', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $entry = app(PayrollService::class)->saveEntry($user, $company, payrollInput($branch, $employee));

    $this->actingAs($user)->post(route('payroll.store', $company), payrollInput($branch, $employee))->assertSessionHasErrors('employee_id');
    $this->put(route('payroll.update', [$company, $entry]), payrollInput($branch, $employee, ['version' => 99, 'salary' => 20000000]))->assertSessionHasErrors('version');
    expect($entry->fresh()->gross)->toBe(10000000);
    expect(PayrollEntry::count())->toBe(1);
});
test('final reconciliation requires explicit opening balances when history is incomplete', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $input = payrollInput($branch, $employee, ['module' => 'annual', 'month' => 12, 'withholding_date' => '2026-12-31']);
    $payroll = app(PayrollService::class);

    expect(fn () => $payroll->saveEntry($user, $company, $input))->toThrow(ValidationException::class);
    expect(PayrollEntry::count())->toBe(0);
    $entry = $payroll->saveEntry($user, $company, $input + ['opening_gross' => 110000000, 'opening_tax' => 2200000, 'opening_reason' => 'Saldo migrasi dari payroll sebelumnya']);
    expect($entry->result['annual_gross'])->toBe(120000000);
    expect($entry->input['months_worked'])->toBe(12);
});
test('resignation uses actual finalized history from earlier months', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create(['left_at' => '2026-03-31']);
    $payroll = app(PayrollService::class);
    foreach ([1, 2] as $month) {
        $date = CarbonImmutable::create(2026, $month, 1)->endOfMonth()->toDateString();
        $previous = $payroll->saveEntry($user, $company, payrollInput($branch, $employee, ['month' => $month, 'withholding_date' => $date, 'method' => 'GROSS_UP']));
        $payroll->transition($user, $company, $previous->payrollRun, 'review');
        $payroll->transition($user, $company, $previous->payrollRun, 'finalize');
    }

    $entry = $payroll->saveEntry($user, $company, payrollInput($branch, $employee, ['module' => 'annual', 'month' => 3, 'withholding_date' => '2026-03-31']));
    expect($entry->input['prior_gross'])->toBe(20460358);
    expect($entry->input['prior_tax'])->toBe(460358);
    expect($entry->tax)->toBe(-460358);
    expect($entry->result['annual_gross'])->toBe(30460358);
    expect($entry->input['annual_components']['prior_tax_allowance'])->toBe(460358);
});
test('bulk changes affect only the selected run and reset review', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $payroll = app(PayrollService::class);
    $entry = $payroll->saveEntry($user, $company, payrollInput($branch, $employee));
    $other = $payroll->saveEntry($user, $company, payrollInput($branch, $employee, ['month' => 10, 'withholding_date' => '2026-10-31']));
    $payroll->transition($user, $company, $entry->payrollRun, 'review');

    $count = $payroll->bulk($user, $company, payrollInput($branch, $employee), 'thr');
    expect($count)->toBe(1);
    expect($entry->fresh()->input['thr'])->toBe(10000000);
    expect($entry->payrollRun->fresh()->status)->toBe('Draft');
    expect($other->fresh()->input['thr'] ?? 0)->toBe(0);
});
test('all workspace pages render with escaped names', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create(['name' => '<script>alert(1)</script>']);
    $branch = Branch::factory()->for($company)->create();
    Employee::factory()->for($company)->create();
    foreach (['companies.index' => [], 'access.index' => [], 'employees.index' => [$company], 'imports.index' => [$company], 'simulator.index' => [$company], 'tax.reference' => [], 'audit.index' => [$company], 'payroll.create' => [$company]] as $route => $parameters) {
        $this->actingAs($user)->get(route($route,$parameters))->assertOk()->assertDontSee('<script>alert(1)</script>',false);
    }
});
