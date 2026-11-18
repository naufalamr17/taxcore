<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CoretaxExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ExportController extends Controller
{
    public function download(Request $request, Company $company, string $format, CoretaxExportService $exports): Response
    {
        abort_unless(in_array($format, ['xlsx', 'xml'], true), 404);
        $selection = $request->validate(['branch_id' => ['required', 'integer'], 'year' => ['required', 'integer', 'between:2024,2026'], 'month' => ['nullable', 'integer', 'between:0,12'], 'module' => ['required', Rule::in(array_keys(config('taxcore.modules')))]]);
        $bytes = $format === 'xlsx' ? $exports->excel($request->user(), $company, $selection) : $exports->xml($request->user(), $company, $selection);

        return response($bytes)->header('Content-Type', $format === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/xml; charset=UTF-8')->header('Content-Disposition', 'attachment; filename="TaxCore_'.$selection['year'].'_'.$selection['module'].'.'.$format.'"');
    }
}
