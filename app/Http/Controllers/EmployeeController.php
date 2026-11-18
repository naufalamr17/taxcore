<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Services\AccessService;
use App\Services\PayrollService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request, Company $company, AccessService $access): View
    {
        $access->company($request->user(), $company->id);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'edit' => ['nullable', 'integer']]);
        $employees = $company->employees()->when($data['q'] ?? '', fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('nik', 'like', '%'.$search.'%')))->orderBy('name')->orderBy('id')->paginate(50)->withQueryString();
        $editing = isset($data['edit']) ? $company->employees()->findOrFail($data['edit']) : null;

        return view('employees.index', compact('company', 'employees', 'editing'));
    }

    public function lookup(Request $request, Company $company, AccessService $access): JsonResponse
    {
        $access->company($request->user(), $company->id);
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $employees = $company->employees()->where('is_active', true)->where(fn ($q) => $q->where('name', 'like', '%'.$data['q'].'%')->orWhere('nik', 'like', '%'.$data['q'].'%'))->orderBy('name')->limit(20)->get(['id', 'name', 'nik', 'position', 'ptkp']);

        return response()->json($employees);
    }

    public function store(EmployeeRequest $request, Company $company, PayrollService $payroll): RedirectResponse
    {
        $payroll->saveEmployee($request->user(), $company, $request->validated());

        return back()->with('status', 'Penerima penghasilan ditambahkan.');
    }

    public function update(EmployeeRequest $request, Company $company, Employee $employee, PayrollService $payroll): RedirectResponse
    {
        $payroll->saveEmployee($request->user(), $company, $request->validated(), $employee);

        return redirect()->route('employees.index',$company)->with('status','Data penerima diperbarui.');
    }
}
