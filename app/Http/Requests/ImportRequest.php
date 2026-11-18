<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payroll.import') && $this->user()?->can('payroll.edit');
    }

    public function rules(): array
    {
        return ['branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('company_id', $this->route('company')?->id)],
            'module' => ['required', Rule::in(array_keys(config('taxcore.modules')))],
            'mode' => ['nullable', Rule::in(['APPEND', 'REPLACE'])],
            'period_source' => ['nullable', Rule::in(['FILE', 'ACTIVE'])],
            'year' => ['required_if:period_source,ACTIVE', 'nullable', 'integer', 'between:2024,2026'],
            'month' => ['required_if:period_source,ACTIVE', 'nullable', 'integer', 'between:1,12'],
            'file' => ['nullable', 'required_without:pasted', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
            'pasted' => ['nullable', 'required_without:file', 'string', 'max:2000000']];
    }
}
