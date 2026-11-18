<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaxService
{
    public const CONTRACTOR_CODES = ['21-100-04', '21-100-05', '21-100-06', '21-100-07', '21-100-18', '21-100-19', '21-100-20', '21-100-21', '21-100-22', '21-100-23', '21-100-33', '21-100-34'];

    /** @param array<string,mixed> $input @param array<string,mixed> $result */
    public function objectCode(array $input, array $result): string
    {
        if ($input['module'] === 'contractor') {
            if (! in_array($input['tax_object_code'] ?? '', self::CONTRACTOR_CODES, true)) {
                throw ValidationException::withMessages(['tax_object_code' => 'Pilih kode bukan pegawai dengan dasar pengenaan 50% sesuai referensi BP21.']);
            }

            return $input['tax_object_code'];
        }

        return match ($input['module']) {
            'pension' => '21-100-02',
            'severance' => ($input['final_kind'] ?? 'SEVERANCE') === 'PENSION' ? '21-401-02' : '21-401-01',
            'daily' => ($input['daily_scheme'] ?? 'DAILY') === 'MONTHLY' ? '21-100-35' : ($result['gross'] > 2500000 * (int) ($input['work_days'] ?? 1) ? '21-100-30' : '21-100-24'),
            default => '21-100-01',
        };
    }

    public const MONEY_FIELDS = ['salary', 'allowance', 'bonus', 'thr', 'overtime', 'bpjs_company', 'pension_employee', 'jht_employee', 'health_employee', 'other_deductions', 'zakat', 'prior_gross', 'prior_tax', 'prior_pension', 'prior_zakat', 'final_prior_gross'];

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'module' => ['required', Rule::in(array_keys(config('taxcore.modules')))],
            'method' => ['required', Rule::in(['GROSS', 'GROSS_UP', 'NETT'])],
            'ptkp' => ['required', Rule::in(array_keys(config('tax.ptkp')))],
            'year' => ['required', 'integer', 'between:'.config('tax.first_year').','.config('tax.last_year')],
            'month' => ['required', 'integer', 'between:1,12'],
            'salary' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            'work_days' => ['nullable', 'integer', 'between:1,31'],
            'months_worked' => ['nullable', 'integer', 'between:1,12'],
            'daily_scheme' => ['nullable', Rule::in(['DAILY', 'MONTHLY'])],
            'final_kind' => ['nullable', Rule::in(['SEVERANCE', 'PENSION'])],
            'is_final_period' => ['nullable', 'boolean'],
            'opening_reason' => ['nullable', 'string', 'max:500'],
        ];
        foreach (self::MONEY_FIELDS as $field) {
            if ($field !== 'salary') {
                $rules[$field] = ['nullable', 'integer', 'min:0', 'max:1000000000000'];
            }
        }

        return $rules;
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function calculate(array $input): array
    {
        $data = Validator::make($input, $this->rules())->validate();
        foreach (self::MONEY_FIELDS as $field) {
            $data[$field] = (int) ($data[$field] ?? 0);
        }
        $data['work_days'] = (int) ($data['work_days'] ?? 1);
        $data['months_worked'] = (int) ($data['months_worked'] ?? $data['month']);
        if ($data['months_worked'] > $data['month']) {
            throw ValidationException::withMessages(['months_worked' => 'Jumlah bulan kerja melebihi masa pajak.']);
        }
        $module = $data['module'];
        $annual = $module === 'annual' || ($module === 'pension' && ($data['month'] == 12 || ($data['is_final_period'] ?? false)));
        if ($module === 'monthly' && $data['month'] == 12) {
            throw ValidationException::withMessages(['module' => 'Gunakan modul masa pajak terakhir untuk Desember.']);
        }
        $cash = $data['salary'] + $data['allowance'] + $data['bonus'] + $data['thr'] + $data['overtime'];
        if ($module === 'daily' && ($data['daily_scheme'] ?? 'DAILY') === 'DAILY') {
            $cash += $data['salary'] * ($data['work_days'] - 1);
        }
        $base = $cash + $data['bpjs_company'];
        [$ptkp,$category] = config('tax.ptkp.'.$data['ptkp']);
        $taxAllowance = 0;
        $taxFunction = fn (int $gross): int => $this->taxForGross($data, $gross, $category, $annual, $ptkp)['tax'];
        if ($data['method'] !== 'GROSS' && $taxFunction($base) > 0) {
            if (! $annual && in_array($module, ['monthly', 'pension'], true) || (! $annual && $module === 'daily' && ($data['daily_scheme'] ?? 'DAILY') === 'MONTHLY')) {
                $taxAllowance = $this->grossUpTer($base, $category);
            } else {
                for ($iteration = 0; $iteration < 128; $iteration++) {
                    $next = max(0, $taxFunction($base + $taxAllowance));
                    if ($next === $taxAllowance) {
                        break;
                    }
                    $taxAllowance = $next;
                }
            }
        }
        $gross = $base + $taxAllowance;
        $result = $this->taxForGross($data, $gross, $category, $annual, $ptkp);
        if ($data['method'] !== 'GROSS' && $taxAllowance > 0 && $taxAllowance !== $result['tax']) {
            throw ValidationException::withMessages(['method' => 'Gross-up belum mencapai nilai yang konsisten. Periksa batas tarif.']);
        }
        $deductions = $data['pension_employee'] + $data['jht_employee'] + $data['health_employee'] + $data['other_deductions'] + $data['zakat'];
        $takeHome = $cash - $deductions - ($data['method'] === 'GROSS' ? $result['tax'] : 0);
        if ($takeHome < 0) {
            throw ValidationException::withMessages(['salary' => 'Potongan melebihi penghasilan tunai.']);
        }

        return array_merge($result, [
            'gross' => $gross, 'cash_income' => $cash, 'tax_allowance' => $taxAllowance,
            'company_tax' => $data['method'] === 'GROSS' ? 0 : $result['tax'],
            'cash_deductions' => $deductions, 'take_home' => $takeHome, 'category' => $category,
            'is_annual' => $annual, 'ptkp_amount' => $ptkp, 'rule_version' => config('tax.version'),
            'method' => $data['method'], 'bpjs_company' => $data['bpjs_company'],
        ]);
    }

    /** @return array{max: ?int, basis_points: int} */
    public function bracket(string $category, int $gross): array
    {
        foreach (config('tax.ter.'.$category) as [$max,$basisPoints]) {
            if ($max === null || $gross <= $max) {
                return ['max' => $max, 'basis_points' => $basisPoints];
            }
        }
        throw new \LogicException('TER category unavailable.');
    }

    private function grossUpTer(int $base, string $category): int
    {
        foreach (config('tax.ter.'.$category) as [$max,$rate]) {
            $gross = intdiv($base * 10000, 10000 - $rate);
            foreach ([$gross, $gross + 1] as $candidate) {
                $actual = $this->bracket($category, $candidate)['basis_points'];
                $tax = intdiv($candidate * $actual, 10000);
                if ($actual === $rate && $candidate - $tax === $base) {
                    return $tax;
                }
            }
        }
        throw ValidationException::withMessages(['method' => 'Gross-up tidak memiliki solusi pada batas TER ini.']);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function taxForGross(array $data, int $gross, string $category, bool $annual, int $ptkp): array
    {
        if ($annual) {
            $annualGross = $data['prior_gross'] + $gross;
            $expense = min(intdiv($annualGross * 5, 100), $data['months_worked'] * ($data['module'] === 'pension' ? 200000 : 500000));
            $pension = $data['module'] === 'pension' ? 0 : $data['prior_pension'] + $data['pension_employee'] + $data['jht_employee'];
            $pkp = intdiv(max(0, $annualGross - $expense - $pension - $data['prior_zakat'] - $data['zakat'] - $ptkp), 1000) * 1000;
            $progressive = $this->progressive($pkp);

            return ['tax' => $progressive['tax'] - $data['prior_tax'], 'annual_tax' => $progressive['tax'], 'annual_gross' => $annualGross, 'prior_tax' => $data['prior_tax'], 'expense' => $expense, 'pkp' => $pkp, 'deductible_pension' => $pension, 'rate_basis_points' => 0, 'layers' => $progressive['layers']];
        }
        if ($data['module'] === 'severance') {
            $prior = $data['final_prior_gross'];
            $brackets = ($data['final_kind'] ?? 'SEVERANCE') === 'PENSION' ? [[50000000, 0], [null, 500]] : [[50000000, 0], [100000000, 500], [500000000, 1500], [null, 2500]];
            $total = $this->progressive($gross + $prior, $brackets);
            $previous = $this->progressive($prior, $brackets);

            return ['tax' => $total['tax'] - $previous['tax'], 'rate_basis_points' => 0, 'layers' => $total['layers'], 'pkp' => $gross, 'annual_gross' => 0, 'annual_tax' => 0, 'prior_tax' => $previous['tax'], 'expense' => 0];
        }
        if ($data['module'] === 'contractor' || ($data['module'] === 'daily' && ($data['daily_scheme'] ?? 'DAILY') === 'DAILY')) {
            if ($data['module'] === 'daily') {
                $days = $data['work_days'];
                if ($gross <= 450000 * $days) {
                    return $this->flat($gross, 0);
                }
                if ($gross <= 2500000 * $days) {
                    return $this->flat($gross, 50);
                }
            }
            // Keep the half-rupiah base exact until the final tax rounding.
            $progressive = $this->progressive($gross, [[120000000, 250], [500000000, 750], [1000000000, 1250], [10000000000, 1500], [null, 1750]]);

            return ['tax' => $progressive['tax'], 'rate_basis_points' => 0, 'layers' => $progressive['layers'], 'pkp' => intdiv($gross, 2), 'annual_gross' => 0, 'annual_tax' => 0, 'prior_tax' => 0, 'expense' => 0];
        }

        return $this->flat($gross, $this->bracket($category, $gross)['basis_points']);
    }

    /** @return array<string, mixed> */
    private function flat(int $gross, int $rate): array
    {
        return ['tax' => intdiv($gross * $rate, 10000), 'rate_basis_points' => $rate, 'layers' => [], 'pkp' => $gross, 'annual_gross' => 0, 'annual_tax' => 0, 'prior_tax' => 0, 'expense' => 0];
    }

    /** @param array<int, array{0: ?int, 1: int}>|null $brackets @return array{tax: int, layers: array<int, array<string, int>>} */
    public function progressive(int $amount, ?array $brackets = null): array
    {
        $brackets ??= [[60000000, 500], [250000000, 1500], [500000000, 2500], [5000000000, 3000], [null, 3500]];
        $previous = 0;
        $numerator = 0;
        $layers = [];
        foreach ($brackets as [$max,$rate]) {
            $slice = max(0, min($amount, $max ?? $amount) - $previous);
            if ($slice > 0) {
                $numerator += $slice * $rate;
                $layers[] = ['base' => $slice, 'basis_points' => $rate, 'tax' => intdiv($slice * $rate, 10000)];
            }
            $previous = $max ?? $amount;
        }

        return ['tax' => intdiv($numerator, 10000), 'layers' => $layers];
    }
}
