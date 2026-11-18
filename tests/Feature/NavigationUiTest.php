<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('selected company keeps workspace menus enabled on access and reference pages', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    Branch::factory()->for($company)->create();
    foreach (['access.index', 'tax.reference'] as $route) {
        $this->actingAs($admin)->withSession(['active_company_id' => $company->id])->get(route($route))
            ->assertOk()->assertSee('data-shadcn="workspace"', false)
            ->assertSee(route('payroll.index', $company), false)
            ->assertSee(route('employees.index', $company), false)
            ->assertSee(route('simulator.index', $company), false)
            ->assertSee('Pegawai Tetap Bulanan')->assertSee('Modul Pajak')
            ->assertSee('"isManagingCompanies":'.($route === 'companies.index' ? 'true' : 'false'))
            ->assertSessionHas('active_company_id', $company->id);
    }
});

test('revoked company selection freezes navigation without selecting another company', function (): void {
    $staff = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $allowed = Company::factory()->create(['name' => 'Perusahaan diizinkan']);
    $foreign = Company::factory()->create(['name' => 'Perusahaan rahasia']);
    $staff->companies()->attach($allowed);
    $this->actingAs($staff)->withSession(['active_company_id' => $foreign->id])->get(route('tax.reference'))
        ->assertOk()->assertSee('"companyId":null')
        ->assertSee('aria-label="Daftar Gaji &amp; PPh 21" aria-disabled="true" disabled', false)
        ->assertDontSee('href="'.route('payroll.index', $allowed).'"', false)
        ->assertDontSee(route('payroll.index', $foreign), false)->assertDontSee($foreign->name);
    $this->get(route('dashboard'))->assertRedirect(route('companies.index'));
});

test('navigation freezes without a company selection and escapes UI props', function (): void {
    $staff = User::factory()->create(['role' => 'PAYROLL_STAFF', 'name' => '<script>alert(1)</script>']);
    $company = Company::factory()->create(['name' => 'Company "quoted" <img src=x onerror=alert(1)>']);
    $staff->companies()->attach($company);
    $this->actingAs($staff)->get(route('companies.index'))->assertOk()
        ->assertSee('"companyId":null')
        ->assertSee('aria-label="Daftar Gaji &amp; PPh 21" aria-disabled="true" disabled', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<img src=x onerror=alert(1)>', false);
});

test('a user without assigned companies can still reach company selection and logout', function (): void {
    $user = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $this->actingAs($user)->get(route('companies.index'))->assertOk()
        ->assertSee(route('companies.index'), false)->assertSee(route('logout'), false)
        ->assertSee('data-shadcn="workspace"', false);
});

test('company management clears the selection and choosing a workspace enables its menus again', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    Branch::factory()->for($company)->create();

    $this->actingAs($admin)->withSession(['active_company_id' => $company->id])->get(route('companies.index'))
        ->assertOk()->assertSessionMissing('active_company_id')
        ->assertSee('"companyId":null')
        ->assertSee('aria-label="Daftar Gaji &amp; PPh 21" aria-disabled="true" disabled', false)
        ->assertSee('aria-label="Log Audit" aria-disabled="true" disabled', false)
        ->assertSee('href="'.route('companies.index').'"', false);
    $this->get(route('tax.reference'))->assertOk()->assertSee('"companyId":null');
    $this->get(route('dashboard'))->assertRedirect(route('companies.index'));
    $this->get(route('payroll.index', $company))->assertOk()->assertSessionHas('active_company_id', $company->id)
        ->assertSee('href="'.route('employees.index', $company).'"', false)
        ->assertDontSee('aria-disabled="true" disabled', false);
    $this->get(route('dashboard'))->assertRedirect(route('payroll.index', $company));
});

test('dashboard requires an explicit company selection even when a company is available', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    Company::factory()->create();

    $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('companies.index'))
        ->assertSessionMissing('active_company_id');
});
