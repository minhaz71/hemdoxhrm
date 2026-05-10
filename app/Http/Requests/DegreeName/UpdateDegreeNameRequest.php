<?php

namespace App\Http\Requests\DegreeName;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDegreeNameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:150', Rule::unique('degree_names', 'name')->ignore($this->degree_name)],
            'degree_type_id' => ['nullable', 'integer', 'exists:degree_types,id'],
            'status'         => ['nullable', 'in:active,inactive'],
        ];
    }
}
