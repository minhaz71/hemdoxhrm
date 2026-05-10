<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeWeeklyOff;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WeeklyOffService
{
    public const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    public function __construct(private readonly SettingService $settings) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return EmployeeWeeklyOff::with(['employee.user', 'assignedBy'])
            ->when($filters['employee_id'] ?? null, fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($filters['day_of_week'] ?? null, fn ($q, $day) => $q->where('day_of_week', $day))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest('effective_from')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, User $actor): EmployeeWeeklyOff
    {
        $this->assertNoDuplicateActive($data);

        return EmployeeWeeklyOff::create($data + [
            'assigned_by' => $actor->id,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    public function update(EmployeeWeeklyOff $weeklyOff, array $data, User $actor): EmployeeWeeklyOff
    {
        $this->assertNoDuplicateActive($data, $weeklyOff);

        $weeklyOff->update($data + ['assigned_by' => $actor->id]);

        return $weeklyOff->fresh(['employee', 'assignedBy']);
    }

    public function syncEmployeeWeeklyOffs(
        Employee $employee,
        array    $days,
        ?User    $actor         = null,
        ?string  $effectiveFrom = null,
        ?string  $note          = null,
    ): void {
        $days = array_values(array_unique(array_intersect($days, self::DAYS)));
        $effectiveFrom ??= now()->toDateString();

        DB::transaction(function () use ($employee, $days, $actor, $effectiveFrom, $note) {
            $employee->weeklyOffs()->active()->update([
                'status'       => 'inactive',
                'effective_to' => now()->toDateString(),
            ]);

            foreach ($days as $day) {
                $this->create([
                    'employee_id'    => $employee->id,
                    'day_of_week'    => $day,
                    'effective_from' => $effectiveFrom,
                    'effective_to'   => null,
                    'status'         => 'active',
                    'note'           => $note,
                ], $actor ?? auth()->user());
            }
        });
    }

    public function defaultDays(): array
    {
        $raw = $this->settings->get('default_weekly_off_days', '');
        $days = is_string($raw) ? array_filter(array_map('trim', explode(',', $raw))) : [];

        return array_values(array_intersect($days, self::DAYS));
    }

    public function updateDefaultDays(array $days): void
    {
        $days = array_values(array_unique(array_intersect($days, self::DAYS)));

        $this->settings->set(
            'default_weekly_off_days',
            implode(',', $days),
            'Default Weekly Off Days',
            'Default weekly off days preselected on employee creation and registration forms.',
            'attendance',
            'text'
        );
    }

    public function formOptions(): array
    {
        return [
            'employees' => Employee::active()->orderBy('first_name')->get(),
            'days' => self::DAYS,
            'defaultDays' => $this->defaultDays(),
        ];
    }

    private function assertNoDuplicateActive(array $data, ?EmployeeWeeklyOff $ignore = null): void
    {
        if (($data['status'] ?? 'active') !== 'active') {
            return;
        }

        $newFrom = $data['effective_from'];
        // null effective_to means "open-ended" — treat as infinite future for the overlap test
        $newTo   = $data['effective_to'] ?? null;

        // Two ranges [A, B] and [C, D] overlap when A <= D AND C <= B.
        // A null end-date represents '9999-12-31' (open / still active).
        //
        //   existing.start (A) <= new.end   (D)  →  effective_from <= newTo (or newTo is null = ∞)
        //   new.start      (C) <= existing.end (B) →  newFrom <= effective_to (or effective_to is null = ∞)
        $query = EmployeeWeeklyOff::active()
            ->where('employee_id', $data['employee_id'])
            ->where('day_of_week', $data['day_of_week'])
            // existing.start <= new.end  (null new end = far future → always true)
            ->where('effective_from', '<=', $newTo ?? '9999-12-31')
            // new.start <= existing.end  (null existing end = open-ended → always overlaps)
            ->where(fn ($q) => $q->whereNull('effective_to')
                ->orWhere('effective_to', '>=', $newFrom));

        if ($ignore) {
            $query->whereKeyNot($ignore->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'day_of_week' => 'This employee already has an active weekly off for the same day that overlaps the specified date range.',
            ]);
        }
    }
}
