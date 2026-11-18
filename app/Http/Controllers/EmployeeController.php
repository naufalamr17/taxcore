<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Services\AccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request, Company $company, AccessService $access): View
    {
        $access->company($request->user(), $company->id);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $employees = $company->employees()->when($data['q'] ?? '', fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('nik', 'like', '%'.$search.'%')))->orderBy('name')->orderBy('id')->paginate(50)->withQueryString();

        return view('employees.index', compact('company', 'employees'));
    }

    public function lookup(Request $request, Company $company, AccessService $access): JsonResponse
    {
        $access->company($request->user(), $company->id);
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $employees = $company->employees()->where('is_active', true)->where(fn ($q) => $q->where('name', 'like', '%'.$data['q'].'%')->orWhere('nik', 'like', '%'.$data['q'].'%'))->orderBy('name')->limit(20)->get(['id', 'name', 'nik', 'position', 'ptkp']);

        return response()->json($employees);
    }

    public function store(Request $request, Company $company, AccessService $access): never
    {
        $access->company($request->user(), $company->id);
        abort(405, 'Kelola penerima melalui modal payroll.');
    }

    public function update(Request $request, Company $company, Employee $employee, AccessService $access): never
    {
        $access->company($request->user(), $company->id);
        $company->employees()->findOrFail($employee->id);
        abort(405, 'Kelola penerima melalui modal payroll.');
    }
}
