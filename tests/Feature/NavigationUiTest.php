<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('authentication pages stay dark and workspace stays light without saved theme preferences', function (): void {
    $this->get(route('setup'))->assertOk()
        ->assertSee('<html lang="id" class="dark" data-theme="dark">', false)
        ->assertDontSee('taxcore-theme');

    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);

    $this->get(route('login'))->assertOk()
        ->assertSee('<html lang="id" class="dark" data-theme="dark">', false)
        ->assertDontSee('taxcore-theme');
    $this->actingAs($admin)->get(route('companies.index'))->assertOk()
        ->assertSee('<html lang="id" class="h-full antialiased">', false)
        ->assertDontSee('<html lang="id" class="dark"', false)
        ->assertDontSee('taxcore-theme');
});

test('selected company keeps workspace menus enabled on access and reference pages', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    Branch::factory()->for($company)->create();
    foreach (['access.index', 'tax.reference'] as $route) {
        $this->actingAs($admin)->withSession(['active_company_id' => $company->id])->get(route($route))
            ->assertOk()->assertSee('data-shadcn="workspace"', false)
            ->assertSee(route('payroll.index', $company), false)
            ->assertSee(route('simulator.index', $company), false)
            ->assertSee('Pegawai Tetap Bulanan')->assertSee('Modul Pajak')
            ->assertSee('"isManagingCompanies":'.($route === 'companies.index' ? 'true' : 'false'))
            ->assertSessionHas('active_company_id', $company->id);
    }
});

test('revoked company selection never exposes companies outside membership', function (): void {
    $staff = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $allowed = Company::factory()->create(['name' => 'Perusahaan diizinkan']);
    $branch = Branch::factory()->for($allowed)->create();
    $foreign = Company::factory()->create(['name' => 'Perusahaan rahasia']);
    $staff->companies()->attach($allowed);
    $this->actingAs($staff)->withSession(['active_company_id' => $foreign->id])->get(route('tax.reference'))
        ->assertOk()->assertSee('"companyId":null')
        ->assertSee('aria-label="Daftar Gaji &amp; PPh 21" aria-disabled="true" disabled', false)
        ->assertDontSee('href="'.route('payroll.index', $allowed).'"', false)
        ->assertDontSee(route('payroll.index', $foreign), false)->assertDontSee($foreign->name);
    $this->get(route('dashboard'))->assertOk()
        ->assertViewHas('bootstrap', fn (array $state): bool => count($state['companies']) === 1 && $state['companies'][0]['companyId'] === $allowed->id && $state['activeCompanyId'] === 'branch_'.$branch->id)
        ->assertDontSee($foreign->name);
});

test('workspace escapes account and company text in bootstrap data', function (): void {
    $staff = User::factory()->create(['role' => 'PAYROLL_STAFF', 'name' => '<script>alert(1)</script>']);
    $company = Company::factory()->create(['name' => 'Company "quoted" <img src=x onerror=alert(1)>']);
    $branch = Branch::factory()->for($company)->create();
    $staff->companies()->attach($company);
    $this->actingAs($staff)->get(route('companies.index'))->assertOk()
        ->assertViewHas('bootstrap', fn (array $state): bool => $state['activeCompanyId'] === 'branch_'.$branch->id && $state['user']['name'] === $staff->name && $state['companies'][0]['name'] === $company->name)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<img src=x onerror=alert(1)>', false);
});

test('a user without assigned companies can still reach company selection and logout', function (): void {
    $user = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $this->actingAs($user)->get(route('companies.index'))->assertOk()
        ->assertSee('id="workspace_company"', false)
        ->assertSee('id="taxcore-bootstrap"', false)
        ->assertViewHas('bootstrap', fn (array $state): bool => $state['companies'] === [] && $state['employees'] === [] && $state['activeCompanyId'] === null && $state['urls']['logout'] === route('logout'));
});

test('company management preserves the active pemotong across workspace pages', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();

    $this->actingAs($admin)->withSession(['active_company_id' => $company->id])->get(route('companies.index'))
        ->assertOk()->assertSessionHas('active_company_id', $company->id)
        ->assertViewHas('bootstrap', fn (array $state): bool => $state['activeCompanyId'] === 'branch_'.$branch->id);
    $this->get(route('tax.reference'))->assertOk()->assertSessionHas('active_company_id', $company->id);
    $this->get(route('dashboard'))->assertOk()
        ->assertViewHas('bootstrap', fn (array $state): bool => $state['activeCompanyId'] === 'branch_'.$branch->id);
    $this->get(route('payroll.index', $company))->assertOk()->assertSessionHas('active_company_id', $company->id)
        ->assertSee('id="workspace_payroll"', false);
    $this->get(route('dashboard'))->assertOk()
        ->assertViewHas('bootstrap', fn (array $state): bool => $state['activeCompanyId'] === 'branch_'.$branch->id);
});

test('dashboard opens the reference workspace with an available pemotong', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();

    $this->actingAs($admin)->get(route('dashboard'))->assertOk()
        ->assertSee('id="workspace_payroll"', false)
        ->assertViewHas('bootstrap', fn (array $state): bool => $state['activeCompanyId'] === 'branch_'.$branch->id);
});
