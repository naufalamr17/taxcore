<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CoretaxExportService
{
    public function __construct(private AccessService $access, private SpreadsheetService $spreadsheets, private TaxService $tax) {}

    /** @param array<string,mixed> $selection @return Collection<int,PayrollRun> */
    public function runs(User $user, Company $company, array $selection): Collection
    {
        Gate::forUser($user)->authorize('payroll.export');
        $this->access->company($user, $company->id);
        $runs = $company->payrollRuns()->with('entries')->whereHas('entries')->where('branch_id', $selection['branch_id'])->where('year', $selection['year'])->where('module', $selection['module'])->when(! empty($selection['month']), fn ($q) => $q->where('month', $selection['month']))->orderBy('month')->get();
        if ($runs->isEmpty()) {
            throw ValidationException::withMessages(['export' => 'Tidak ada data pada periode yang dipilih.']);
        }
        if ($runs->contains(fn ($run) => $run->status !== 'Finalized')) {
            throw ValidationException::withMessages(['export' => 'Finalisasi seluruh periode terpilih sebelum ekspor.']);
        }
        if ($runs->pluck('company_snapshot.npwp')->unique()->count() !== 1) {
            throw ValidationException::withMessages(['export' => 'NPWP snapshot berbeda. Ekspor setiap masa secara terpisah.']);
        }

        return $runs;
    }

    /** @param array<string,mixed> $selection */
    public function excel(User $user, Company $company, array $selection): string
    {
        $runs = $this->runs($user, $company, $selection);
        $documents = $this->documents($runs);
        $family = $documents[0]['family'];
        $tin = (string) $runs->first()->company_snapshot['npwp'];
        if ($family === 'MmPayroll') {
            $rows = [['NPWP Pemotong', $tin], [], ['', 'Masa Pajak', 'Tahun Pajak', 'Status Pegawai', 'NPWP/NIK/TIN', 'Nomor Passport', 'Status', 'Posisi', 'Sertifikat/Fasilitas', 'Kode Objek Pajak', 'Penghasilan Kotor', 'Tarif', 'ID TKU', 'Tgl Pemotongan', '', 'TER A', 'TER B', 'TER C']];
            foreach ($documents as $document) {
                $f = $document['fields'];
                $rows[] = ['', $f['TaxPeriodMonth'], $f['TaxPeriodYear'], 'Resident', (string) $f['CounterpartTin'], '', $f['StatusTaxExemption'], $f['Position'], 'N/A', $f['TaxObjectCode'], $f['Gross'], $f['Rate'], (string) $f['IDPlaceOfBusinessActivity'], CarbonImmutable::parse($f['WithholdingDate'])->format('d/m/Y'), '', $this->tax->bracket('A', $f['Gross'])['basis_points'] / 100, $this->tax->bracket('B', $f['Gross'])['basis_points'] / 100, $this->tax->bracket('C', $f['Gross'])['basis_points'] / 100];
            }
        } elseif ($family === 'Bp21') {
            $rows = [['NPWP Pemotong', '', $tin], [], ['', 'Masa Pajak', 'Tahun Pajak', 'NPWP', 'ID TKU Penerima Penghasilan', 'Status PTKP', 'Fasilitas', 'Kode Objek Pajak', 'Penghasilan', 'Deemed', 'Tarif', 'Jenis Dok. Referensi', 'Nomor Dok. Referensi', 'Tanggal Dok. Referensi', 'ID TKU Pemotong', 'Tanggal Pemotongan']];
            foreach ($documents as $document) {
                $rows[] = array_merge([''], array_values($document['fields']));
            }
        } else {
            $rows = [['NPWP Pemotong', $tin], [], array_keys($documents[0]['fields'])];
            foreach ($documents as $document) {
                $rows[] = array_values($document['fields']);
            }
        }
        $this->access->audit($user, $company->id, 'export.xlsx', 'payroll', ['selection' => $selection, 'family' => $family, 'rows' => count($documents)]);

        return $this->spreadsheets->write($rows, $family === 'MmPayroll' ? 'Coretax_Import' : 'Coretax_'.$family, 3);
    }

    /** @param array<string,mixed> $selection */
    public function xml(User $user, Company $company, array $selection): string
    {
        $runs = $this->runs($user, $company, $selection);
        $documents = $this->documents($runs);
        $family = $documents[0]['family'];
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);
        $xml->startElement($family.'Bulk');
        $xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->writeElement('TIN', (string) $runs->first()->company_snapshot['npwp']);
        $xml->startElement('ListOf'.$family);
        foreach ($documents as $document) {
            $xml->startElement($family);
            foreach ($document['fields'] as $name => $value) {
                if ($value === null) {
                    $xml->startElement($name);
                    $xml->writeAttribute('xsi:nil', 'true');
                    $xml->endElement();
                } else {
                    $xml->writeElement($name, (string) $value);
                }
            }
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();
        $this->access->audit($user, $company->id, 'export.xml', 'payroll', ['selection' => $selection, 'family' => $family, 'rows' => count($documents)]);

        return $xml->outputMemory();
    }

    /** @param Collection<int,PayrollRun> $runs @return array<int,array{family:string,fields:array<string,mixed>}> */
    private function documents(Collection $runs): array
    {
        $documents = [];
        foreach ($runs as $run) {
            foreach ($run->entries as $entry) {
                $documents[] = $this->document($run, $entry);
            }
        }
        if (count(array_unique(array_column($documents, 'family'))) !== 1) {
            throw ValidationException::withMessages(['export' => 'Pisahkan masa terakhir dari masa TER untuk ekspor.']);
        }

        return $documents;
    }

    /** @return array{family:string,fields:array<string,mixed>} */
    private function document(PayrollRun $run, PayrollEntry $entry): array
    {
        $input = $entry->input;
        $result = $entry->result;
        $snapshot = $run->company_snapshot;
        $family = $result['is_annual'] ? 'A1' : (in_array($run->module, ['monthly', 'pension'], true) ? 'MmPayroll' : 'Bp21');
        if ($family === 'A1') {
            if ((int) ($input['opening_gross'] ?? 0) > 0 && ! ($input['opening_salary_only'] ?? false)) {
                throw ValidationException::withMessages(['export' => 'Rincian saldo awal belum cukup untuk BPA1. Konfirmasi jika seluruh bruto awal berupa gaji, atau impor rincian bulan sebelumnya untuk komponen campuran.']);
            }
            $parts = $input['annual_components'];
            $fields = [
                'WorkForSecondEmployer' => 'No', 'TaxPeriodMonthStart' => (int) $run->month - (int) $input['months_worked'] + 1,
                'TaxPeriodMonthEnd' => $run->month, 'TaxPeriodYear' => $run->year, 'CounterpartOpt' => 'Resident', 'CounterpartPassport' => null,
                'CounterpartTin' => $input['nik'], 'TaxExemptOpt' => $input['ptkp'],
                'StatusOfWithholding' => $run->month === 12 && (int) $input['months_worked'] === 12 ? 'FullYear' : 'PartialYear',
                'CounterpartPosition' => $input['position'], 'TaxObjectCode' => $input['tax_object_code'], 'NumberOfMonths' => 0,
                'SalaryPensionJhtTht' => $parts['salary'], 'GrossUpOpt' => $input['method'] === 'GROSS' ? 'No' : 'Yes',
                'IncomeTaxBenefit' => $parts['prior_tax_allowance'] + $result['tax_allowance'], 'OtherBenefit' => $parts['allowance'] + $parts['overtime'],
                'Honorarium' => 0, 'InsurancePaidByEmployer' => $parts['bpjs_company'], 'Natura' => 0, 'TantiemBonusThr' => $parts['bonus'] + $parts['thr'],
                'PensionContributionJhtThtFee' => $result['deductible_pension'], 'Zakat' => $input['prior_zakat'] + (int) ($input['zakat'] ?? 0),
                'PrevWhTaxSlip' => null, 'TaxCertificate' => 'N/A', 'Article21IncomeTax' => $result['prior_tax'],
            ];
        } elseif ($family === 'MmPayroll') {
            $fields = ['TaxPeriodMonth' => $run->month, 'TaxPeriodYear' => $run->year, 'CounterpartOpt' => 'Resident', 'CounterpartPassport' => null,
                'CounterpartTin' => $input['nik'], 'StatusTaxExemption' => $input['ptkp'], 'Position' => $input['position'], 'TaxCertificate' => 'N/A',
                'TaxObjectCode' => $input['tax_object_code'], 'Gross' => $entry->gross, 'Rate' => $result['rate_basis_points'] / 100];
        } else {
            if (empty($input['recipient_nitku']) || empty($input['document_number']) || empty($input['document_date'])) {
                throw ValidationException::withMessages(['export' => 'BP21 memerlukan NITKU penerima, nomor dokumen, dan tanggal dokumen pada setiap baris.']);
            }
            $deemed = ($run->module === 'contractor' || ($run->module === 'daily' && $input['tax_object_code'] === '21-100-30')) ? 50 : 100;
            $rate = $result['rate_basis_points'] / 100;
            if ($result['layers']) {
                $layer = $result['layers'][array_key_last($result['layers'])];
                $rate = $layer['basis_points'] / 100 * ($deemed === 50 ? 2 : 1);
            }
            $fields = ['TaxPeriodMonth' => $run->month, 'TaxPeriodYear' => $run->year, 'CounterpartTin' => $input['nik'],
                'IDPlaceOfBusinessActivityOfIncomeRecipient' => $input['recipient_nitku'], 'StatusTaxExemption' => $input['ptkp'], 'TaxCertificate' => 'N/A',
                'TaxObjectCode' => $input['tax_object_code'], 'Gross' => $entry->gross, 'Deemed' => $deemed, 'Rate' => $rate,
                'Document' => 'PaymentProof', 'DocumentNumber' => $input['document_number'], 'DocumentDate' => $input['document_date']];
        }
        $fields += ['IDPlaceOfBusinessActivity' => $snapshot['nitku'], 'WithholdingDate' => $input['withholding_date']];

        return ['family' => $family, 'fields' => $fields];
    }
}
