<?php

use App\Services\JournalService;
use App\Services\TaxService;
use Illuminate\Validation\ValidationException;

function taxInput(array $changes = []): array
{
    return array_merge(['module' => 'monthly', 'method' => 'GROSS', 'ptkp' => 'TK/0', 'year' => 2026, 'month' => 11, 'salary' => 10000000], $changes);
}

test('all TER thresholds agree with the official DJP converter', function (): void {
    $stream = fopen(__DIR__.'/../Fixtures/coretax/ter-official.csv', 'r');
    fgetcsv($stream, 0, ',', '"', '');
    $previous = [];
    $tax = app(TaxService::class);
    while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
        [$category,$upper,$rate] = $row;
        $lower = ($previous[$category] ?? -1) + 1;
        expect($tax->bracket($category, $lower)['basis_points'])->toBe((int) $rate);
        if ($upper !== 'null') {
            expect($tax->bracket($category, (int) $upper)['basis_points'])->toBe((int) $rate);
            $previous[$category] = (int) $upper;
        }
    }
    fclose($stream);
});
test('official monthly TER examples and category thresholds', function (string $ptkp, int $salary, int $expected): void {
    $result = app(TaxService::class)->calculate(taxInput(['ptkp' => $ptkp, 'salary' => $salary]));
    expect($result['tax'])->toBe($expected);
})->with([
    ['TK/0', 5400000, 0], ['TK/0', 5400001, 13500], ['TK/0', 10000000, 200000],
    ['K/1', 6200000, 0], ['K/1', 6200001, 15500], ['K/3', 6600000, 0], ['K/3', 6600001, 16500],
    ['K/0', 6000000, 45000], ['K/0', 8000000, 120000], ['K/0', 9000000, 157500],
]);
test('monthly contributions reduce cash but not the TER gross base', function (): void {
    $result = app(TaxService::class)->calculate(taxInput(['pension_employee' => 100000, 'jht_employee' => 200000, 'health_employee' => 100000]));
    expect($result['tax'])->toBe(200000);
    expect($result['take_home'])->toBe(9400000);
});
test('gross up protects cash income and equals tax on actual gross', function (string $method): void {
    $result = app(TaxService::class)->calculate(taxInput(['method' => $method]));
    expect($result['gross'])->toBe(10230179);
    expect($result['tax'])->toBe(230179);
    expect($result['tax_allowance'])->toBe($result['tax']);
    expect($result['take_home'])->toBe(10000000);
})->with(['GROSS_UP', 'NETT']);
test('final period matches the official annual example', function (): void {
    $result = app(TaxService::class)->calculate(taxInput(['module' => 'annual', 'ptkp' => 'K/0', 'month' => 12, 'months_worked' => 12, 'prior_gross' => 110000000, 'prior_tax' => 2200000, 'prior_pension' => 1100000, 'pension_employee' => 100000]));
    expect($result['annual_tax'])->toBe(2715000);
    expect($result['pkp'])->toBe(54300000);
    expect($result['tax'])->toBe(515000);
    expect($result['take_home'])->toBe(9385000);
});
test('final period retains overpayment refunds', function (): void {
    $result = app(TaxService::class)->calculate(taxInput(['module' => 'annual', 'month' => 12, 'salary' => 5000000, 'prior_gross' => 55000000, 'prior_tax' => 1000000, 'months_worked' => 12]));
    expect($result['annual_tax'])->toBe(150000);
    expect($result['tax'])->toBe(-850000);
    expect($result['take_home'])->toBe(5850000);
});
test('pension final period applies the pension expense cap', function (): void {
    $result = app(TaxService::class)->calculate(taxInput(['module' => 'pension', 'month' => 12, 'prior_gross' => 110000000, 'prior_tax' => 2200000, 'months_worked' => 12]));
    expect($result['expense'])->toBe(2400000);
    expect($result['annual_tax'])->toBe(3540000);
    expect($result['tax'])->toBe(1340000);
});
test('daily and nonemployee calculations use the correct basis', function (array $changes, int $expected): void {
    $result = app(TaxService::class)->calculate(taxInput($changes));
    expect($result['tax'])->toBe($expected);
})->with([
    [['module' => 'daily', 'salary' => 450000, 'work_days' => 10], 0],
    [['module' => 'daily', 'salary' => 500000, 'work_days' => 10], 25000],
    [['module' => 'daily', 'salary' => 3000000, 'work_days' => 1], 75000],
    [['module' => 'contractor', 'salary' => 45000000], 1125000],
    [['module' => 'contractor', 'salary' => 300000000], 16500000],
    [['module' => 'severance', 'salary' => 150000000], 10000000],
    [['module' => 'severance', 'final_kind' => 'PENSION', 'salary' => 100000000], 2500000],
    [['module' => 'severance', 'salary' => 30000000, 'final_prior_gross' => 40000000], 1000000],
]);
test('gross up handles progressive and annual methods', function (array $changes): void {
    $result = app(TaxService::class)->calculate(taxInput($changes + ['method' => 'GROSS_UP']));
    expect($result['tax_allowance'])->toBe($result['tax']);
    expect($result['take_home'])->toBe((int) ($changes['salary'] ?? 10000000));
})->with([
    [['module' => 'contractor', 'salary' => 300000000]],
    [['module' => 'severance', 'salary' => 150000000]],
    [['module' => 'daily', 'salary' => 500000, 'work_days' => 1]],
    [['module' => 'annual', 'month' => 12, 'salary' => 10000000, 'months_worked' => 12, 'prior_gross' => 110000000, 'prior_tax' => 2200000]],
]);
test('journals balance for all tax methods including refunds', function (array $changes): void {
    $result = app(TaxService::class)->calculate(taxInput($changes));
    $journal = app(JournalService::class)->entries($result);
    expect(array_sum(array_column($journal, 'debit')))->toBe(array_sum(array_column($journal, 'credit')));
})->with([
    [[]], [['method' => 'GROSS_UP', 'bpjs_company' => 400000, 'jht_employee' => 200000]],
    [['method' => 'NETT', 'bpjs_company' => 400000]],
    [['module' => 'annual', 'month' => 12, 'salary' => 5000000, 'prior_gross' => 55000000, 'prior_tax' => 1000000, 'months_worked' => 12]],
]);
test('unsupported years negative money and monthly December are rejected', function (array $changes): void {
    expect(fn () => app(TaxService::class)->calculate(taxInput($changes)))->toThrow(ValidationException::class);
})->with([[['year' => 2019]], [['year' => 2036]], [['salary' => -1]], [['month' => 12]], [['other_deductions' => 20000000]]]);
