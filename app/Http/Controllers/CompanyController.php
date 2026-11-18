<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Services\AccessService;
use App\Services\CompanyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request, AccessService $access): View
    {
        return view('companies.index', ['companies' => $access->companies($request->user())->with('branches')->orderBy('name')->get()]);
    }

    public function store(CompanyRequest $request, CompanyService $companies): RedirectResponse
    {
        $companies->save($request->user(), $request->validated());

        return back()->with('status', 'Perusahaan ditambahkan.');
    }

    public function destroy(Request $request, Company $company, CompanyService $companies): RedirectResponse
    {
        $companies->remove($request->user(), $company);

        return redirect()->route('companies.index')->with('status', 'Perusahaan kosong dihapus.');
    }

    public function update(CompanyRequest $request, Company $company, CompanyService $companies): RedirectResponse
    {
        $companies->save($request->user(), $request->validated(), $company);

        return back()->with('status', 'Profil perusahaan diperbarui. Snapshot payroll final tetap tersimpan.');
    }

    public function updateBranch(Request $request, Company $company, Branch $branch, CompanyService $companies, AccessService $access): RedirectResponse
    {
        Gate::authorize('company.manage');
        $access->company($request->user(), $company->id);
        abort_unless($branch->company_id === $company->id, 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'code' => ['required', 'digits:6', Rule::unique('branches')->where('company_id', $company->id)->ignore($branch)]]);
        $companies->saveBranch($request->user(), $company, $data, $branch);

        return back()->with('status', 'Cabang dan NITKU diperbarui. Snapshot final tetap tersimpan.');
    }

    public function branch(Request $request, Company $company, CompanyService $companies): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'code' => ['required', 'digits:6', Rule::unique('branches')->where('company_id', $company->id)]]);
        $companies->saveBranch($request->user(), $company, $data);

        return back()->with('status', 'Cabang ditambahkan.');
    }
}
