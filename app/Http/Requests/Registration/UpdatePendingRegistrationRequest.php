<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePendingRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('registration')) ?? false;
    }

    public function rules(): array
    {
        $registration = $this->route('registration');

        return [
            'first_name'      => ['required', 'string', 'max:100'],
            'last_name'       => ['required', 'string', 'max:100'],
            'email'           => ['required', 'email', 'max:255', "unique:pending_registrations,email,{$registration->id}", 'unique:users,email'],
            'organization_employee_code' => [
                'nullable',
                'string',
                'max:80',
                'unique:employees,organization_employee_code',
                Rule::unique('pending_registrations', 'organization_employee_code')->ignore($registration->id),
            ],
            'phone'           => ['nullable', 'string', 'max:20'],
            'date_of_birth'   => ['nullable', 'date', 'before:today'],
            'gender'          => ['nullable', 'in:male,female,other'],
            'address'         => ['nullable', 'string', 'max:255'],
            'nid'             => ['nullable', 'string', 'max:50'],
            'nid_document'    => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'certificate'     => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'photo'           => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'designation_id'  => ['required', 'integer', 'exists:designations,id'],
            'department_id'   => ['nullable', 'integer', 'exists:departments,id'],
            'branch_id'       => ['nullable', 'integer', 'exists:branches,id'],
            'shift_id'        => ['nullable', 'integer', 'exists:shifts,id'],
            'weekly_off_days'   => ['nullable', 'array', 'max:7'],
            'weekly_off_days.*' => ['string', 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday'],
            'weekly_off_note'   => ['nullable', 'string', 'max:500'],
            'join_date'       => ['required', 'date'],
            'employment_type' => ['required', 'in:full_time,part_time,contract'],
            'base_salary'     => ['required', 'numeric', 'min:0'],
            'login_id'        => [
                'required', 'string', 'min:4', 'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:users,login_id',
                "unique:pending_registrations,login_id,{$registration->id}",
            ],
        ];
    }
}
