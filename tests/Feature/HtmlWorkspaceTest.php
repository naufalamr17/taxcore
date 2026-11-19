<?php

use App\Models\AuditEvent;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function htmlRecord(Branch $branch, array $changes = []): array
{
    return array_merge(['id' => 'emp_new_1', 'companyId' => 'branch_'.$branch->id, 'year' => 2026, 'month' => 11, 'name' => 'Penerima HTML', 'nik' => '3171000000000099', 'position' => 'Staff', 'subjectType' => 'pegawai_bulanan', 'ptkp' => 'TK/0', 'taxMethod' => 'GROSS', 'gajiPokok' => 10000000, 'tunjangan' => 0, 'bonus' => 0, 'thr' => 0, 'kompensasi' => 0, 'bpjs' => 0, 'iuranPensiun' => 0, 'iuranJht' => 0, 'workMonths' => 12, 'dailyScheme' => 'HARIAN', 'workDays' => 1, 'finalSubType' => 'PESANGON', 'hasNpwp' => true, 'syncedFromMonthly' => false], $changes);
}

test('html workspace requires authentication and never exposes other company records', function (): void {
    $this->getJson(route('workspace.state'))->assertUnauthorized();
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $run = PayrollRun::factory()->for($branch)->create(['company_id' => $company->id]);
    PayrollEntry::factory()->for($run)->create();
    PayrollEntry::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);

    $this->actingAs($user)->getJson(route('workspace.state'))->assertOk()->assertJsonCount(1, 'companies')->assertJsonCount(1, 'employees')->assertJsonPath('companies.0.id', 'branch_'.$branch->id)->assertJsonPath('employees.0.companyId', 'branch_'.$branch->id)->assertJsonMissingPath('user.password');
});

test('html payroll saves and reloads original fields while computing trusted totals and audit', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $record = htmlRecord($branch, ['gajiPokok' => 10000000.25]);

    $this->actingAs($user)->postJson(route('workspace.employees', [$company, $branch]), ['upserts' => [$record], 'deletes' => []])->assertOk()->assertJsonPath('employees.0.id', 'emp_new_1')->assertJsonPath('employees.0._version', 1)->assertJsonPath('employees.0.gajiPokok', 10000000.25);

    $entry = PayrollEntry::firstOrFail();
    expect($entry->tax)->toBe(200000);
    expect($entry->input['html_record']['gajiPokok'])->toBe(10000000.25);
    expect(AuditEvent::where('action', 'payroll.entry.saved')->where('company_id', $company->id)->count())->toBe(1);
    $this->getJson(route('workspace.state'))->assertOk()->assertJsonPath('employees.0.gajiPokok', 10000000.25);
});

test('html payroll edits and deletes rows with version checks', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $record = htmlRecord($branch);
    $url = route('workspace.employees', [$company, $branch]);
    $this->actingAs($user)->postJson($url, ['upserts' => [$record], 'deletes' => []])->assertOk();
    $record['_version'] = 1;
    $record['gajiPokok'] = 11000000;

    $this->postJson($url, ['upserts' => [$record], 'deletes' => []])->assertOk()->assertJsonPath('employees.0._version', 2);

    expect(PayrollEntry::firstOrFail()->tax)->toBe(330000);
    $this->postJson($url, ['upserts' => [$record], 'deletes' => []])->assertUnprocessable()->assertJsonValidationErrors('version');
    expect(PayrollEntry::firstOrFail()->version)->toBe(2);
    $this->postJson($url, ['upserts' => [], 'deletes' => [['id' => $record['id'], '_version' => 1]]])->assertUnprocessable();
    expect(PayrollEntry::count())->toBe(1);
    $this->postJson($url, ['upserts' => [], 'deletes' => [['id' => $record['id'], '_version' => 2]]])->assertOk()->assertJsonCount(0, 'employees');
    expect(PayrollEntry::count())->toBe(0);
    expect(AuditEvent::where('action', 'payroll.entry.deleted')->count())->toBe(1);
});

test('html payroll rejects cross company branch and read only users', function (): void {
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $other = Company::factory()->create();
    $otherBranch = Branch::factory()->for($other)->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);

    $this->actingAs($user)->postJson(route('workspace.employees', [$other, $otherBranch]), ['upserts' => [htmlRecord($otherBranch)], 'deletes' => []])->assertNotFound();
    $this->postJson(route('workspace.employees', [$company, $otherBranch]), ['upserts' => [htmlRecord($otherBranch)], 'deletes' => []])->assertNotFound();
    $this->postJson(route('workspace.employees', [$company, $branch]), ['upserts' => [htmlRecord($otherBranch)], 'deletes' => []])->assertNotFound();
    $auditor = User::factory()->create(['role' => 'AUDITOR']);
    $auditor->companies()->attach($company);
    $this->actingAs($auditor)->postJson(route('workspace.employees', [$company, $branch]), ['upserts' => [htmlRecord($branch)], 'deletes' => []])->assertForbidden();
    expect(PayrollEntry::count())->toBe(0);
});

test('html payroll rejects invalid values and client calculated totals atomically', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();

    $this->actingAs($user)->postJson(route('workspace.employees', [$company, $branch]), ['upserts' => [htmlRecord($branch), htmlRecord($branch, ['id' => 'emp_bad', 'nik' => 'invalid', 'gajiPokok' => -1, 'gross' => 1])], 'deletes' => []])->assertUnprocessable()->assertJsonValidationErrors(['upserts.1', 'upserts.1.nik', 'upserts.1.gajiPokok']);

    expect(PayrollEntry::count())->toBe(0);
    expect(Employee::count())->toBe(0);
    expect(AuditEvent::count())->toBe(0);
});

test('html annual rows and all reference modules save without a review workflow', function (string $subject): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $record = htmlRecord($branch, ['subjectType' => $subject, 'month' => 12, 'year' => 2035, 'a1ManualRegBruto' => $subject === 'pegawai_tahunan' ? 120000000 : null]);

    $this->actingAs($user)->postJson(route('workspace.employees', [$company, $branch]), ['upserts' => [$record], 'deletes' => []])->assertOk()->assertJsonPath('employees.0.subjectType', $subject)->assertJsonPath('employees.0.year', 2035);

    expect(PayrollEntry::count())->toBe(1);
})->with(['pegawai_bulanan', 'pegawai_tahunan', 'pensiunan', 'tidak_tetap', 'bukan_pegawai', 'pesangon_final']);

test('html payroll rolls back prior valid edits when another row has a stale version', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $run = PayrollRun::factory()->for($branch)->create(['company_id' => $company->id]);
    $entries = PayrollEntry::factory()->for($run)->count(2)->create();
    $record = htmlRecord($branch, ['id' => 'entry_'.$entries[0]->id, '_version' => 1]);
    $stale = htmlRecord($branch, ['id' => 'entry_'.$entries[1]->id, '_version' => 2, 'nik' => '3171000000000088']);

    $this->actingAs($user)->postJson(route('workspace.employees', [$company, $branch]), ['upserts' => [$record, $stale], 'deletes' => []])->assertUnprocessable()->assertJsonValidationErrors('version');

    expect($entries[0]->fresh()->version)->toBe(1);
    expect($entries[0]->fresh()->input['name'])->not->toBe('Penerima HTML');
    expect(AuditEvent::count())->toBe(0);
});

test('html company form persists one flat company and tku record', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $record = ['id' => 'comp_123', 'name' => 'Perusahaan HTML', 'branchName' => 'Cabang Jakarta', 'npwp' => '1234567890123456', 'nitkuSuffix' => '000001', 'city' => 'Jakarta', 'address' => 'Jalan Contoh', 'signerName' => 'Penandatangan', 'signerNpwp' => '3171000000000001'];

    $response = $this->actingAs($user)->postJson(route('workspace.companies.save'), $record)->assertOk()->assertJsonCount(1, 'companies')->assertJsonPath('company.nitkuSuffix', '000001')->assertJsonPath('company.idTkuFull', '1234567890123456000001');

    expect(Company::count())->toBe(1);
    expect(Branch::count())->toBe(1);
    $record['id'] = $response['company']['id'];
    $record['name'] = 'Profil diperbarui';
    $this->postJson(route('workspace.companies.save'), $record)->assertOk()->assertJsonPath('company.name', 'Profil diperbarui');
    expect(Company::firstOrFail()->name)->toBe('Profil diperbarui');
    $this->deleteJson(route('workspace.companies.destroy', Branch::firstOrFail()))->assertOk()->assertJsonCount(0, 'companies');
    expect(Company::count())->toBe(0);
    expect(AuditEvent::where('action', 'company.deleted')->count())->toBe(1);
});

test('html company form cannot modify a company outside user membership', function (): void {
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $manager = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    RolePermission::factory()->create(['role' => 'PAYROLL_STAFF', 'permission' => 'company.manage', 'allowed' => true]);
    $record = ['id' => 'branch_'.$branch->id, 'name' => 'Unauthorized', 'branchName' => 'Pusat', 'npwp' => $company->npwp, 'nitkuSuffix' => '000000', 'city' => 'Jakarta'];

    $this->actingAs($manager)->postJson(route('workspace.companies.save'), $record)->assertNotFound();
    $this->deleteJson(route('workspace.companies.destroy', $branch))->assertNotFound();

    expect($company->fresh()->name)->not->toBe('Unauthorized');
});

test('html tku profiles retain independent signers and preserve legacy branch details', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create(['name' => 'Perusahaan Lama', 'signer_name' => 'Penandatangan Lama', 'signer_npwp' => '3171000000000001', 'city' => 'Bandung', 'address' => 'Alamat Lama']);
    $head = Branch::factory()->for($company)->create(['code' => '000000']);
    $branch = Branch::factory()->for($company)->create(['code' => '000001']);
    $record = ['id' => 'branch_'.$head->id, 'name' => 'Perusahaan Pusat', 'branchName' => 'Pusat', 'npwp' => $company->npwp, 'nitkuSuffix' => '000000', 'city' => 'Jakarta', 'address' => 'Alamat Pusat', 'signerName' => 'Penandatangan Pusat', 'signerNpwp' => '3171000000000002'];

    $this->actingAs($user)->postJson(route('workspace.companies.save'), $record)->assertOk()->assertJsonPath('company.signerName', 'Penandatangan Pusat');
    $records = collect($this->getJson(route('workspace.state'))->assertOk()['companies'])->keyBy('id');
    expect($records['branch_'.$branch->id])->toMatchArray(['name' => 'Perusahaan Lama', 'city' => 'Bandung', 'address' => 'Alamat Lama', 'signerName' => 'Penandatangan Lama']);

    $record = array_merge($record, ['id' => 'branch_'.$branch->id, 'name' => 'Perusahaan Cabang', 'branchName' => 'Cabang', 'nitkuSuffix' => '000001', 'city' => 'Surabaya', 'address' => 'Alamat Cabang', 'signerName' => 'Penandatangan Cabang', 'signerNpwp' => '3171000000000003']);
    $this->postJson(route('workspace.companies.save'), $record)->assertOk();
    $records = collect($this->getJson(route('workspace.state'))->assertOk()['companies'])->keyBy('id');

    expect($records['branch_'.$head->id])->toMatchArray(['name' => 'Perusahaan Pusat', 'city' => 'Jakarta', 'address' => 'Alamat Pusat', 'signerName' => 'Penandatangan Pusat', 'signerNpwp' => '3171000000000002']);
    expect($records['branch_'.$branch->id])->toMatchArray(['name' => 'Perusahaan Cabang', 'city' => 'Surabaya', 'address' => 'Alamat Cabang', 'signerName' => 'Penandatangan Cabang', 'signerNpwp' => '3171000000000003']);
    expect(Company::count())->toBe(1);
    expect(Branch::count())->toBe(2);
});

test('html export records the existing audit action for the selected tku', function (string $format): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();

    $this->actingAs($user)->postJson(route('workspace.export', [$company, $branch]), ['format' => $format, 'year' => 2026, 'month' => 11])->assertOk()->assertJsonPath('recorded', true);

    $event = AuditEvent::firstOrFail();
    expect($event->action)->toBe('export.'.$format);
    expect($event->company_id)->toBe($company->id);
    expect($event->details['branch_id'])->toBe($branch->id);
})->with(['xlsx', 'xml']);

test('html export checks permission tenant and format before writing an audit event', function (): void {
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $staff = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $staff->companies()->attach($company);
    $data = ['format' => 'xml', 'year' => 2026, 'month' => 11];

    $this->actingAs($staff)->postJson(route('workspace.export', [$company, $branch]), $data)->assertForbidden();
    $auditor = User::factory()->create(['role' => 'AUDITOR']);
    $this->actingAs($auditor)->postJson(route('workspace.export', [$company, $branch]), $data)->assertNotFound();
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $this->actingAs($admin)->postJson(route('workspace.export', [$company, $branch]), array_merge($data, ['format' => 'exe']))->assertUnprocessable();

    expect(AuditEvent::count())->toBe(0);
});

test('html pension benefit final subtype retains its own tax scale', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $record = htmlRecord($branch, ['subjectType' => 'pesangon_final', 'gajiPokok' => 120000000, 'finalSubType' => 'MANFAAT_PENSIUN']);

    $this->actingAs($user)->postJson(route('workspace.employees', [$company, $branch]), ['upserts' => [$record], 'deletes' => []])->assertOk()->assertJsonPath('employees.0.finalSubType', 'MANFAAT_PENSIUN');

    expect(PayrollEntry::firstOrFail()->tax)->toBe(3500000);
});

test('html manual worksheet changes require correction permission and preserve existing data on refusal', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $user->companies()->attach($company);
    $record = htmlRecord($branch, ['subjectType' => 'pegawai_tahunan', 'month' => 12, 'a1ManualRegBruto' => 120000000]);

    $this->actingAs($user)->postJson(route('workspace.employees', [$company, $branch]), ['upserts' => [$record], 'deletes' => []])->assertForbidden();

    expect(PayrollEntry::count())->toBe(0);
    expect(AuditEvent::count())->toBe(0);
});

test('html company deletion keeps payroll and audit history', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $run = PayrollRun::factory()->for($branch)->create(['company_id' => $company->id]);
    $entry = PayrollEntry::factory()->for($run)->create();

    $this->actingAs($user)->deleteJson(route('workspace.companies.destroy', $branch))->assertUnprocessable()->assertJsonValidationErrors('company');

    $this->assertModelExists($company);
    $this->assertModelExists($entry);
});
