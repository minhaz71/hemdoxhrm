<?php

namespace App\Services;

use App\Models\Designation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\SalaryHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeService
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return Employee::with(['user', 'branch', 'department'])
            ->when(! empty($filters['branch_id']), fn ($q) => $q->where('branch_id', $filters['branch_id']))
            ->when(! empty($filters['status']),    fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['search']),    fn ($q) => $q->where(function ($q2) use ($filters) {
                $q2->where('first_name', 'like', "%{$filters['search']}%")
                   ->orWhere('last_name',  'like', "%{$filters['search']}%")
                   ->orWhere('employee_code', 'like', "%{$filters['search']}%");
            }))
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $weeklyOffDays = $data['weekly_off_days'] ?? [];
            unset($data['weekly_off_days']);
            $this->syncLegacyCompanyFields($data);

            // Create linked user account if email provided
            $user = $this->createLinkedUser($data);

            $employee = Employee::create([
                ...$data,
                'user_id'       => $user?->id,
                'employee_code' => $this->generateCode(),
            ]);

            // Seed initial salary history
            SalaryHistory::create([
                'employee_id'    => $employee->id,
                'base_salary'    => $data['base_salary'] ?? 0,
                'effective_from' => $employee->join_date
                    ? $employee->join_date->startOfMonth()->toDateString()
                    : now()->startOfMonth()->toDateString(),
                'effective_to'   => null,
                'changed_by'     => Auth::id(),
                'note'           => 'Initial salary at hire.',
            ]);

            if ($weeklyOffDays) {
                app(WeeklyOffService::class)->syncEmployeeWeeklyOffs($employee, $weeklyOffDays, auth()->user(), $employee->join_date?->toDateString() ?? now()->toDateString());
            }

            return $employee;
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $weeklyOffDays        = $data['weekly_off_days'] ?? null;
            $salaryEffectiveFrom  = $data['salary_effective_from'] ?? null;
            unset($data['weekly_off_days'], $data['salary_effective_from']);

            // ── Salary history tracking ───────────────────────────────
            $newSalary = isset($data['base_salary']) ? (float) $data['base_salary'] : null;
            $oldSalary = (float) $employee->base_salary;

            if ($newSalary !== null && abs($newSalary - $oldSalary) > 0.001) {
                // Determine first day of effective month (defaults to current month)
                $effectiveDate = $salaryEffectiveFrom
                    ? Carbon::createFromFormat('Y-m', $salaryEffectiveFrom)->startOfMonth()
                    : now()->startOfMonth();

                // Close the currently open history row (the one with effective_to = null)
                SalaryHistory::where('employee_id', $employee->id)
                    ->whereNull('effective_to')
                    ->update([
                        'effective_to' => $effectiveDate->copy()->subDay()->toDateString(),
                        'updated_at'   => now(),
                    ]);

                // Open a new history row for the new salary
                SalaryHistory::create([
                    'employee_id'    => $employee->id,
                    'base_salary'    => $newSalary,
                    'effective_from' => $effectiveDate->toDateString(),
                    'effective_to'   => null,
                    'changed_by'     => Auth::id(),
                    'note'           => "Salary updated from {$oldSalary} to {$newSalary}.",
                ]);
            }

            $this->syncLegacyCompanyFields($data);
            $employee->update($data);

            // Keep linked user name/email in sync. If HR adds an email later,
            // create the account so Time Doctor can match by email.
            if ($employee->user) {
                $employee->user->update([
                    'name' => $data['first_name'] . ' ' . $data['last_name'],
                    'email' => $data['email'] ?: $employee->user->email,
                ]);
            } elseif (! empty($data['email'])) {
                $user = $this->createLinkedUser($data);
                $employee->update(['user_id' => $user?->id]);
            }

            if (is_array($weeklyOffDays)) {
                app(WeeklyOffService::class)->syncEmployeeWeeklyOffs($employee, $weeklyOffDays, auth()->user(), $data['join_date'] ?? now()->toDateString());
            }

            return $employee->fresh();
        });
    }

    public function terminate(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $employee->update(['status' => 'terminated']);

            // Block login immediately
            if ($employee->user) {
                $employee->user->update(['status' => 'terminated']);
            }
        });
    }

    public function delete(Employee $employee): void
    {
        $employee->delete(); // soft delete
    }

    // Derives legacy text columns from normalized FKs so older reports and
    // strict MySQL schemas keep working while the new company structure is used.
    private function syncLegacyCompanyFields(array &$data): void
    {
        if (! empty($data['department_id'])) {
            $department = Department::find($data['department_id']);
            if ($department) {
                $data['department'] = $department->name;
            }
        }

        if (! empty($data['designation_id'])) {
            $designation = Designation::find($data['designation_id']);
            if ($designation) {
                $data['designation'] = $designation->name;
            }
        }
    }

    private function createLinkedUser(array $data): ?User
    {
        if (empty($data['email'])) {
            return null;
        }

        $user = User::create([
            'name'            => $data['first_name'] . ' ' . $data['last_name'],
            'email'           => $data['email'],
            'password'        => Hash::make($data['password'] ?? Str::random(32)),
            'approval_status' => 'approved',
        ]);

        $user->assignRole(Role::EMPLOYEE);

        return $user;
    }

    private function generateCode(): string
    {
        $last = Employee::withTrashed()->max('id') ?? 0;
        return 'EMP-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }
}
