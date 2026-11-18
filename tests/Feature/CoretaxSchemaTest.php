<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\User;
use App\Services\CoretaxExportService;
use App\Services\PayrollService;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);
test('XML field names and order match official DJP samples', function (string $module, string $fixture, string $family, array $changes): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $input = array_merge(['branch_id' => $branch->id, 'employee_id' => $employee->id, 'year' => 2026, 'month' => 11, 'module' => $module, 'method' => 'GROSS', 'ptkp' => 'TK/0', 'salary' => 10000000, 'tax_object_code' => '21-100-07', 'withholding_date' => '2026-11-30', 'recipient_nitku' => '0000000000000001000000', 'document_number' => 'PAY-001', 'document_date' => '2026-11-30'], $changes);
    $payroll = app(PayrollService::class);
    $entry = $payroll->saveEntry($user, $company, $input);
    $payroll->transition($user, $company, $entry->payrollRun, 'review');
    $payroll->transition($user, $company, $entry->payrollRun, 'finalize');

    $xml = simplexml_load_string(app(CoretaxExportService::class)->xml($user, $company, ['branch_id' => $branch->id, 'year' => 2026, 'month' => $input['month'], 'module' => $module]));
    $official = simplexml_load_file(__DIR__.'/../Fixtures/coretax/'.$fixture.'.xml');
    expect($xml->getName())->toBe($official->getName());
    $actual = $xml->{'ListOf'.$family}->{$family};
    $expected = $official->{'ListOf'.$family}->{$family}[0];
    $names = static function (SimpleXMLElement $element): array {
        $keys = [];
        foreach ($element->children() as $child) {
            $keys[] = $child->getName();
        }

        return $keys;
    };
    expect($names($actual))->toBe($names($expected));
    if ($family === 'Bp21') {
        expect((string) $actual->Document)->toBe('PaymentProof');
        expect((string) $actual->Deemed)->toBe('50');
        expect((string) $actual->Rate)->toBe('15');
    }
    if ($family === 'A1') {
        expect((string) $actual->StatusOfWithholding)->toBe('FullYear');
        expect((string) $actual->NumberOfMonths)->toBe('0');
        expect((string) $actual->Article21IncomeTax)->toBe('2200000');
    }
})->with([
    ['monthly', 'bpmp', 'MmPayroll', []],
    ['contractor', 'bp21', 'Bp21', ['salary' => 300000000]],
    ['annual', 'bpa1', 'A1', ['month' => 12, 'withholding_date' => '2026-12-31', 'opening_salary_only' => true, 'opening_gross' => 110000000, 'opening_tax' => 2200000, 'opening_reason' => 'Saldo awal dari sistem sebelumnya']],
]);
test('daily and final benefit codes follow the current DJP reference', function (array $input, string $expected): void {
    $data = array_merge(['module' => 'daily', 'method' => 'GROSS', 'ptkp' => 'TK/0', 'year' => 2026, 'month' => 11, 'salary' => 500000, 'tax_object_code' => '21-100-03'], $input);
    $tax = app(TaxService::class);
    expect($tax->objectCode($data, $tax->calculate($data)))->toBe($expected);
})->with([
    [[], '21-100-24'],
    [['salary' => 3000000], '21-100-30'],
    [['daily_scheme' => 'MONTHLY'], '21-100-35'],
    [['module' => 'severance', 'final_kind' => 'PENSION'], '21-401-02'],
]);
test('one employee cannot split monthly withholding across branches', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $second = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $data = ['branch_id' => $branch->id, 'employee_id' => $employee->id, 'module' => 'monthly', 'year' => 2026, 'month' => 11, 'ptkp' => 'TK/0', 'method' => 'GROSS', 'salary' => 10000000, 'tax_object_code' => '21-100-01', 'withholding_date' => '2026-11-30'];
    $payroll = app(PayrollService::class);
    $payroll->saveEntry($user, $company, $data);

    expect(fn () => $payroll->saveEntry($user, $company, array_merge($data, ['branch_id' => $second->id])))->toThrow(ValidationException::class);
    expect(PayrollEntry::count())->toBe(1);
});
test('copying previous payroll uses the new date and clears irregular pay', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $payroll = app(PayrollService::class);
    $entry = $payroll->saveEntry($user, $company, ['branch_id' => $branch->id, 'employee_id' => $employee->id, 'module' => 'monthly', 'year' => 2026, 'month' => 10, 'ptkp' => 'TK/0', 'method' => 'GROSS', 'salary' => 10000000, 'bonus' => 2000000, 'thr' => 5000000, 'tax_object_code' => '21-100-01', 'withholding_date' => '2026-10-31']);
    $payroll->transition($user, $company, $entry->payrollRun, 'review');
    $payroll->transition($user, $company, $entry->payrollRun, 'finalize');

    $count = $payroll->copyPrevious($user, $company, ['branch_id' => $branch->id, 'year' => 2026, 'month' => 11, 'module' => 'monthly']);
    $copy = PayrollEntry::latest('id')->firstOrFail();
    expect($count)->toBe(1);
    expect($copy->input['withholding_date'])->toBe('2026-11-30');
    expect($copy->input['bonus'])->toBe(0);
    expect($copy->input['thr'])->toBe(0);
    expect($copy->gross)->toBe(10000000);
});
test('database version check rejects an old object after another update', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $payroll = app(PayrollService::class);
    $data = ['branch_id' => $branch->id, 'employee_id' => $employee->id, 'module' => 'monthly', 'year' => 2026, 'month' => 11, 'ptkp' => 'TK/0', 'method' => 'GROSS', 'salary' => 10000000, 'tax_object_code' => '21-100-01', 'withholding_date' => '2026-11-30'];
    $entry = $payroll->saveEntry($user, $company, $data);
    $stale = PayrollEntry::findOrFail($entry->id);
    $payroll->saveEntry($user, $company, array_merge($data, ['version' => 1, 'salary' => 11000000]), $entry);

    expect(fn () => $payroll->saveEntry($user, $company, array_merge($data, ['version' => 1, 'salary' => 12000000]), $stale))->toThrow(ValidationException::class);
    expect($entry->fresh()->input['salary'])->toBe(11000000);
});

test('annual exports reject unclassified opening balances', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();
    $payroll = app(PayrollService::class);
    $entry = $payroll->saveEntry($user, $company, ['branch_id' => $branch->id, 'employee_id' => $employee->id, 'year' => 2026, 'month' => 12, 'module' => 'annual', 'method' => 'GROSS', 'ptkp' => 'TK/0', 'salary' => 10000000, 'tax_object_code' => '21-100-01', 'withholding_date' => '2026-12-31', 'opening_gross' => 110000000, 'opening_tax' => 2200000, 'opening_reason' => 'Saldo awal campuran belum dirinci']);
    $payroll->transition($user, $company, $entry->payrollRun, 'review');
    $payroll->transition($user, $company, $entry->payrollRun, 'finalize');

    expect(fn () => app(CoretaxExportService::class)->xml($user, $company, ['branch_id' => $branch->id, 'year' => 2026, 'month' => 12, 'module' => 'annual']))->toThrow(ValidationException::class);
});
