<?php

use App\Jobs\CommitPayrollImport;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\User;
use App\Services\CoretaxExportService;
use App\Services\PayrollImportService;
use App\Services\PayrollService;
use App\Services\SpreadsheetService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);
function importRows(int $count = 1): array
{
    $rows = [['Masa_Pajak', 'Tahun_Pajak', 'NIK', 'Nama', 'Jabatan', 'PTKP', 'Metode_Pajak', 'Tanggal_Masuk', 'Gaji_Pokok', 'Kode_Objek_Pajak']];
    for ($index = 1; $index <= $count; $index++) {
        $rows[] = ['11', '2026', str_pad((string) $index, 16, '0', STR_PAD_LEFT), 'Pegawai '.$index, 'Staff', 'TK/0', 'GROSS', '2026-01-01', '10000000', '21-100-01'];
    }

    return $rows;
}
test('XLSX round trip preserves leading zero identifiers and neutralizes formulas', function (): void {
    $sheets = app(SpreadsheetService::class);
    $path = tempnam(sys_get_temp_dir(), 'taxcore-test-');
    try {
        file_put_contents($path, $sheets->write([['NIK', 'Nama', 'Gaji'], ['0000000000000001', '=HYPERLINK("https://example.test")', 10000000]]));
        $rows = $sheets->read($path, 'xlsx');
        expect($rows[1])->toBe(['0000000000000001', '=HYPERLINK("https://example.test")', '10000000']);
    } finally {
        unlink($path);
    }
});
test('CSV parser handles quoted delimiters and embedded newlines', function (): void {
    $rows = app(SpreadsheetService::class)->delimited("Nama,NIK\n\"Nama, lengkap\",0000000000000001\n\"Nama\nlanjutan\",0000000000000002");
    expect($rows[1][0])->toBe('Nama, lengkap');
    expect($rows[2][0])->toBe("Nama\nlanjutan");
});

test('sparse XLSX columns preserve payroll header positions without allocating empty cells', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $source = importRows();
    foreach ($source as &$row) {
        $row[16383] = $row[8];
        unset($row[8]);
    }
    unset($row);
    $sheets = app(SpreadsheetService::class);
    $path = tempnam(sys_get_temp_dir(), 'taxcore-sparse-');
    try {
        file_put_contents($path, $sheets->write($source));

        $rows = $sheets->read($path, 'xlsx');
        $batch = app(PayrollImportService::class)->preview($user, $company, $branch->id, 'monthly', $rows);

        expect($rows[1])->toHaveCount(10);
        expect($rows[1][16383])->toBe('10000000');
        expect($batch->errors)->toBe([]);
        expect($batch->records[0]['salary'])->toBe('10000000');
    } finally {
        unlink($path);
    }
});

test('XLSX imports reject invalid worksheet coordinates before allocating rows', function (string $rowReference, string $cellReference, string $message): void {
    $path = tempnam(sys_get_temp_dir(), 'taxcore-coordinate-');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="'.$rowReference.'"><c r="'.$cellReference.'"><v>1</v></c></row></sheetData></worksheet>');
    $zip->close();
    try {
        expect(fn () => app(SpreadsheetService::class)->read($path, 'xlsx'))->toThrow(ValidationException::class, $message);
    } finally {
        unlink($path);
    }
})->with([
    'huge column' => ['1', 'ZZZZZZZZZZZZ1', 'Referensi sel XLSX tidak valid.'],
    'past final Excel column' => ['1', 'XFE1', 'Referensi sel XLSX tidak valid.'],
    'zero cell row' => ['1', 'A0', 'Referensi sel XLSX tidak valid.'],
    'cell outside containing row' => ['1', 'A2', 'Referensi sel XLSX tidak valid.'],
    'malformed cell' => ['1', 'A1trailing', 'Referensi sel XLSX tidak valid.'],
    'zero worksheet row' => ['0', 'A1', 'Referensi baris XLSX tidak valid.'],
    'past final Excel row' => ['1048577', 'A1048577', 'Referensi baris XLSX tidak valid.'],
    'oversized worksheet row' => ['999999999999999999999', 'A1', 'Referensi baris XLSX tidak valid.'],
]);
test('malformed and entity bearing XLSX files are rejected', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'taxcore-test-');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('xl/worksheets/sheet1.xml', '<!DOCTYPE test [<!ENTITY ext SYSTEM "file:///etc/passwd">]><worksheet>&ext;</worksheet>');
    $zip->close();
    try {
        expect(fn () => app(SpreadsheetService::class)->read($path, 'xlsx'))->toThrow(ValidationException::class);
    } finally {
        unlink($path);
    }
});
test('import preview reports bad rows and duplicate identities', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $rows = importRows();
    $rows[] = $rows[1];
    $rows[1][8] = '-1000';

    $batch = app(PayrollImportService::class)->preview($user, $company, $branch->id, 'monthly', $rows);
    expect($batch->errors)->toHaveCount(2);
    expect(PayrollEntry::count())->toBe(0);
});
test('import failure rolls back all employee and payroll changes', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $imports = app(PayrollImportService::class);
    $batch = $imports->preview($user, $company, $branch->id, 'monthly', importRows());
    $batch->update(['status' => 'Queued']);
    $imports->commit($batch);
    $batch2 = $imports->preview($user, $company, $branch->id, 'monthly', array_merge([importRows()[0]], [importRows(2)[2], importRows()[1]]));
    $batch2->update(['status' => 'Queued']);

    expect(fn () => $imports->commit($batch2))->toThrow(ValidationException::class);
    expect(PayrollEntry::count())->toBe(1);
    expect(Employee::count())->toBe(1);
});
test('large imports are queued after confirmation', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $batch = app(PayrollImportService::class)->preview($user, $company, $branch->id, 'monthly', importRows(201));
    Queue::fake([CommitPayrollImport::class]);

    $this->actingAs($user)->post(route('imports.commit', [$company, $batch]))->assertRedirect();
    Queue::assertPushed(CommitPayrollImport::class, fn ($job) => $job->batchId === $batch->id);
    expect($batch->fresh()->status)->toBe('Queued');
    expect(PayrollEntry::count())->toBe(0);
});
test('queued imports recheck user permissions before writing', function (): void {
    $user = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $user->companies()->attach($company);
    $batch = app(PayrollImportService::class)->preview($user, $company, $branch->id, 'monthly', importRows());
    $batch->update(['status' => 'Queued']);
    $user->update(['is_active' => false]);

    expect(fn () => (new CommitPayrollImport($batch->id))->handle(app(PayrollImportService::class)))->toThrow(AuthorizationException::class);
    expect(PayrollEntry::count())->toBe(0);
});
test('a thousand payroll rows import and remain available for full-period totals', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $imports = app(PayrollImportService::class);
    $batch = $imports->preview($user, $company, $branch->id, 'monthly', importRows(1000));
    $batch->update(['status' => 'Queued']);

    $imports->commit($batch);
    expect($batch->fresh()->status)->toBe('Completed');
    expect(PayrollEntry::count())->toBe(1000);
    expect((int) PayrollEntry::sum('tax'))->toBe(200000000);
    $this->actingAs($user)->get(route('payroll.index', ['company' => $company, 'branch_id' => $branch->id, 'year' => 2026, 'month' => 11, 'per_page' => 25]))->assertOk()
        ->assertViewHas('bootstrap', fn (array $state): bool => count($state['employees']) === 1000 && array_sum(array_column($state['employees'], 'gajiPokok')) === 10000000000);
});
test('exports use finalized identity snapshots and the official monthly XML structure', function (): void {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN']);
    $company = Company::factory()->create(['npwp' => '0000000000000001']);
    $branch = Branch::factory()->for($company)->create(['code' => '000000']);
    $employee = Employee::factory()->for($company)->create(['nik' => '0000000000000002', 'name' => 'Name & <escaped>']);
    $input = ['branch_id' => $branch->id, 'employee_id' => $employee->id, 'year' => 2026, 'month' => 11, 'module' => 'monthly', 'method' => 'GROSS', 'ptkp' => 'TK/0', 'salary' => 10000000, 'tax_object_code' => '21-100-01', 'withholding_date' => '2026-11-30'];
    $payroll = app(PayrollService::class);
    $entry = $payroll->saveEntry($user, $company, $input);
    $selection = ['branch_id' => $branch->id, 'year' => 2026, 'month' => 11, 'module' => 'monthly'];
    $exports = app(CoretaxExportService::class);
    expect(fn () => $exports->xml($user, $company, $selection))->toThrow(ValidationException::class);
    $payroll->transition($user, $company, $entry->payrollRun, 'review');
    $payroll->transition($user, $company, $entry->payrollRun, 'finalize');
    $company->update(['npwp' => '9999999999999999']);

    $xml = simplexml_load_string($exports->xml($user, $company, $selection));
    expect($xml->getName())->toBe('MmPayrollBulk');
    expect((string) $xml->TIN)->toBe('0000000000000001');
    expect((string) $xml->ListOfMmPayroll->MmPayroll->CounterpartTin)->toBe('0000000000000002');
    expect((string) $xml->ListOfMmPayroll->MmPayroll->Rate)->toBe('2');
    $path = tempnam(sys_get_temp_dir(), 'taxcore-export-');
    try {
        file_put_contents($path, $exports->excel($user, $company, $selection));
        $rows = app(SpreadsheetService::class)->read($path, 'xlsx');
        expect($rows[0][1])->toBe('0000000000000001');
        expect($rows[1][1])->toBe('Masa Pajak');
        expect($rows[2][4])->toBe('0000000000000002');
        expect($rows[2][12])->toBe('0000000000000001000000');
    } finally {
        unlink($path);
    }
    expect(fn () => $exports->xml($user, $company, array_merge($selection, ['month' => 10])))->toThrow(ValidationException::class);
});
