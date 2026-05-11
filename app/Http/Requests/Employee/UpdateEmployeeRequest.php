<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'hr']);
    }

    public function rules(): array
    {
        return [
            'first_name'      => ['required', 'string', 'max:100'],
            'last_name'       => ['required', 'string', 'max:100'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'date_of_birth'   => ['nullable', 'date', 'before:today'],
            'gender'          => ['nullable', 'in:male,female,other'],
            'address'         => ['nullable', 'string', 'max:255'],
            'branch_id'       => ['nullable', 'integer', 'exists:branches,id'],
            'organization_employee_code' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('employees', 'organization_employee_code')->ignore($this->route('employee')),
            ],
            'department_id'   => ['nullable', 'integer', 'exists:departments,id'],
            'shift_id'        => ['nullable', 'integer', 'exists:shifts,id'],
            'team_leader_id'  => [
                'nullable',
                'integer',
                'exists:employees,id',
                Rule::notIn([$this->route('employee')?->id]),
            ],
            'weekly_off_days' => ['nullable', 'array'],
            'weekly_off_days.*' => ['in:sunday,monday,tuesday,wednesday,thursday,friday,saturday'],
            'department'      => ['nullable', 'string', 'max:100'],
            'designation_id'  => ['required', 'integer', 'exists:designations,id'],
            'join_date'       => ['required', 'date'],
            'employment_type' => ['required', 'in:full_time,part_time,contract'],
            'status'          => ['required', 'in:active,inactive,terminated'],
            'base_salary'          => ['required', 'numeric', 'min:0'],
            'salary_effective_from' => ['nullable', 'date_format:Y-m', 'before_or_equal:' . now()->format('Y-m')],
            'email'           => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('employee')?->user_id),
            ],
            'alternate_email' => ['nullable', 'email', 'max:255'],
            // Documents & photo
            'nid'             => ['nullable', 'string', 'max:50'],
            'nid_document'    => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'certificate'     => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'photo'           => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'base_salary.min'         => 'Base salary cannot be negative.',
            'designation_id.required' => 'Please select a designation.',
            'designation_id.exists'   => 'Selected designation does not exist.',
            'nid_document.mimes'      => 'NID document must be a PDF file.',
            'nid_document.max'        => 'NID document must not exceed 5MB.',
            'certificate.mimes'       => 'Certificate must be a PDF file.',
            'certificate.max'         => 'Certificate must not exceed 5MB.',
            'photo.max'               => 'Profile photo must not exceed 2MB.',
        ];
    }
}
