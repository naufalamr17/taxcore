<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('payroll dialogs use compact authorized pages and same origin framing', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    Branch::factory()->for($company)->create();

    $this->actingAs($admin)->get(route('payroll.create', ['company' => $company, '_dialog' => 1]))
        ->assertOk()->assertSee('data-workspace-frame', false)
        ->assertDontSee('data-shadcn="workspace"', false)
        ->assertSee('name="new_employee"', false)
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $this->get(route('payroll.index', $company))->assertOk()
        ->assertSee('data-workspace-dialog', false)->assertSee('data-shadcn="workspace-dialog"', false);
});

test('saving a new recipient in a dialog retains compact result and server calculation', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();

    $response = $this->actingAs($admin)->post(route('payroll.store', $company), [
        '_dialog' => 1, 'new_employee' => 1, 'name' => 'Pegawai Modal', 'nik' => '0000000000000123', 'position' => 'Staff',
        'joined_at' => '2026-01-01', 'branch_id' => $branch->id, 'module' => 'monthly', 'year' => 2026, 'month' => 11,
        'ptkp' => 'TK/0', 'method' => 'GROSS', 'salary' => 10000000, 'tax_object_code' => '21-100-01', 'withholding_date' => '2026-11-30',
    ]);

    $entry = PayrollEntry::firstOrFail();
    $response->assertRedirect(route('payroll.show', ['company' => $company, 'entry' => $entry, '_dialog' => 1]));
    expect($entry->tax)->toBe(200000);
    $this->get($response->headers->get('Location'))->assertOk()
        ->assertSee('data-workspace-frame', false)->assertSee('data-dialog-updated', false)->assertSee('Pegawai Modal');
});

test('dialog validation preserves old input and does not save invalid payroll', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    Branch::factory()->for($company)->create();
    $origin = route('payroll.create', ['company' => $company, '_dialog' => 1]);

    $this->actingAs($admin)->from($origin)->followingRedirects()->post(route('payroll.store', $company), ['_dialog' => 1, 'name' => 'Belum lengkap'])
        ->assertOk()->assertSee('data-workspace-frame', false)->assertSee('role="alert"', false)->assertSee('Belum lengkap');
    $this->assertDatabaseCount('payroll_entries', 0);
});

test('recipient database lists records without independent write operations', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $employee = Employee::factory()->for($company)->create();

    $this->actingAs($admin)->get(route('employees.index', ['company' => $company, 'edit' => $employee->id]))
        ->assertOk()->assertSee($employee->name)->assertDontSee('name="new_employee"', false)
        ->assertDontSee('name="name"', false)->assertDontSee('Simpan penerima');
    $this->post(route('employees.store', $company), [])->assertStatus(405);
    $this->put(route('employees.update', [$company, $employee]), ['name' => 'Ditolak'])->assertStatus(405);
    expect($employee->fresh()->name)->toBe($employee->name);
    $this->assertDatabaseCount('employees', 1);
});

test('company users cannot read admin audit pages or see the audit menu', function (string $role): void {
    $user = User::factory()->create(['role' => $role]);
    $company = Company::factory()->create();
    $user->companies()->attach($company);
    Branch::factory()->for($company)->create();

    $this->actingAs($user)->get(route('audit.index', $company))->assertForbidden();
    $this->get(route('payroll.index', $company))->assertOk()->assertDontSee('Log Audit');
})->with(['PAYROLL_STAFF', 'TAX_MANAGER', 'AUDITOR']);

test('dialog presentation never bypasses company boundaries', function (): void {
    $user = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $company = Company::factory()->create();
    Branch::factory()->for($company)->create();

    $this->actingAs($user)->get(route('payroll.create', ['company' => $company, '_dialog' => 1]))->assertNotFound();
    $this->get(route('imports.index', ['company' => $company, '_dialog' => 1]))->assertNotFound();
    $user->companies()->attach($company);
    $foreign = Employee::factory()->create();
    $this->put(route('employees.update', [$company, $foreign]), ['name' => 'Ditolak'])->assertNotFound();
});

test('delegated account management does not grant access to administrator audit records', function (): void {
    $user = User::factory()->create(['role' => 'TAX_MANAGER']);
    RolePermission::factory()->create(['role' => 'TAX_MANAGER', 'permission' => 'access.manage', 'allowed' => true]);

    $this->actingAs($user)->get(route('access.index'))->assertOk()
        ->assertDontSee('Audit akun')->assertViewHas('events', fn ($events): bool => $events->isEmpty());
});

test('payroll footer totals include every matching row beyond the current page', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $run = PayrollRun::factory()->for($company)->for($branch)->create();
    PayrollEntry::factory()->count(26)->for($run)->create();

    $this->actingAs($admin)->get(route('payroll.index', ['company' => $company, 'branch_id' => $branch->id, 'module' => 'monthly', 'year' => 2026, 'month' => 11, 'per_page' => 25]))
        ->assertOk()->assertViewHas('entries', fn ($entries): bool => $entries->count() === 25)
        ->assertViewHas('tableTotals', fn (array $totals): bool => $totals['salary'] === 260000000 && $totals['tax'] === 5200000)
        ->assertSee('Tarif / DPP')->assertSee('TOTAL 26 PENERIMA');
});
