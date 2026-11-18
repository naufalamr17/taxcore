<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\PayrollEntry;
use App\Services\AccessService;
use App\Services\TaxService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceController extends Controller
{
    public function __construct(private AccessService $access) {}

    public function dashboard(Request $request): RedirectResponse
    {
        $company = $this->access->companies($request->user())->find($request->session()->get('active_company_id'));

        return $company ? redirect()->route('payroll.index', $company) : redirect()->route('companies.index');
    }

    public function index(Request $request, Company $company): View
    {
        $this->access->company($request->user(), $company->id);
        $selection = $request->validate(['year' => ['nullable', 'integer', 'between:2024,2026'], 'month' => ['nullable', 'integer', 'between:0,12'], 'module' => ['nullable', Rule::in(array_keys(config('taxcore.modules')))], 'branch_id' => ['nullable', 'integer'], 'q' => ['nullable', 'string', 'max:100'], 'per_page' => ['nullable', Rule::in([25, 50, 100, 250, 500])]]);
        $selection += ['year' => min(2026, (int) now()->year), 'month' => (int) now()->month, 'module' => 'monthly', 'q' => '', 'per_page' => 50];
        $branches = $company->branches()->orderBy('code')->get();
        $selection['branch_id'] = (int) ($selection['branch_id'] ?? $branches->first()?->id);
        $company->branches()->findOrFail($selection['branch_id']);
        $runs = $company->payrollRuns()->where('branch_id', $selection['branch_id'])->where('year', $selection['year'])->where('module', $selection['module'])->when($selection['month'], fn ($query) => $query->where('month', $selection['month']))->withCount('entries')->orderBy('month')->get();
        $query = PayrollEntry::whereIn('payroll_run_id', $runs->pluck('id'));
        $totals = (clone $query)->selectRaw('COUNT(*) as count, COALESCE(SUM(gross),0) as gross, COALESCE(SUM(tax),0) as tax, COALESCE(SUM(take_home),0) as take_home')->first();
        $query->when($selection['q'], function ($query) use ($selection): void {
            $query->whereHas('employee', fn ($employees) => $employees->where(function ($employees) use ($selection): void {
                $employees->where('name', 'like', '%'.$selection['q'].'%')->orWhere('nik', 'like', '%'.$selection['q'].'%')->orWhere('position', 'like', '%'.$selection['q'].'%');
            }));
        });
        $entries = $query->with(['employee', 'payrollRun'])->orderBy('id')->paginate((int) $selection['per_page'])->withQueryString();

        return view('payroll.index', compact('company', 'branches', 'selection', 'runs', 'entries', 'totals'));
    }

    public function reference(TaxService $tax): View
    {
        return view('tax.reference', ['tables' => config('tax.ter')]);
    }

    public function audit(Request $request, Company $company): View
    {
        $this->access->company($request->user(), $company->id);
        $events = AuditEvent::with('user')->where('company_id', $company->id)->latest('id')->paginate(50);

        return view('audit', compact('company', 'events'));
    }
}
