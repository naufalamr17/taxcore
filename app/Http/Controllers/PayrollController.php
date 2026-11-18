<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollEntryRequest;
use App\Models\Company;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Services\AccessService;
use App\Services\JournalService;
use App\Services\PayrollService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PayrollController extends Controller
{
    public function create(Request $request, Company $company, AccessService $access): View
    {
        Gate::authorize('payroll.edit');
        $access->company($request->user(), $company->id);
        $data = $request->validate(['year' => ['nullable', 'integer', 'between:2024,2026'], 'month' => ['nullable', 'integer', 'between:1,12'], 'module' => ['nullable', Rule::in(array_keys(config('taxcore.modules')))], 'branch_id' => ['nullable', 'integer']]);
        $data += ['year' => 2026, 'month' => (int) now()->month, 'module' => 'monthly', 'method' => 'GROSS', 'ptkp' => 'TK/0'];

        return view('payroll.form', ['company' => $company, 'branches' => $company->branches()->get(), 'entry' => null, 'data' => $data]);
    }

    public function edit(Request $request, Company $company, PayrollEntry $entry, AccessService $access): View
    {
        Gate::authorize('payroll.edit');
        $access->company($request->user(), $company->id);
        $run = $company->payrollRuns()->findOrFail($entry->payroll_run_id);

        return view('payroll.form', ['company' => $company, 'branches' => $company->branches()->get(), 'entry' => $entry, 'data' => array_merge($entry->input, ['version' => $entry->version])]);
    }

    public function store(PayrollEntryRequest $request, Company $company, PayrollService $payroll): RedirectResponse
    {
        $entry = $payroll->saveEntry($request->user(), $company, $request->validated());

        return redirect()->route($request->input('return_to') === 'simulator' ? 'simulator.index' : 'payroll.show', $request->input('return_to') === 'simulator' ? ['company' => $company, 'entry' => $entry->id] : [$company, $entry])->with('status', 'Payroll dihitung dan disimpan sebagai draft.');
    }

    public function update(PayrollEntryRequest $request, Company $company, PayrollEntry $entry, PayrollService $payroll): RedirectResponse
    {
        $entry = $payroll->saveEntry($request->user(), $company, $request->validated(), $entry);
        if ($request->input('return_to') === 'spreadsheet') {
            return redirect()->route('payroll.index', ['company' => $company] + collect($entry->input)->only(['year', 'month', 'branch_id', 'module'])->all())->with('status', 'Baris spreadsheet disimpan dan dihitung ulang.');
        }
        if ($request->input('return_to') === 'simulator') {
            return redirect()->route('simulator.index', ['company' => $company, 'entry' => $entry->id])->with('status', 'Draft diperbarui dari simulator.');
        }

        return redirect()->route('payroll.show', [$company, $entry])->with('status', 'Payroll diperbarui.');
    }

    public function show(Request $request, Company $company, PayrollEntry $entry, AccessService $access, JournalService $journal): View
    {
        $access->company($request->user(), $company->id);
        $run = $company->payrollRuns()->with('branch')->findOrFail($entry->payroll_run_id);

        return view('payroll.show', ['company' => $company, 'entry' => $entry, 'run' => $run, 'journal' => $journal->entries($entry->result)]);
    }

    public function destroy(Request $request, Company $company, PayrollEntry $entry, PayrollService $payroll): RedirectResponse
    {
        $payroll->remove($request->user(), $company, $entry);

        return redirect()->route('payroll.index', array_merge(['company' => $company], collect($entry->input)->only(['year', 'month', 'branch_id', 'module'])->all()))->with('status', 'Baris draft dihapus dan dicatat dalam audit.');
    }

    public function transition(Request $request, Company $company, PayrollRun $run, PayrollService $payroll): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(['review', 'finalize', 'correct'])], 'reason' => ['required_if:action,correct', 'nullable', 'string', 'min:5', 'max:500']]);
        $payroll->transition($request->user(), $company, $run, $data['action'], $data['reason'] ?? null);

        return back()->with('status', 'Status payroll diperbarui.');
    }

    public function bulk(Request $request, Company $company, PayrollService $payroll): RedirectResponse
    {
        $data = $request->validate(['branch_id' => ['required', 'integer'], 'year' => ['required', 'integer', 'between:2024,2026'], 'month' => ['required', 'integer', 'between:1,12'], 'module' => ['required', Rule::in(array_keys(config('taxcore.modules')))], 'action' => ['required', Rule::in(['thr', 'reset_thr', 'bonus', 'GROSS', 'GROSS_UP', 'NETT', 'copy', 'pull_monthly', 'reset_demo'])], 'value' => ['nullable', 'integer', 'between:0,1000']]);
        $count = match ($data['action']) {
            'copy' => $payroll->copyPrevious($request->user(), $company, $data),
            'pull_monthly' => $payroll->pullMonthly($request->user(), $company, $data),
            'reset_demo' => $payroll->resetDemo($request->user(), $company, $data),
            default => $payroll->bulk($request->user(), $company, $data, $data['action'], (int) ($data['value'] ?? 0)),
        };

        return back()->with('status', $count.' baris diproses pada periode terpilih.');
    }
}
