<?php

namespace App\Http\Requests;

use App\Services\TaxService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payroll.edit') ?? false;
    }

    public function rules(TaxService $tax): array
    {
        $companyId = $this->route('company')?->id;
        $rules = $tax->rules() + [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'version' => ['nullable', 'integer', 'min:1'],
            'opening_salary_only' => ['nullable', 'boolean'],
            'opening_reason' => ['nullable', 'string', 'max:500'],
            'tax_object_code' => ['required', 'regex:/^21-[0-9]{3}-[0-9]{2}$/'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'document_date' => ['nullable', 'date'],
            'recipient_nitku' => ['nullable', 'digits:22'],
            'final_start_year' => ['nullable', 'integer', 'min:2020', 'max:2026'],
            'withholding_date' => ['required', 'date'],
        ];
        foreach (['gross', 'tax', 'pension', 'zakat'] as $field) {
            $rules['opening_'.$field] = ['nullable', 'integer', 'between:0,1000000000000'];
        }

        return $rules;
    }
}
