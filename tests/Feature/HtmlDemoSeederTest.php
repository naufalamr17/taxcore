<?php

use App\Models\AuditEvent;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\HtmlWorkspaceService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\HtmlDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

test('default seeder provides the html company tku payroll and usable role accounts', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(Company::count())->toBe(1);
    expect(Branch::count())->toBe(2);
    expect(Employee::count())->toBe(6);
    expect(PayrollEntry::count())->toBe(8);
    expect(User::count())->toBe(4);
    $company = Company::firstOrFail();
    expect($company->name)->toBe('PT Nusantara Teknologi Mandiri');
    expect($company->npwp)->toBe('0127543973027000');
    $branches = $company->branches()->get()->keyBy('code');
    expect($branches['000000']->profile['signerName'])->toBe('Ir. Hendra Wijaya, M.B.A.');
    expect($branches['000001']->profile['signerName'])->toBe('Budi Santoso, S.E.');
    expect($branches['000001']->nitku)->toBe('0127543973027000000001');
    expect(PayrollRun::where('month', 11)->withCount('entries')->get()->sum('entries_count'))->toBe(6);
    expect(PayrollRun::where('month', 12)->withCount('entries')->get()->sum('entries_count'))->toBe(2);
    expect(PayrollEntry::whereHas('payrollRun', fn ($query) => $query->where('branch_id', $branches['000001']->id))->count())->toBe(0);
    foreach (['admin' => ['admin123', 'SUPER_ADMIN'], 'taxmanager' => ['pajak123', 'TAX_MANAGER'], 'payroll' => ['gaji1234', 'PAYROLL_STAFF'], 'auditor' => ['audit123', 'AUDITOR']] as $username => [$password, $role]) {
        $user = User::where('username', $username)->firstOrFail();
        expect($user->role)->toBe($role);
        expect($user->is_active)->toBeTrue();
        expect(Hash::check($password, $user->password))->toBeTrue();
        expect($user->companies()->pluck('companies.id')->all())->toBe([$company->id]);
    }

    $this->post(route('login.store'), ['username' => 'payroll', 'password' => 'gaji1234'])->assertRedirect(route('dashboard'));
    $state = $this->getJson(route('workspace.state'))->assertOk()->assertJsonCount(2, 'companies')->assertJsonCount(8, 'employees');
    $records = collect($state['employees'])->keyBy('id');
    expect($records['html_demo_emp_3'])->toMatchArray(['name' => 'Dimas Prakoso, S.Kom.', 'gajiPokok' => 28000000, 'thr' => 28000000, 'bpjs' => 631200, 'year' => 2026, 'month' => 11]);
    expect($records['html_demo_emp_8'])->toMatchArray(['subjectType' => 'pegawai_tahunan', 'month' => 12, 'gajiPokok' => 14500000, 'iuranJht' => 290000, 'syncedFromMonthly' => true]);
});

test('reseeding preserves edited passwords access profiles payroll and audit history', function (): void {
    $this->seed(DatabaseSeeder::class);
    $payroll = User::where('username', 'payroll')->firstOrFail();
    $payroll->update(['name' => 'Akun diperbarui', 'password' => 'changed-password', 'role' => 'AUDITOR', 'is_active' => false]);
    $passwordHash = $payroll->password;
    $permission = RolePermission::where('role', 'TAX_MANAGER')->where('permission', 'payroll.export')->firstOrFail();
    $permission->update(['allowed' => false]);
    $branch = Branch::where('code', '000000')->firstOrFail();
    $branch->update(['profile' => array_merge($branch->profile, ['signerName' => 'Penandatangan diperbarui'])]);
    $employee = Employee::where('nik', '3174051807890002')->firstOrFail();
    $employee->update(['name' => 'Nama diperbarui']);
    $entry = PayrollEntry::firstOrFail();
    $input = $entry->input;
    $input['html_record']['gajiPokok'] = 12345678;
    $entry->update(['input' => $input, 'version' => 7]);
    $auditCount = AuditEvent::count();

    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(4);
    expect(Company::count())->toBe(1);
    expect(Branch::count())->toBe(2);
    expect(Employee::count())->toBe(6);
    expect(PayrollEntry::count())->toBe(8);
    expect($payroll->fresh()->password)->toBe($passwordHash);
    expect($payroll->fresh()->name)->toBe('Akun diperbarui');
    expect($payroll->fresh()->role)->toBe('AUDITOR');
    expect($payroll->fresh()->is_active)->toBeFalse();
    expect($permission->fresh()->allowed)->toBeFalse();
    expect($branch->fresh()->profile['signerName'])->toBe('Penandatangan diperbarui');
    expect($employee->fresh()->name)->toBe('Nama diperbarui');
    expect($entry->fresh()->input['html_record']['gajiPokok'])->toBe(12345678);
    expect($entry->fresh()->version)->toBe(7);
    expect(AuditEvent::count())->toBe($auditCount);
});

test('demo seeding does not overwrite colliding existing accounts or employee records', function (): void {
    $user = User::factory()->create(['username' => 'payroll', 'name' => 'Existing Account', 'role' => 'AUDITOR', 'is_active' => false]);
    $company = Company::factory()->create(['npwp' => '0127543973027000', 'name' => 'Existing Company']);
    $branch = Branch::factory()->for($company)->create(['code' => '000000', 'name' => 'Existing Branch', 'profile' => ['signerName' => 'Existing Signer']]);
    $employee = Employee::factory()->for($company)->create(['nik' => '3174051807890002', 'name' => 'Existing Employee']);
    $run = PayrollRun::factory()->for($company)->for($branch)->create();
    $entry = PayrollEntry::factory()->for($run)->for($employee)->create();
    $originalInput = $entry->input;
    $originalPassword = $user->password;

    $this->seed(DatabaseSeeder::class);

    expect($user->fresh()->name)->toBe('Existing Account');
    expect($user->fresh()->password)->toBe($originalPassword);
    expect($user->fresh()->role)->toBe('AUDITOR');
    expect($user->companies()->count())->toBe(0);
    expect($company->fresh()->name)->toBe('Existing Company');
    expect($branch->fresh()->name)->toBe('Existing Branch');
    expect($branch->fresh()->profile)->toBe(['signerName' => 'Existing Signer']);
    expect($employee->fresh()->name)->toBe('Existing Employee');
    expect($entry->fresh()->input)->toBe($originalInput);
    expect(Employee::count())->toBe(6);
    expect(PayrollEntry::count())->toBe(7);
});

test('demo seeder does not create accounts or payroll in production', function (): void {
    expect(User::count())->toBe(0);
    $this->app->instance('env', 'production');
    try {
        app(HtmlDemoSeeder::class)->run(app(HtmlWorkspaceService::class));
    } finally {
        $this->app->instance('env', 'testing');
    }

    expect(User::count())->toBe(0);
    expect(Company::count())->toBe(0);
    expect(PayrollEntry::count())->toBe(0);
});

test('demo seeding rolls back instead of reactivating an existing inactive admin', function (): void {
    $user = User::factory()->create(['username' => 'admin', 'role' => 'SUPER_ADMIN', 'is_active' => false]);

    expect(fn () => app(HtmlDemoSeeder::class)->run(app(HtmlWorkspaceService::class)))->toThrow(RuntimeException::class, 'An active Super Admin is required');

    expect(User::count())->toBe(1);
    expect($user->fresh()->is_active)->toBeFalse();
    expect(Company::count())->toBe(0);
    expect(PayrollEntry::count())->toBe(0);
});
