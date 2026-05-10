<?php

namespace App\Services;

use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StaffUserService
{
    public function create(array $data, ?UploadedFile $photo, bool $forcePasswordReset): User
    {
        return DB::transaction(function () use ($data, $photo, $forcePasswordReset) {
            $user = User::create([
                'name'                 => $data['first_name'] . ' ' . $data['last_name'],
                'email'                => $data['email'],
                'login_id'             => $data['login_id'] ?? null,
                'password'             => Hash::make($data['password']),
                'approval_status'      => 'approved',
                'force_password_reset' => $forcePasswordReset,
                'status'               => 'active',
            ]);

            $user->roles()->sync($data['role_ids']);

            Employee::create($this->employeePayload($data, $user->id, $photo?->store('employees/photos', 'public')) + [
                'status'        => 'active',
                'employee_code' => $this->generateCode(),
            ]);

            return $user;
        });
    }

    public function update(User $staffUser, array $data, ?UploadedFile $photo, bool $forcePasswordReset): User
    {
        return DB::transaction(function () use ($staffUser, $data, $photo, $forcePasswordReset) {
            $userUpdate = [
                'name'                 => $data['first_name'] . ' ' . $data['last_name'],
                'email'                => $data['email'],
                'login_id'             => $data['login_id'] ?? null,
                'approval_status'      => $staffUser->approval_status ?: 'approved',
                'force_password_reset' => $forcePasswordReset,
            ];

            if (! empty($data['new_password'])) {
                $userUpdate['password'] = Hash::make($data['new_password']);
                $userUpdate['remember_token'] = null;
            }

            $staffUser->update($userUpdate);
            $staffUser->roles()->sync($data['role_ids']);

            $employee = $staffUser->employee;
            $photoPath = $employee?->photo;

            if ($photo) {
                if ($photoPath) {
                    Storage::disk('public')->delete($photoPath);
                }
                $photoPath = $photo->store('employees/photos', 'public');
            }

            $employeeData = $this->employeePayload($data, $staffUser->id, $photoPath);

            if ($employee) {
                $employee->update($employeeData);
            } else {
                Employee::create($employeeData + [
                    'status'        => 'active',
                    'employee_code' => $this->generateCode(),
                ]);
            }

            return $staffUser->fresh(['roles', 'employee']);
        });
    }

    private function employeePayload(array $data, int $userId, ?string $photoPath): array
    {
        $designationName = null;
        if (! empty($data['designation_id'])) {
            $designationName = Designation::find($data['designation_id'])?->name;
        }

        return [
            'user_id'         => $userId,
            'login_id'        => $data['login_id'] ?? null,
            'first_name'      => $data['first_name'],
            'last_name'       => $data['last_name'],
            'phone'           => $data['phone'] ?? null,
            'date_of_birth'   => $data['date_of_birth'] ?? null,
            'gender'          => $data['gender'] ?? null,
            'address'         => $data['address'] ?? null,
            'nid'             => $data['nid'] ?? null,
            'designation_id'  => $data['designation_id'] ?? null,
            'designation'     => $designationName,
            'department_id'   => $data['department_id'] ?? null,
            'branch_id'       => $data['branch_id'] ?? null,
            'shift_id'        => $data['shift_id'] ?? null,
            'join_date'       => $data['join_date'] ?? null,
            'employment_type' => $data['employment_type'] ?? 'full_time',
            'base_salary'     => $data['base_salary'] ?? 0,
            'photo'           => $photoPath,
        ];
    }

    private function generateCode(): string
    {
        $last = Employee::withTrashed()->max('id') ?? 0;
        return 'EMP-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }
}
