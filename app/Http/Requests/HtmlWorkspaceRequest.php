<?php

namespace App\Http\Requests;

use App\Services\HtmlWorkspaceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HtmlWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('payroll.edit') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'upserts' => ['present', 'array', 'max:10000'],
            'deletes' => ['present', 'array', 'max:10000'],
            'upserts.*.id' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/', 'distinct'],
            'upserts.*._version' => ['nullable', 'integer', 'min:1'],
            'upserts.*.companyId' => ['nullable', 'string', 'max:100'],
            'upserts.*.name' => ['required', 'string', 'max:150'],
            'upserts.*.nik' => ['required', 'digits:16'],
            'upserts.*.position' => ['required', 'string', 'max:150'],
            'upserts.*.year' => ['required', 'integer', 'between:2020,2035'],
            'upserts.*.month' => ['required', 'integer', 'between:1,12'],
            'upserts.*.subjectType' => ['required', Rule::in(array_keys(HtmlWorkspaceService::SUBJECTS))],
            'upserts.*.ptkp' => ['required', Rule::in(array_keys(config('tax.ptkp')))],
            'upserts.*.taxMethod' => ['required', Rule::in(['GROSS', 'GROSS_UP', 'NETT'])],
            'upserts.*.workMonths' => ['nullable', 'integer', 'between:1,12'],
            'upserts.*.workDays' => ['nullable', 'integer', 'between:1,31'],
            'upserts.*.dailyScheme' => ['nullable', Rule::in(['HARIAN', 'BULANAN'])],
            'upserts.*.finalSubType' => ['nullable', Rule::in(['PESANGON', 'MANFAAT_PENSIUN'])],
            'upserts.*.hasNpwp' => ['nullable', 'boolean'],
            'upserts.*.syncedFromMonthly' => ['nullable', 'boolean'],
            'deletes.*' => ['array:id,_version'],
            'deletes.*.id' => ['required', 'string', 'max:100', 'distinct'],
            'deletes.*._version' => ['required', 'integer', 'min:1'],
        ];
        foreach (HtmlWorkspaceService::AMOUNTS as $field) {
            $rules['upserts.*.'.$field] = ['nullable', 'numeric', 'between:0,1000000000000'];
        }
        $fields = array_map(fn (string $key): string => substr($key, strlen('upserts.*.')), array_filter(array_keys($rules), fn (string $key): bool => str_starts_with($key, 'upserts.*.')));
        $rules['upserts.*'] = ['array:'.implode(',', $fields)];

        return $rules;
    }
}
