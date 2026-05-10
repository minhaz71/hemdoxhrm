<?php

namespace App\Http\Requests\DegreeType;

use Illuminate\Foundation\Http\FormRequest;

class StoreDegreeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'max:100', 'unique:degree_types,name'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
