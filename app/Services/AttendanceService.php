<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeWeeklyOff;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function mark(Employee $employee, array $data): Attendance
    {
        $today = today()->toDateString();

        // Enforce: only today's date allowed
        if (($data['date'] ?? $today) !== $today) {
            throw ValidationException::withMessages([
                'date' => 'Attendance can only be marked for today.',
            ]);
        }

        // Enforce: one record per day
        if (Attendance::forEmployee($employee->id)->forDate($today)->exists()) {
            throw ValidationException::withMessages([
                'employee_id' => "{$employee->full_name} already has attendance marked for today.",
            ]);
        }

        $checkIn = Carbon::now()->format('H:i:s');
        $status  = $checkIn > Attendance::LATE_THRESHOLD ? 'late' : 'present';

        return Attendance::create([
            'employee_id' => $employee->id,
            'date'        => $today,
            'check_in'    => $checkIn,
            'status'      => $status,
            'note'        => $data['note'] ?? null,
        ]);
    }

    public function checkOut(Attendance $attendance): Attendance
    {
        if ($attendance->check_out) {
            throw ValidationException::withMessages([
                'check_out' => 'Already checked out.',
            ]);
        }

        $attendance->update(['check_out' => Carbon::now()->format('H:i:s')]);

        return $attendance->fresh();
    }

    public function todaySummary(): array
    {
        $today    = today();
        $todayStr = $today->toDateString();
        $dayName  = strtolower($today->format('l')); // e.g. 'friday'

        $total = Employee::active()->count();

        // ── Attendance counts (single query) ──────────────────────────
        $counts = Attendance::today()
            ->selectRaw("
                SUM(status IN ('present','late','underworked')) as present,
                SUM(status = 'late')                           as late,
                SUM(status = 'underworked')                    as underworked
            ")
            ->first();

        $present     = (int) ($counts->present     ?? 0);
        $late        = (int) ($counts->late        ?? 0);
        $underworked = (int) ($counts->underworked ?? 0);

        // ── Weekly off count (employees on weekly off today who didn't come in) ──
        // 1. Collect IDs of active employees whose weekly off day is today
        $weeklyOffIds = EmployeeWeeklyOff::query()
            ->where('status', 'active')
            ->where('day_of_week', $dayName)
            ->where('effective_from', '<=', $todayStr)
            ->where(fn ($q) => $q->whereNull('effective_to')
                ->orWhere('effective_to', '>=', $todayStr))
            ->whereHas('employee', fn ($q) => $q->where('status', 'active'))
            ->pluck('employee_id')
            ->unique();

        // 2. Among those, exclude any who actually came in today (they chose to work)
        $presentIds = Attendance::today()
            ->whereIn('status', ['present', 'late', 'underworked'])
            ->pluck('employee_id')
            ->unique();

        // 3. "On weekly off" = weekly off today AND not marked present
        $onWeeklyOff = $weeklyOffIds->diff($presentIds)->count();

        // ── On approved leave today ────────────────────────────────────
        $onLeaveIds = Leave::where('status', 'approved')
            ->where('start_date', '<=', $todayStr)
            ->where('end_date', '>=', $todayStr)
            ->pluck('employee_id')
            ->unique();

        // Exclude those who actually came in despite the leave
        $onLeave = $onLeaveIds->diff($presentIds)->count();

        // ── Absent = not present, not on weekly off, not on leave ──────
        $absent = max(0, $total - $present - $onWeeklyOff - $onLeave);

        return compact('total', 'present', 'late', 'underworked', 'onWeeklyOff', 'onLeave', 'absent');
    }

    public function todayList(): Collection
    {
        return Attendance::with('employee')
            ->today()
            ->latest()
            ->get();
    }

    public function employeeHistory(Employee $employee, int $perPage = 20): LengthAwarePaginator
    {
        return Attendance::forEmployee($employee->id)
            ->latest('date')
            ->paginate($perPage);
    }

    public function paginateByDate(string $date, int $perPage = 20): LengthAwarePaginator
    {
        return Attendance::with('employee')
            ->forDate($date)
            ->latest()
            ->paginate($perPage);
    }

    public function alreadyMarked(Employee $employee): bool
    {
        return Attendance::forEmployee($employee->id)
            ->forDate(today()->toDateString())
            ->exists();
    }
}
