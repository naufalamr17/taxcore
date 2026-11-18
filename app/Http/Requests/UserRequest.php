<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access.manage') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150'], 'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users')->ignore($this->route('user'))],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($this->route('user'))],
            'password' => [$this->route('user') ? 'nullable' : 'required', 'string', 'min:12', 'max:128'],
            'role' => ['required', Rule::in(array_keys(config('taxcore.roles')))], 'is_active' => ['required', 'boolean'],
            'company_ids' => ['nullable', 'array'], 'company_ids.*' => ['integer', 'distinct', 'exists:companies,id']];
    }
}
