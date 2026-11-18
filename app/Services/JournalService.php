<?php

namespace App\Services;

class JournalService
{
    /** @param array<string, mixed> $result @return array<int, array{account:string, debit:int, credit:int}> */
    public function entries(array $result): array
    {
        $lines = [
            ['6101 Beban Gaji', $result['cash_income']],
            ['6102 Beban Tunjangan Pajak', $result['company_tax']],
            ['6105 Beban BPJS Perusahaan', $result['bpjs_company']],
            ['2104 Utang PPh 21', -$result['tax']],
            ['2106 Utang Potongan & BPJS', -($result['cash_deductions'] + $result['bpjs_company'])],
            ['1102 Bank / Utang Gaji', -$result['take_home']],
        ];
        $rows = [];
        foreach ($lines as [$account,$signed]) {
            if ($signed !== 0) {
                $rows[] = ['account' => $account, 'debit' => max(0, $signed), 'credit' => max(0, -$signed)];
            }
        }
        if (array_sum(array_column($rows, 'debit')) !== array_sum(array_column($rows, 'credit'))) {
            throw new \LogicException('Jurnal payroll tidak seimbang.');
        }

        return $rows;
    }
}
