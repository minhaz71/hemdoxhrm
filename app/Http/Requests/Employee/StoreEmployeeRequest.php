<?php

namespace App\Http\Requests\Employee;

use App\Support\EnterprisePassword;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
                'unique:employees,organization_employee_code',
                'unique:pending_registrations,organization_employee_code',
            ],
            'department_id'   => ['nullable', 'integer', 'exists:departments,id'],
            'shift_id'        => ['nullable', 'integer', 'exists:shifts,id'],
            'team_leader_id'  => ['nullable', 'integer', 'exists:employees,id'],
            'weekly_off_days' => ['nullable', 'array'],
            'weekly_off_days.*' => ['in:sunday,monday,tuesday,wednesday,thursday,friday,saturday'],
            'department'      => ['nullable', 'string', 'max:100'],
            'designation_id'  => ['required', 'integer', 'exists:designations,id'],
            'join_date'       => ['required', 'date'],
            'employment_type' => ['required', 'in:full_time,part_time,contract'],
            'base_salary'     => ['required', 'numeric', 'min:0'],

            // Optional linked account
            'email'           => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'alternate_email' => ['nullable', 'email', 'max:255'],
            'password'        => ['nullable', 'confirmed', EnterprisePassword::rules()],
        ];
    }

    public function messages(): array
    {
        return [
            'base_salary.min'    => 'Base salary cannot be negative.',
            'email.unique'       => 'This email is already registered.',
            'join_date.required' => 'Join date is required.',
            'designation_id.required' => 'Please select a designation.',
            'designation_id.exists'   => 'Selected designation does not exist.',
        ];
    }
}
