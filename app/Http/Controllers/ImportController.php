<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportRequest;
use App\Jobs\CommitPayrollImport;
use App\Models\Company;
use App\Models\ImportBatch;
use App\Services\AccessService;
use App\Services\PayrollImportService;
use App\Services\SpreadsheetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    public function index(Request $request, Company $company, AccessService $access): View
    {
        Gate::authorize('payroll.import');
        $access->company($request->user(), $company->id);
        $module = $request->validate(['module' => ['nullable', Rule::in(array_keys(config('taxcore.modules')))]])['module'] ?? 'monthly';

        return view('imports.index', ['company' => $company, 'branches' => $company->branches()->get(), 'module' => $module, 'batches' => ImportBatch::where('company_id', $company->id)->where('user_id', $request->user()->id)->latest('id')->paginate(20)]);
    }

    public function template(Request $request, Company $company, AccessService $access, PayrollImportService $imports, SpreadsheetService $sheets): Response
    {
        Gate::authorize('payroll.import');
        $access->company($request->user(), $company->id);
        $selection = $request->validate(['module' => ['required', Rule::in(array_keys(config('taxcore.modules')))], 'year' => ['required', 'integer', 'between:2024,2026'], 'month' => ['required', 'integer', 'between:1,12']]);

        return response($sheets->write($imports->template($selection['module'], (int) $selection['year'], (int) $selection['month']), 'Template_Impor'))->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')->header('Content-Disposition', 'attachment; filename="TaxCore_Template.xlsx"');
    }

    public function preview(ImportRequest $request, Company $company, PayrollImportService $imports, SpreadsheetService $sheets): RedirectResponse
    {
        $data = $request->validated();
        $rows = $request->hasFile('file') ? $sheets->read($request->file('file')->getPathname(), strtolower($request->file('file')->getClientOriginalExtension())) : $sheets->delimited($data['pasted']);
        $imports->preview($request->user(), $company, (int) $data['branch_id'], $data['module'], $rows);

        return redirect()->route('imports.index', ['company' => $company, 'module' => $data['module']])->with('status', 'Pratinjau dibuat. Periksa baris sebelum konfirmasi.');
    }

    public function commit(Request $request, Company $company, ImportBatch $batch, AccessService $access): RedirectResponse
    {
        Gate::authorize('payroll.import');
        Gate::authorize('payroll.edit');
        $access->company($request->user(), $company->id);
        abort_unless($batch->company_id === $company->id && $batch->user_id === $request->user()->id, 404);
        DB::transaction(function () use ($batch): void {
            $locked = ImportBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'Preview' || $locked->errors) {
                throw ValidationException::withMessages(['import' => 'Impor sudah diproses atau masih memiliki kesalahan.']);
            }
            $locked->update(['status' => 'Queued']);
            if ($locked->row_count > 200) {
                CommitPayrollImport::dispatch($locked->id)->afterCommit();
            }
        });
        if ($batch->row_count <= 200) {
            try {
                CommitPayrollImport::dispatchSync($batch->id);
            } catch (\Throwable $exception) {
                $batch->update(['status' => 'Failed', 'failure' => 'Tidak ada baris disimpan. Periksa duplikasi atau riwayat pajak.']);
                if ($exception instanceof ValidationException) {
                    throw $exception;
                }
                throw $exception;
            }
        }

        return back()->with('status', $batch->row_count > 200 ? 'Impor masuk antrean. Status akan diperbarui setelah worker selesai.' : 'Impor selesai.');
    }
}
