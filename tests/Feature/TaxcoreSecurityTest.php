<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);
test('guests cannot read payroll or access management', function (): void {
    $company = Company::factory()->create();
    $this->get(route('payroll.index', $company))->assertRedirect(route('login'));
    $this->get(route('access.index'))->assertRedirect(route('login'));
});
test('initial setup creates one hashed admin and closes registration', function (): void {
    $this->get(route('setup'))->assertOk();
    $this->post(route('setup.store'), ['name' => 'First Admin', 'username' => 'firstadmin', 'email' => 'admin@example.test', 'password' => 'A-long-private-password-123', 'password_confirmation' => 'A-long-private-password-123'])->assertRedirect(route('companies.index'));
    $user = User::firstOrFail();
    expect($user->role)->toBe('SUPER_ADMIN');
    expect(Hash::check('A-long-private-password-123', $user->password))->toBeTrue();
    expect($user->toArray())->not->toHaveKey('password');
    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->get(route('setup'))->assertNotFound();
});
test('login accepts username and rejects inactive accounts', function (): void {
    $user = User::factory()->create(['username' => 'payrolluser', 'password' => 'Valid-password-123']);
    $this->post(route('login.store'), ['username' => 'payrolluser', 'password' => 'incorrect'])->assertSessionHasErrors('username');
    $this->post(route('login.store'), ['username' => 'payrolluser', 'password' => 'Valid-password-123'])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
    $user->update(['is_active' => false]);
    $this->get(route('companies.index'))->assertRedirect(route('login'));
    $this->assertGuest();
    $this->post(route('login.store'), ['username' => 'payrolluser', 'password' => 'Valid-password-123'])->assertSessionHasErrors('username');
});
test('login limits repeated failed attempts', function (): void {
    User::factory()->create();
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login.store'), ['username' => 'unknown', 'password' => 'invalid'])->assertSessionHasErrors('username');
    }
    $this->post(route('login.store'), ['username' => 'unknown', 'password' => 'invalid'])->assertTooManyRequests();
});
test('roles enforce application abilities', function (string $role, array $allowed): void {
    $user = User::factory()->create(['role' => $role]);
    foreach (config('taxcore.permissions') as $permission) {
        expect(Gate::forUser($user)->allows($permission))->toBe(in_array($permission, $allowed, true));
    }
})->with([
    ['AUDITOR', ['payroll.view', 'payroll.export']],
    ['PAYROLL_STAFF', ['payroll.view', 'payroll.edit', 'payroll.import']],
    ['TAX_MANAGER', ['payroll.view', 'payroll.edit', 'payroll.review', 'payroll.finalize', 'payroll.correct', 'payroll.import', 'payroll.export']],
]);
test('auditors cannot submit payroll or manage users', function (): void {
    $user = User::factory()->create(['role' => 'AUDITOR']);
    $company = Company::factory()->create();
    $user->companies()->attach($company);
    $this->actingAs($user)->post(route('payroll.store', $company), [])->assertForbidden();
    $this->get(route('access.index'))->assertForbidden();
    expect(PayrollEntry::count())->toBe(0);
});
test('company membership prevents read search and export leaks', function (): void {
    $user = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $own = Company::factory()->create();
    $other = Company::factory()->create();
    $user->companies()->attach($own);
    $branch = Branch::factory()->for($other)->create();
    $this->actingAs($user)->get(route('payroll.index', $other))->assertNotFound();
    $this->get(route('employees.lookup', ['company' => $other, 'q' => 'secret']))->assertNotFound();
    $this->get(route('audit.index', $other))->assertNotFound();
    $this->post(route('employees.store', $other), ['name' => 'Injected', 'nik' => '0000000000000001', 'position' => 'Staff', 'ptkp' => 'TK/0', 'joined_at' => '2026-01-01', 'is_active' => 1])->assertNotFound();
    expect(Employee::count())->toBe(0);
});
test('nested resource ids cannot escape the selected company', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $own = Company::factory()->create();
    $otherEntry = PayrollEntry::factory()->create();
    $this->actingAs($admin)->get(route('payroll.show', [$own, $otherEntry]))->assertNotFound();
    $this->delete(route('payroll.destroy', [$own, $otherEntry]))->assertNotFound();
    $this->assertModelExists($otherEntry);
});
test('role permission changes apply and the final admin stays active', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $staff = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    expect(Gate::forUser($staff)->allows('payroll.export'))->toBeFalse();
    $this->actingAs($admin)->put(route('access.permissions'), ['permissions' => ['PAYROLL_STAFF' => ['payroll.view' => 1, 'payroll.export' => 1]]])->assertRedirect();
    expect(Gate::forUser($staff)->allows('payroll.export'))->toBeTrue();
    $this->put(route('access.update', $admin), ['name' => $admin->name, 'username' => $admin->username, 'email' => $admin->email, 'role' => 'AUDITOR', 'is_active' => 0])->assertSessionHasErrors('role');
    expect($admin->fresh()->role)->toBe('SUPER_ADMIN');
    expect($admin->fresh()->is_active)->toBeTrue();
});

test('stale account models cannot demote the final active administrator', function (): void {
    $originalAdmin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $target = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $staleTarget = $target->fresh();
    $target->update(['role' => 'SUPER_ADMIN']);
    $originalAdmin->update(['role' => 'AUDITOR']);
    $actor = $target->fresh();
    $data = $target->only(['name', 'username', 'email']) + ['role' => 'AUDITOR', 'is_active' => true];

    expect(fn () => app(UserService::class)->save($actor, $data, $staleTarget))
        ->toThrow(ValidationException::class, 'Super Admin aktif terakhir tidak dapat dinonaktifkan atau diturunkan.');

    expect($target->fresh()->role)->toBe('SUPER_ADMIN');
    expect(User::where('role', 'SUPER_ADMIN')->where('is_active', true)->count())->toBe(1);
});
test('forged company ownership and calculated totals are not mass assigned', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $input = ['branch_id' => $branch->id, 'employee_id' => $employee->id, 'module' => 'monthly', 'year' => 2026, 'month' => 11, 'ptkp' => 'TK/0', 'method' => 'GROSS', 'salary' => 10000000, 'tax_object_code' => '21-100-01', 'withholding_date' => '2026-11-30', 'company_id' => $other->id, 'tax' => 0, 'take_home' => 999999999, 'status' => 'Finalized'];

    $this->actingAs($admin)->post(route('payroll.store', $company), $input)->assertRedirect();
    $entry = PayrollEntry::firstOrFail();
    expect($entry->tax)->toBe(200000);
    expect($entry->payrollRun->company_id)->toBe($company->id);
    expect($entry->payrollRun->status)->toBe('Draft');
});
