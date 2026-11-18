<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company.manage') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150'], 'npwp' => ['required', 'digits:16', Rule::unique('companies')->ignore($this->route('company'))],
            'city' => ['required', 'string', 'max:100'], 'address' => ['nullable', 'string', 'max:255'],
            'signer_name' => ['required', 'string', 'max:150'], 'signer_npwp' => ['required', 'digits:16']];
    }
}
