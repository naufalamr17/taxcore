<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PayrollEntry;
use App\Services\AccessService;
use App\Services\JournalService;
use App\Services\PayrollService;
use App\Services\TaxService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SimulatorController extends Controller
{
    public function __construct(private JournalService $journal) {}

    public function index(Request $request, Company $company, AccessService $access): View
    {
        $access->company($request->user(), $company->id);
        $selection = $request->validate(['module' => ['nullable', Rule::in(array_keys(config('taxcore.modules')))], 'entry' => ['nullable', 'integer'], 'year' => ['nullable', 'integer', 'between:2024,2026'], 'month' => ['nullable', 'integer', 'between:1,12']]);
        $entry = isset($selection['entry']) ? PayrollEntry::whereHas('payrollRun', fn ($query) => $query->where('company_id', $company->id))->findOrFail($selection['entry']) : null;
        $data = $entry ? array_merge($entry->input, ['version' => $entry->version]) : $selection + ['module' => 'monthly', 'method' => 'GROSS', 'ptkp' => 'TK/0', 'year' => 2026, 'month' => ($selection['module'] ?? '') === 'annual' ? 12 : (int) now()->month, 'salary' => 0];

        return $this->workspace($request, $company, $data, $entry?->result, $entry);
    }

    public function calculate(Request $request, Company $company, AccessService $access, TaxService $tax, PayrollService $payroll): View
    {
        $access->company($request->user(), $company->id);
        $rules = $tax->rules() + [
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('company_id', $company->id)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('company_id', $company->id)],
            'entry_id' => ['nullable', 'integer'], 'version' => ['nullable', 'integer'],
            'opening_salary_only' => ['nullable', 'boolean'],
            'tax_object_code' => ['nullable', 'regex:/^21-[0-9]{3}-[0-9]{2}$/'],
            'withholding_date' => ['nullable', 'date'], 'final_start_year' => ['nullable', 'integer', 'between:2020,2026'],
            'recipient_nitku' => ['nullable', 'digits:22'], 'document_number' => ['nullable', 'string', 'max:100'], 'document_date' => ['nullable', 'date'],
        ];
        foreach (['gross', 'tax', 'pension', 'zakat'] as $field) {
            $rules['opening_'.$field] = ['nullable', 'integer', 'between:0,1000000000000'];
        }
        $data = $request->validate($rules);
        $entry = isset($data['entry_id']) ? PayrollEntry::whereHas('payrollRun', fn ($query) => $query->where('company_id', $company->id))->findOrFail($data['entry_id']) : null;
        if (! empty($data['employee_id'])) {
            $employee = $company->employees()->findOrFail($data['employee_id']);
            $calculated = $payroll->preview($request->user(), $company, $employee, $data);
            $data = array_merge($data, $calculated['context'], $employee->only(['name', 'nik', 'position']));
            $result = $calculated['result'];
        } else {
            $result = $tax->calculate($data);
        }

        return $this->workspace($request, $company, $data, $result, $entry);
    }

    /** @param array<string, mixed> $data @param array<string, mixed>|null $result */
    private function workspace(Request $request, Company $company, array $data, ?array $result, ?PayrollEntry $entry): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'all_modules' => ['nullable', 'boolean'], 'all_months' => ['nullable', 'boolean'], 'branch_id' => ['nullable', 'integer'], 'per_page' => ['nullable', Rule::in([25, 50, 100, 250, 500])]]);
        $branches = $company->branches()->orderBy('code')->get();
        $branchId = (int) ($data['branch_id'] ?? $filters['branch_id'] ?? $branches->first()?->id);
        $company->branches()->findOrFail($branchId);
        $data['branch_id'] = $branchId;
        $query = PayrollEntry::whereHas('payrollRun', fn ($query) => $query->where('company_id', $company->id)->where('branch_id', $branchId)->where('year', $data['year'])->when(empty($filters['all_months']), fn ($query) => $query->where('month', $data['month']))->when(empty($filters['all_modules']), fn ($query) => $query->where('module', $data['module'])))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->whereHas('employee', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('nik', 'like', '%'.$search.'%')->orWhere('position', 'like', '%'.$search.'%'))));
        $totals = array_fill_keys(['count', 'salary', 'allowance', 'bonus', 'thr', 'overtime', 'bpjs_company', 'gross', 'tax', 'take_home'], 0);
        foreach ((clone $query)->select(['id', 'input', 'gross', 'tax', 'take_home'])->lazyById(500) as $saved) {
            $totals['count']++;
            foreach (['salary', 'allowance', 'bonus', 'thr', 'overtime', 'bpjs_company'] as $field) {
                $totals[$field] += (int) ($saved->input[$field] ?? 0);
            }
            foreach (['gross', 'tax', 'take_home'] as $field) {
                $totals[$field] += $saved->{$field};
            }
        }
        $entries = $query->with(['employee', 'payrollRun'])->orderBy('id')->paginate((int) ($filters['per_page'] ?? 50))->withQueryString();

        return view('tax.simulator', ['company' => $company, 'data' => $data, 'entry' => $entry, 'result' => $result, 'journal' => $result ? $this->journal->entries($result) : [], 'branches' => $branches, 'entries' => $entries, 'filters' => $filters, 'totals' => $totals]);
    }
}
