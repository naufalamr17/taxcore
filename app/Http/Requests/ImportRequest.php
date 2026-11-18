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
            'file' => ['nullable', 'required_without:pasted', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
            'pasted' => ['nullable', 'required_without:file', 'string', 'max:2000000']];
    }
}
