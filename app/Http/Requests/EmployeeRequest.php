<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payroll.edit') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150'], 'nik' => ['required', 'digits:16', Rule::unique('employees')->where('company_id', $this->route('company')?->id)->ignore($this->route('employee'))],
            'position' => ['required', 'string', 'max:150'], 'ptkp' => ['required', Rule::in(array_keys(config('tax.ptkp')))],
            'joined_at' => ['required', 'date'], 'left_at' => ['nullable', 'date', 'after_or_equal:joined_at'], 'is_active' => ['required', 'boolean']];
    }
}
