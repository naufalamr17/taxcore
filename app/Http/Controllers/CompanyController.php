<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use App\Services\AccessService;
use App\Services\CompanyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function update(CompanyRequest $request, Company $company, CompanyService $companies): RedirectResponse
    {
        $companies->save($request->user(), $request->validated(), $company);

        return back()->with('status', 'Profil perusahaan diperbarui. Snapshot payroll final tetap tersimpan.');
    }

    public function branch(Request $request, Company $company, CompanyService $companies): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'code' => ['required', 'digits:6', Rule::unique('branches')->where('company_id', $company->id)]]);
        $companies->addBranch($request->user(), $company, $data);

        return back()->with('status', 'Cabang ditambahkan.');
    }
}
