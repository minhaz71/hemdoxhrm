<?php

namespace App\Http\Requests\EmployeeEducation;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $this->user()->can('manageEducation', $employee);
    }

    public function rules(): array
    {
        return [
            'degree_type_id'  => ['required', 'integer', 'exists:degree_types,id'],
            'degree_name_id'  => ['required', 'integer', 'exists:degree_names,id'],
            'institute_name'  => ['required', 'string', 'max:200'],
            'passing_year'    => ['required', 'integer', 'min:1950', 'max:' . (date('Y') + 1)],
            'result'          => ['nullable', 'string', 'max:50'],
            'board_university' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'passing_year.min' => 'Passing year must be 1950 or later.',
            'passing_year.max' => 'Passing year cannot be in the future.',
        ];
    }
}
