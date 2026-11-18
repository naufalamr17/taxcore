<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\AccessService;
use App\Services\JournalService;
use App\Services\TaxService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SimulatorController extends Controller
{
    public function index(Request $request, Company $company, AccessService $access): View
    {
        $access->company($request->user(), $company->id);
        $selection = $request->validate(['module' => ['nullable', Rule::in(array_keys(config('taxcore.modules')))]]);

        return view('tax.simulator', ['company' => $company, 'data' => ['module' => $selection['module'] ?? 'monthly', 'method' => 'GROSS', 'ptkp' => 'TK/0', 'year' => 2026, 'month' => ($selection['module'] ?? '') === 'annual' ? 12 : (int) now()->month], 'result' => null]);
    }

    public function calculate(Request $request, Company $company, AccessService $access, TaxService $tax, JournalService $journal): View
    {
        $access->company($request->user(), $company->id);
        $data = $request->validate($tax->rules());
        $result = $tax->calculate($data);

        return view('tax.simulator', ['company' => $company, 'data' => $data, 'result' => $result, 'journal' => $journal->entries($result)]);
    }
}
