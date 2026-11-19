<?php

use App\Jobs\CommitPayrollImport;
use App\Models\AuditEvent;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\CoretaxExportService;
use App\Services\JournalService;
use App\Services\PayrollImportService;
use App\Services\PayrollService;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

function parityInput(Branch $branch, Employee $employee, array $changes = []): array
{
    return array_merge(['branch_id' => $branch->id, 'employee_id' => $employee->id, 'module' => 'monthly', 'year' => 2026, 'month' => 11, 'ptkp' => 'TK/0', 'method' => 'GROSS', 'salary' => 10000000, 'tax_object_code' => '21-100-01', 'withholding_date' => '2026-11-30'], $changes);
}

function parityRows(string $nik = '0000000000000001', int $salary = 10000000): array
{
    return [['Masa_Pajak', 'Tahun_Pajak', 'NIK', 'Nama', 'Jabatan', 'PTKP', 'Metode_Pajak', 'Tanggal_Masuk', 'Gaji_Pokok', 'Kode_Objek_Pajak'], ['11', '2026', $nik, 'Penerima impor', 'Staff', 'TK/0', 'GROSS', '2026-01-01', (string) $salary, '21-100-01']];
}

test('first administrator accepts eight characters and rejects seven', function (): void {
    $data = ['name' => 'Admin', 'username' => 'admin8', 'email' => 'admin8@example.test', 'password' => '1234567', 'password_confirmation' => '1234567'];
    $this->post(route('setup.store'), $data)->assertSessionHasErrors('password');
    expect(User::count())->toBe(0);
    $data['password'] = $data['password_confirmation'] = '12345678';
    $this->post(route('setup.store'), $data)->assertRedirect(route('companies.index'));
    expect(Hash::check('12345678', User::firstOrFail()->password))->toBeTrue();
});

test('account creation and password changes share the eight character boundary', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $data = ['name' => 'Staff', 'username' => 'staff8', 'email' => 'staff8@example.test', 'role' => 'PAYROLL_STAFF', 'is_active' => true, 'password' => '1234567'];
    $this->actingAs($admin)->post(route('access.store'), $data)->assertSessionHasErrors('password');
    $data['password'] = '12345678';
    $this->post(route('access.store'), $data)->assertRedirect();
    $staff = User::where('username', 'staff8')->firstOrFail();
    expect(Hash::check('12345678', $staff->password))->toBeTrue();
    $data['password'] = 'abcdefg';
    $this->put(route('access.update', $staff), $data)->assertSessionHasErrors('password');
    $data['password'] = 'abcdefgh';
    $this->put(route('access.update', $staff), $data)->assertRedirect();
    expect(Hash::check('abcdefgh', $staff->fresh()->password))->toBeTrue();
    $data['password'] = '';
    $this->put(route('access.update', $staff), $data)->assertRedirect();
    expect(Hash::check('abcdefgh', $staff->fresh()->password))->toBeTrue();
});

test('simulator loads scoped saved data and can save an updated calculation', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $this->actingAs($admin)->post(route('simulator.calculate', $company), parityInput($branch, $employee))->assertOk()->assertViewHas('result', fn ($result) => $result['tax'] === 200000);
    expect(PayrollEntry::count())->toBe(0);
    $this->post(route('payroll.store', $company), parityInput($branch, $employee, ['return_to' => 'simulator']))->assertRedirect();
    $entry = PayrollEntry::firstOrFail();
    $this->get(route('simulator.index', ['company' => $company, 'entry' => $entry->id]))->assertOk()
        ->assertSee('id="manualEmployeeModal"', false)->assertDontSee('Muat ke simulator')
        ->assertViewHas('bootstrap', fn (array $state): bool => $state['mode'] === 'simulator' && $state['employees'][0]['gajiPokok'] === 10000000);
    $this->put(route('payroll.update', [$company, $entry]), parityInput($branch, $employee, ['version' => 1, 'salary' => 11000000, 'return_to' => 'simulator']))->assertRedirect(route('simulator.index', ['company' => $company, 'entry' => $entry->id]));
    expect($entry->fresh()->tax)->toBe(330000);
    $this->get(route('simulator.index', ['company' => $company, 'entry' => $entry->id]))->assertOk()
        ->assertViewHas('bootstrap', fn (array $state): bool => $state['employees'][0]['_version'] === 2);
    $this->put(route('payroll.update', [$company, $entry]), parityInput($branch, $employee, ['version' => 2, 'salary' => 12000000, 'return_to' => 'simulator']))->assertRedirect();
    expect($entry->fresh()->version)->toBe(3);
    $outsider = Company::factory()->create();
    Branch::factory()->for($outsider)->create();
    $this->get(route('payroll.show', [$outsider, $entry]))->assertNotFound();
});

test('simulator provides saved records and reference filters across modules and months', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create(['name' => 'Dicari']);
    $payroll = app(PayrollService::class);
    $payroll->saveEntry($admin, $company, parityInput($branch, $employee));
    $payroll->saveEntry($admin, $company, parityInput($branch, $employee, ['module' => 'contractor', 'month' => 10, 'tax_object_code' => '21-100-07', 'withholding_date' => '2026-10-31']));
    $this->actingAs($admin)->get(route('simulator.index', ['company' => $company, 'branch_id' => $branch->id, 'year' => 2026, 'month' => 11]))->assertOk()
        ->assertSee('id="simSavedSearchInput"', false)->assertSee('id="simSavedShowAllModules"', false)
        ->assertViewHas('bootstrap', fn (array $state): bool => count($state['employees']) === 2 && array_sum(array_column($state['employees'], 'gajiPokok')) === 20000000);
    $this->get(route('simulator.index', ['company' => $company, 'year' => 2026, 'month' => 0]))->assertOk()
        ->assertViewHas('bootstrap', fn (array $state): bool => (int) $state['selection']['month'] === 0 && (int) $state['selection']['year'] === 2026);
});

test('simulator loads every saved row for client filtering with the selected branch active', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $run = PayrollRun::factory()->for($branch)->create(['company_id' => $company->id]);
    PayrollEntry::factory()->for($run)->count(26)->create();
    $otherBranch = Branch::factory()->for($company)->create();
    $otherRun = PayrollRun::factory()->for($otherBranch)->create(['company_id' => $company->id]);
    PayrollEntry::factory()->for($otherRun)->create();
    PayrollEntry::factory()->create();

    $this->actingAs($admin)->get(route('simulator.index', ['company' => $company, 'branch_id' => $branch->id, 'year' => 2026, 'month' => 11, 'per_page' => 25, 'page' => 2]))
        ->assertOk()->assertViewHas('bootstrap', function (array $state) use ($branch): bool {
            $records = collect($state['employees'])->where('companyId', 'branch_'.$branch->id);

            return $state['activeCompanyId'] === 'branch_'.$branch->id && $records->count() === 26 && $records->sum('gajiPokok') === 260000000;
        })
        ->assertSee('id="simSavedListTableBody"', false)->assertSee('id="simSpreadsheetToggleBtn"', false)
        ->assertDontSee('id="simulator-calculation"', false);
});

test('simulator keeps saved payroll read only for auditors', function (): void {
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $run = PayrollRun::factory()->for($branch)->create(['company_id' => $company->id]);
    PayrollEntry::factory()->for($run)->create();
    $auditor = User::factory()->create(['role' => 'AUDITOR']);
    $auditor->companies()->attach($company);

    $this->actingAs($auditor)->get(route('simulator.index', ['company' => $company, 'branch_id' => $branch->id, 'year' => 2026, 'month' => 11]))
        ->assertOk()->assertSee('Kertas Kerja')
        ->assertViewHas('bootstrap', fn (array $state): bool => count($state['employees']) === 1 && in_array('payroll.view', $state['permissions'], true) && ! in_array('payroll.edit', $state['permissions'], true));
    $this->postJson(route('workspace.employees', [$company, $branch]), ['upserts' => [], 'deletes' => []])->assertForbidden();
});

test('new payroll recipient is created together with the first payroll entry', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $data = ['new_employee' => 1, 'name' => 'Baru', 'nik' => '0000000000000012', 'position' => 'Staff', 'joined_at' => '2026-01-01', 'branch_id' => $branch->id, 'module' => 'monthly', 'year' => 2026, 'month' => 11, 'ptkp' => 'TK/0', 'method' => 'GROSS', 'salary' => 10000000, 'tax_object_code' => '21-100-01', 'withholding_date' => '2026-11-30'];
    $this->actingAs($admin)->post(route('payroll.store', $company), $data)->assertRedirect();
    expect(Employee::where('company_id', $company->id)->count())->toBe(1);
    expect(PayrollEntry::firstOrFail()->input['name'])->toBe('Baru');
    $this->get(route('simulator.index', ['company' => $company, 'entry' => PayrollEntry::firstOrFail()->id]))->assertOk()
        ->assertDontSee('name="new_employee" value="1"', false);
    $data['nik'] = '0000000000000013';
    $data['month'] = 12;
    $this->post(route('payroll.store', $company), $data)->assertSessionHasErrors('withholding_date');
    expect(Employee::count())->toBe(1);
});

test('spreadsheet updates identity bpjs and period atomically and refuses occupied destinations', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $payroll = app(PayrollService::class);
    $entry = $payroll->saveEntry($admin, $company, parityInput($branch, $employee));
    $input = parityInput($branch, $employee, ['version' => 1, 'employee_name' => 'Nama baru', 'employee_nik' => '0000000000000099', 'employee_position' => 'Manager', 'bpjs_company' => 500000, 'month' => 10, 'withholding_date' => '2026-10-31', 'return_to' => 'spreadsheet']);
    $this->actingAs($admin)->put(route('payroll.update', [$company, $entry]), $input)->assertRedirect();
    expect($employee->fresh()->name)->toBe('Nama baru');
    expect($entry->fresh()->gross)->toBe(10500000);
    expect($entry->fresh()->payrollRun->month)->toBe(10);
    expect(AuditEvent::where('action', 'payroll.entry.moved')->exists())->toBeTrue();
    $payroll->saveEntry($admin, $company, parityInput($branch, $employee));
    $input['month'] = 11;
    $input['withholding_date'] = '2026-11-30';
    $input['version'] = 2;
    $input['employee_name'] = 'Tidak tersimpan';
    $this->put(route('payroll.update', [$company, $entry]), $input)->assertSessionHasErrors('employee_id');
    expect($entry->fresh()->payrollRun->month)->toBe(10);
    expect($employee->fresh()->name)->toBe('Nama baru');
});

test('replace import archives only the selected branch module and period and rejects stale previews', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $other = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create(['nik' => '0000000000000001']);
    $otherEmployee = Employee::factory()->for($company)->create();
    $payroll = app(PayrollService::class);
    $entry = $payroll->saveEntry($admin, $company, parityInput($branch, $employee));
    $outside = $payroll->saveEntry($admin, $company, parityInput($other, $otherEmployee));
    $imports = app(PayrollImportService::class);
    $batch = $imports->preview($admin, $company, $branch->id, 'monthly', parityRows(salary: 12000000), ['mode' => 'REPLACE']);
    $this->actingAs($admin)->post(route('imports.commit', [$company, $batch]))->assertRedirect();
    expect($batch->fresh()->status)->toBe('Completed');
    expect(PayrollEntry::whereKey($entry->id)->exists())->toBeFalse();
    expect(PayrollEntry::findOrFail($outside->id)->gross)->toBe(10000000);
    expect((int) PayrollEntry::where('employee_id', $employee->id)->firstOrFail()->gross)->toBe(12000000);
    expect(AuditEvent::where('action', 'payroll.entry.deleted')->count())->toBe(1);
    $stale = $imports->preview($admin, $company, $branch->id, 'monthly', parityRows(salary: 13000000), ['mode' => 'REPLACE']);
    $current = PayrollEntry::where('employee_id', $employee->id)->firstOrFail();
    $payroll->saveEntry($admin, $company, parityInput($branch, $employee, ['version' => $current->version, 'salary' => 14000000]), $current);
    $this->post(route('imports.commit', [$company, $stale]))->assertSessionHasErrors('import');
    expect($current->fresh()->gross)->toBe(14000000);
});

test('replace import rolls back deletion when an imported row fails and never replaces finalized data', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create(['nik' => '0000000000000001']);
    $payroll = app(PayrollService::class);
    $entry = $payroll->saveEntry($admin, $company, parityInput($branch, $employee));
    $imports = app(PayrollImportService::class);
    $rows = parityRows();
    $rows[1][7] = '2026-12-01';
    $batch = $imports->preview($admin, $company, $branch->id, 'monthly', $rows, ['mode' => 'REPLACE']);
    // Existing recipients keep their employment dates; use a new recipient to exercise rollback.
    $records = $batch->records;
    $records[0]['nik'] = '0000000000000010';
    $batch->update(['records' => $records, 'status' => 'Queued']);
    expect(fn () => $imports->commit($batch))->toThrow(ValidationException::class);
    expect($entry->fresh()->gross)->toBe(10000000);
    expect(Employee::count())->toBe(1);
    $payroll->transition($admin, $company, $entry->payrollRun, 'review');
    $payroll->transition($admin, $company, $entry->payrollRun, 'finalize');
    expect(fn () => $imports->preview($admin, $company, $branch->id, 'monthly', parityRows(), ['mode' => 'REPLACE']))->toThrow(ValidationException::class);
});

test('active import period overrides file period and is visible before commit', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $rows = parityRows();
    $text = implode("\t", $rows[0])."\n".implode("\t", $rows[1]);
    $this->actingAs($admin)->post(route('imports.preview', $company), ['branch_id' => $branch->id, 'module' => 'monthly', 'pasted' => $text, 'period_source' => 'ACTIVE', 'month' => 10, 'year' => 2026, 'mode' => 'APPEND'])->assertRedirect();
    $batch = ImportBatch::firstOrFail();
    expect($batch->records[0]['month'])->toBe(10);
    expect($batch->records[0]['withholding_date'])->toBe('2026-10-31');
    $this->get(route('imports.index', $company))->assertOk()->assertSee('10/2026')->assertSee('APPEND');
});

test('module templates contain the required subject fields and can be previewed', function (string $module, string $field): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $imports = app(PayrollImportService::class);
    $rows = $imports->template($module, 2026, $module === 'annual' ? 12 : 11);
    expect($rows[0])->toContain($field);
    expect($imports->preview($admin, $company, $branch->id, $module, $rows)->errors)->toBe([]);
})->with([['monthly', 'BPJS_Perusahaan'], ['annual', 'Saldo_Awal_Bruto'], ['pension', 'Masa_Terakhir'], ['daily', 'Hari_Kerja'], ['contractor', 'Kode_Objek_Pajak'], ['severance', 'Tahun_Awal_Final']]);

test('generated data uses private preview and resetting samples preserves real payroll', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $payroll = app(PayrollService::class);
    $real = $payroll->saveEntry($admin, $company, parityInput($branch, $employee));
    $this->actingAs($admin)->post(route('imports.demo', $company), ['branch_id' => $branch->id, 'module' => 'monthly', 'year' => 2026, 'month' => 11, 'count' => 50])->assertRedirect();
    $batch = ImportBatch::firstOrFail();
    expect($batch->row_count)->toBe(50);
    expect($batch->errors)->toBe([]);
    expect(PayrollEntry::count())->toBe(1);
    $this->post(route('imports.commit', [$company, $batch]))->assertRedirect();
    expect(PayrollEntry::count())->toBe(51);
    $this->post(route('payroll.bulk', $company), ['branch_id' => $branch->id, 'module' => 'monthly', 'year' => 2026, 'month' => 11, 'action' => 'reset_demo'])->assertRedirect();
    expect(PayrollEntry::count())->toBe(1);
    expect(Employee::count())->toBe(1);
    expect($real->fresh()->gross)->toBe(10000000);
});

test('annual synchronization uses finalized real history and preserves existing December bonuses', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create(['joined_at' => '2026-11-01']);
    $payroll = app(PayrollService::class);
    $previous = $payroll->saveEntry($admin, $company, parityInput($branch, $employee, ['pension_employee' => 100000]));
    $selection = ['branch_id' => $branch->id, 'year' => 2026, 'month' => 12, 'module' => 'annual'];
    $this->actingAs($admin)->post(route('payroll.bulk', $company), $selection + ['action' => 'pull_monthly'])->assertSessionHasErrors('run');
    expect(PayrollEntry::count())->toBe(1);
    $payroll->transition($admin, $company, $previous->payrollRun, 'review');
    $payroll->transition($admin, $company, $previous->payrollRun, 'finalize');
    $this->post(route('payroll.bulk', $company), $selection + ['action' => 'pull_monthly'])->assertRedirect();
    $annual = PayrollEntry::where('id', '!=', $previous->id)->firstOrFail();
    expect($annual->input['prior_gross'])->toBe(10000000);
    expect($annual->input['pension_employee'])->toBe(100000);
    expect($annual->input['months_worked'])->toBe(2);
    $payroll->saveEntry($admin, $company, array_merge($annual->input, ['version' => $annual->version, 'bonus' => 1000000]), $annual);
    $this->post(route('payroll.bulk', $company), $selection + ['action' => 'pull_monthly'])->assertRedirect();
    expect($annual->fresh()->input['bonus'])->toBe(1000000);
    expect($annual->fresh()->result['annual_gross'])->toBe(21000000);
});

test('annual worksheet adjustments recalculate exports and can be reset with an audit trail', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create(['joined_at' => '2026-01-01']);
    $input = parityInput($branch, $employee, ['module' => 'annual', 'month' => 12, 'withholding_date' => '2026-12-31', 'opening_gross' => 110000000, 'opening_tax' => 2200000, 'opening_salary_only' => true, 'opening_reason' => 'Saldo migrasi gaji Januari-November', 'worksheet' => ['bonus' => 12000000, 'pension_employee' => 2400000, 'reason' => 'Rincian bonus dan iuran dari payroll lama']]);
    $this->actingAs($admin)->post(route('payroll.store', $company), $input)->assertRedirect();
    $entry = PayrollEntry::firstOrFail();
    expect($entry->result['annual_gross'])->toBe(132000000);
    expect($entry->result['annual_tax'])->toBe(4440000);
    expect($entry->tax)->toBe(2240000);
    expect($entry->input['annual_components']['bonus'])->toBe(12000000);
    $payroll = app(PayrollService::class);
    $payroll->transition($admin, $company, $entry->payrollRun, 'review');
    $payroll->transition($admin, $company, $entry->payrollRun, 'finalize');
    $xml = app(CoretaxExportService::class)->xml($admin, $company, ['branch_id' => $branch->id, 'year' => 2026, 'month' => 12, 'module' => 'annual']);
    expect($xml)->toContain('<TantiemBonusThr>12000000</TantiemBonusThr>');
    $payroll->transition($admin, $company, $entry->payrollRun, 'correct', 'Reset penyesuaian worksheet');
    unset($input['worksheet']);
    $input['version'] = $entry->version;
    $this->put(route('payroll.update', [$company, $entry]), $input)->assertRedirect();
    expect($entry->fresh()->result['annual_gross'])->toBe(120000000);
    expect($entry->fresh()->tax)->toBe(800000);
    expect(AuditEvent::where('action', 'payroll.worksheet.changed')->count())->toBe(2);
});

test('worksheet rejects arbitrary expenses and missing adjustment reasons', function (): void {
    $tax = app(TaxService::class);
    $input = ['module' => 'annual', 'year' => 2026, 'month' => 12, 'months_worked' => 12, 'ptkp' => 'TK/0', 'method' => 'GROSS', 'salary' => 10000000, 'prior_gross' => 110000000, 'worksheet' => ['expense' => 10000000, 'reason' => 'Uji batas biaya']];
    expect(fn () => $tax->calculate($input))->toThrow(ValidationException::class);
    $input['worksheet'] = ['bonus' => 1000000];
    expect(fn () => $tax->calculate($input))->toThrow(ValidationException::class);
});

test('new write actions reject guests unprivileged users and foreign companies', function (): void {
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $data = ['branch_id' => $branch->id, 'module' => 'monthly', 'year' => 2026, 'month' => 11, 'count' => 50];
    $this->post(route('imports.demo', $company), $data)->assertRedirect(route('login'));
    $auditor = User::factory()->create(['role' => 'AUDITOR']);
    $auditor->companies()->attach($company);
    $this->actingAs($auditor)->post(route('imports.demo', $company), $data)->assertForbidden();
    $staff = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $this->actingAs($staff)->post(route('imports.demo', $company), $data)->assertNotFound();
    expect(ImportBatch::count())->toBe(0);
    expect(PayrollEntry::count())->toBe(0);
});

test('empty companies can be deleted while companies with recipient history remain intact', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    Branch::factory()->for($company)->create();
    $this->actingAs($admin)->delete(route('companies.destroy', $company))->assertRedirect(route('companies.index'));
    expect(Company::whereKey($company->id)->exists())->toBeFalse();
    expect(Branch::where('company_id', $company->id)->exists())->toBeFalse();
    expect(AuditEvent::where('action', 'company.deleted')->exists())->toBeTrue();
    $occupied = Company::factory()->create();
    Employee::factory()->for($occupied)->create();
    $this->delete(route('companies.destroy', $occupied))->assertSessionHasErrors('company');
    expect(Company::whereKey($occupied->id)->exists())->toBeTrue();
});

test('account deletion protects the current administrator and users with transaction history', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $staff = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $this->actingAs($admin)->delete(route('access.destroy', $staff))->assertRedirect(route('access.index'));
    expect(User::whereKey($staff->id)->exists())->toBeFalse();
    $this->delete(route('access.destroy', $admin))->assertSessionHasErrors('user');
    expect($admin->fresh()->is_active)->toBeTrue();
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $historical = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    ImportBatch::factory()->create(['user_id' => $historical->id, 'company_id' => $company->id, 'branch_id' => $branch->id]);
    $this->delete(route('access.destroy', $historical))->assertSessionHasErrors('user');
    expect(User::whereKey($historical->id)->exists())->toBeTrue();
});

test('contractor non-service costs require evidence and recalculate payroll journals and exports', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $input = parityInput($branch, $employee, ['module' => 'contractor', 'salary' => 10000000, 'contractor_expenses' => 2000000, 'tax_object_code' => '21-100-07', 'recipient_nitku' => $employee->nik.'000000', 'document_number' => 'PAY/11/1', 'document_date' => '2026-11-30']);
    $this->actingAs($admin)->post(route('payroll.store', $company), $input)->assertSessionHasErrors('contractor_expenses');
    $input['contractor_expense_reference'] = 'Kontrak 001, faktur MAT-10 dan bukti pembayaran 002';
    $this->post(route('payroll.store', $company), $input)->assertRedirect();
    $entry = PayrollEntry::firstOrFail();
    expect($entry->gross)->toBe(8000000);
    expect($entry->tax)->toBe(200000);
    expect($entry->take_home)->toBe(7800000);
    $this->get(route('payroll.show', [$company, $entry]))->assertOk()->assertSee('7.800.000');
    $payroll = app(PayrollService::class);
    $payroll->transition($admin, $company, $entry->payrollRun, 'review');
    $payroll->transition($admin, $company, $entry->payrollRun, 'finalize');
    $xml = app(CoretaxExportService::class)->xml($admin, $company, ['branch_id' => $branch->id, 'year' => 2026, 'month' => 11, 'module' => 'contractor']);
    expect($xml)->toContain('<Gross>8000000</Gross>');
});

test('payroll staff cannot persist manual worksheet corrections', function (): void {
    $staff = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $company = Company::factory()->create();
    $staff->companies()->attach($company);
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create(['joined_at' => '2026-12-01']);
    $input = parityInput($branch, $employee, ['module' => 'annual', 'month' => 12, 'withholding_date' => '2026-12-31', 'worksheet' => ['bonus' => 1000000, 'reason' => 'Koreksi bonus']]);
    $this->actingAs($staff)->post(route('payroll.store', $company), $input)->assertForbidden();
    expect(PayrollEntry::count())->toBe(0);
    unset($input['worksheet']);
    $input['worksheet'] = ['bonus' => null, 'reason' => null];
    $this->post(route('payroll.store', $company), $input)->assertRedirect();
    expect(PayrollEntry::firstOrFail()->input)->not->toHaveKey('worksheet');
});

test('gross-up annual worksheet and contractor expenses retain consistent taxes and balanced journals', function (string $method): void {
    $tax = app(TaxService::class);
    $result = $tax->calculate(['module' => 'annual', 'year' => 2026, 'month' => 12, 'salary' => 10000000, 'prior_gross' => 110000000, 'prior_tax' => 2200000, 'method' => $method, 'ptkp' => 'TK/0', 'worksheet' => ['bonus' => 12000000, 'reason' => 'Rekonsiliasi bonus']]);
    expect($result['tax_allowance'])->toBe($result['tax']);
    $journal = app(JournalService::class)->entries($result);
    expect(array_sum(array_column($journal, 'debit')))->toBe(array_sum(array_column($journal, 'credit')));
    $result = $tax->calculate(['module' => 'contractor', 'year' => 2026, 'month' => 11, 'salary' => 10000000, 'method' => $method, 'ptkp' => 'TK/0', 'contractor_expenses' => 2000000, 'contractor_expense_reference' => 'Faktur pembayaran terpisah']);
    expect($result['tax_allowance'])->toBe($result['tax']);
    expect($result['take_home'])->toBe(8000000);
})->with(['GROSS_UP', 'NETT']);

test('selected company persists across dashboard navigation and branch edits preserve finalized snapshots', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $other = Company::factory()->create();
    Branch::factory()->for($other)->create();
    $this->actingAs($admin)->get(route('payroll.index', $other))->assertOk()->assertSessionHas('active_company_id', $other->id);
    $this->get(route('dashboard'))->assertOk()
        ->assertViewHas('bootstrap', fn (array $state): bool => collect($state['companies'])->firstWhere('id', $state['activeCompanyId'])['companyId'] === $other->id);
    $employee = Employee::factory()->for($company)->create();
    $payroll = app(PayrollService::class);
    $entry = $payroll->saveEntry($admin, $company, parityInput($branch, $employee));
    $payroll->transition($admin, $company, $entry->payrollRun, 'review');
    $payroll->transition($admin, $company, $entry->payrollRun, 'finalize');
    $snapshotNitku = $entry->payrollRun->fresh()->company_snapshot['nitku'];
    $this->put(route('branches.update', [$company, $branch]), ['name' => 'Cabang baru', 'code' => '000999'])->assertRedirect();
    expect($branch->fresh()->nitku)->toBe($company->npwp.'000999');
    expect($entry->payrollRun->fresh()->company_snapshot['nitku'])->toBe($snapshotNitku);
    $this->put(route('branches.update', [$other, $branch]), ['name' => 'Lintas perusahaan', 'code' => '000998'])->assertNotFound();
});

test('copying contractor payroll resets payment document and bulk THR can be reset', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $payroll = app(PayrollService::class);
    $previous = $payroll->saveEntry($admin, $company, parityInput($branch, $employee, ['module' => 'contractor', 'month' => 10, 'withholding_date' => '2026-10-31', 'tax_object_code' => '21-100-07', 'document_number' => 'PAY/10', 'document_date' => '2026-10-31']));
    $payroll->transition($admin, $company, $previous->payrollRun, 'review');
    $payroll->transition($admin, $company, $previous->payrollRun, 'finalize');
    $this->actingAs($admin)->post(route('payroll.bulk', $company), ['branch_id' => $branch->id, 'year' => 2026, 'month' => 11, 'module' => 'contractor', 'action' => 'copy'])->assertRedirect();
    $copied = PayrollEntry::where('id', '!=', $previous->id)->firstOrFail();
    expect($copied->input)->not->toHaveKey('document_number');
    expect($copied->input['withholding_date'])->toBe('2026-11-30');
    $monthly = $payroll->saveEntry($admin, $company, parityInput($branch, $employee, ['thr' => 10000000]));
    $this->post(route('payroll.bulk', $company), ['branch_id' => $branch->id, 'year' => 2026, 'month' => 11, 'module' => 'monthly', 'action' => 'reset_thr'])->assertRedirect();
    expect($monthly->fresh()->input['thr'])->toBe(0);
    expect($copied->fresh()->gross)->toBe(10000000);
});

test('2500 generated recipients are queued and the worker operation imports the full batch', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $imports = app(PayrollImportService::class);
    $batch = $imports->demo($admin, $company, ['branch_id' => $branch->id, 'module' => 'monthly', 'year' => 2026, 'month' => 11], 2500);
    expect($batch->errors)->toBe([]);
    Queue::fake([CommitPayrollImport::class]);
    $this->actingAs($admin)->post(route('imports.commit', [$company, $batch]))->assertRedirect();
    Queue::assertPushed(CommitPayrollImport::class, fn ($job) => $job->batchId === $batch->id);
    (new CommitPayrollImport($batch->id))->handle($imports);
    expect($batch->fresh()->status)->toBe('Completed');
    expect(PayrollEntry::count())->toBe(2500);
    expect(Employee::count())->toBe(2500);
});

test('spreadsheet preserves an annual worksheet adjustment and stale versions cannot overwrite it', function (): void {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create(['joined_at' => '2026-12-01']);
    $input = parityInput($branch, $employee, ['module' => 'annual', 'month' => 12, 'withholding_date' => '2026-12-31', 'worksheet' => ['bonus' => 1000000, 'reason' => 'Koreksi bonus']]);
    $entry = app(PayrollService::class)->saveEntry($admin, $company, $input);
    $this->actingAs($admin)->get(route('payroll.index', ['company' => $company, 'branch_id' => $branch->id, 'module' => 'annual', 'month' => 12, 'year' => 2026]))->assertOk()
        ->assertViewHas('bootstrap', fn (array $state): bool => $state['employees'][0]['subjectType'] === 'pegawai_tahunan' && $state['employees'][0]['_version'] === 1);
    $this->put(route('payroll.update', [$company, $entry]), $input + ['version' => 1, 'return_to' => 'spreadsheet'])->assertRedirect();
    expect($entry->fresh()->input['worksheet']['bonus'])->toBe(1000000);
    $this->put(route('payroll.update', [$company, $entry]), $input + ['version' => 1, 'return_to' => 'spreadsheet'])->assertSessionHasErrors('version');
    expect($entry->fresh()->version)->toBe(2);
});
