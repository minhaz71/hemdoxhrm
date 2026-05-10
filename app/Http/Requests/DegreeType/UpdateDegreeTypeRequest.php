<?php

namespace App\Http\Requests\DegreeType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDegreeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'max:100', Rule::unique('degree_types', 'name')->ignore($this->degree_type)],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
