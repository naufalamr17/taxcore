<?php

namespace App\Http\Controllers;

use App\Http\Requests\HtmlWorkspaceRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Services\AccessService;
use App\Services\HtmlWorkspaceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class HtmlWorkspaceController extends Controller
{
    public function __construct(private HtmlWorkspaceService $workspace, private AccessService $access) {}

    public function index(Request $request, ?Company $company = null): View
    {
        $selection = $request->validate(['mode' => ['nullable', Rule::in(['payroll', 'company', 'simulator', 'access', 'audit'])], 'module' => ['nullable', Rule::in(array_keys(config('taxcore.modules')))], 'year' => ['nullable', 'integer', 'between:2020,2035'], 'month' => ['nullable', 'integer', 'between:0,12'], 'branch_id' => ['nullable', 'integer', 'min:1']]);
        if ($company) {
            $this->access->company($request->user(), $company->id);
            $request->session()->put('active_company_id', $company->id);
        }
        $branch = null;
        if (! empty($selection['branch_id'])) {
            $branch = Branch::findOrFail($selection['branch_id']);
            $this->access->company($request->user(), $branch->company_id);
            abort_if($company && $branch->company_id !== $company->id, 404);
            $request->session()->put('active_company_id', $branch->company_id);
        }
        $bootstrap = $this->workspace->state($request->user());
        $bootstrap['mode'] = $request->routeIs('companies.index') ? 'company' : ($request->routeIs('simulator.index') ? 'simulator' : ($selection['mode'] ?? 'payroll'));
        $bootstrap['selection'] = $selection;
        if ($branch) {
            $bootstrap['activeCompanyId'] = 'branch_'.$branch->id;
        }

        return view('workspace', compact('bootstrap'));
    }

    public function state(Request $request): JsonResponse
    {
        return response()->json($this->workspace->state($request->user()));
    }

    public function saveEmployees(HtmlWorkspaceRequest $request, Company $company, Branch $branch): JsonResponse
    {
        return response()->json(['employees' => $this->workspace->saveEmployees($request->user(), $company, $branch, $request->validated())]);
    }

    public function recordExport(Request $request, Company $company, Branch $branch): JsonResponse
    {
        Gate::authorize('payroll.export');
        $this->access->company($request->user(), $company->id);
        abort_unless($branch->company_id === $company->id, 404);
        $selection = $request->validate(['format' => ['required', Rule::in(['xlsx', 'xml'])], 'year' => ['required', 'integer', 'between:2020,2035'], 'month' => ['required', 'integer', 'between:0,12']]);
        $this->access->audit($request->user(), $company->id, 'export.'.$selection['format'], 'payroll', ['branch_id' => $branch->id, 'selection' => $selection, 'source' => 'html']);

        return response()->json(['recorded' => true]);
    }

    public function saveCompany(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'string', 'regex:/^(?:branch_[0-9]+|comp_[A-Za-z0-9_-]+)$/'],
            'name' => ['required', 'string', 'max:150'], 'branchName' => ['required', 'string', 'max:150'],
            'npwp' => ['required', 'digits:16'], 'nitkuSuffix' => ['required', 'digits:6'],
            'signerName' => ['nullable', 'string', 'max:150'], 'signerNpwp' => ['nullable', 'digits:16'],
            'city' => ['required', 'string', 'max:100'], 'address' => ['nullable', 'string', 'max:255'],
        ]);
        $branch = $this->workspace->saveCompany($request->user(), $data);

        return response()->json(['company' => $this->workspace->companyRecord($branch), 'companies' => $this->workspace->state($request->user())['companies']]);
    }

    public function deleteCompany(Request $request, Branch $branch): JsonResponse
    {
        $this->workspace->deleteCompany($request->user(), $branch);

        return response()->json(['companies' => $this->workspace->state($request->user())['companies']]);
    }
}
