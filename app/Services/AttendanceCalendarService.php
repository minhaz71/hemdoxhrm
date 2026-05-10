<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Support\DayStatus;
use Carbon\Carbon;

/**
 * AttendanceCalendarService
 *
 * Extends HolidayCalendarService with rich per-employee day-status
 * resolution. Returns a DayStatus value object that carries the full
 * reason a day is non-working so the absent cron (and any other caller)
 * can decide what to do with that information.
 *
 * Check order (most-specific wins, short-circuits):
 *   1. Employee's custom weekly off
 *   2. Employee-specific holiday
 *   3. Department holiday
 *   4. Branch holiday
 *   5. Company-wide (global) holiday
 *   6. Carbon weekend (Saturday / Sunday)
 *   7. Normal working day
 *
 * Weekly off is checked before holidays so that an employee who has both
 * a weekly off AND a company holiday on the same day gets the more
 * informative "weekly_off" status (which is always correct from their
 * perspective).
 */
class AttendanceCalendarService extends HolidayCalendarService
{
    // ── Core API ───────────────────────────────────────────────────────

    /**
     * Return a full DayStatus describing what $date means for $employee.
     *
     * This is the single source of truth for the absent cron and any
     * UI that needs to render a calendar.
     */
    public function getDayStatus(Employee $employee, Carbon|string $date): DayStatus
    {
        $carbon  = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dateStr = $carbon->toDateString();
        $dayName = strtolower($carbon->format('l')); // e.g. 'friday'

        // ── 1. Employee weekly off ────────────────────────────────────
        if ($this->isWeeklyOff($employee, $carbon)) {
            return new DayStatus(DayStatus::WEEKLY_OFF);
        }

        // ── 2–5. Holiday checks (most-specific first) ─────────────────
        // Employee-specific
        $holiday = $this->resolveHoliday($employee, $dateStr, 'employee_specific');
        if ($holiday) {
            return new DayStatus(DayStatus::HOLIDAY, DayStatus::HOLIDAY_EMPLOYEE, $holiday->title, $holiday->id);
        }

        // Department
        $holiday = $this->resolveHoliday($employee, $dateStr, 'department');
        if ($holiday) {
            return new DayStatus(DayStatus::HOLIDAY, DayStatus::HOLIDAY_DEPT, $holiday->title, $holiday->id);
        }

        // Branch
        $holiday = $this->resolveHoliday($employee, $dateStr, 'branch');
        if ($holiday) {
            return new DayStatus(DayStatus::HOLIDAY, DayStatus::HOLIDAY_BRANCH, $holiday->title, $holiday->id);
        }

        // Global (company-wide)
        $holiday = $this->resolveHoliday($employee, $dateStr, 'global');
        if ($holiday) {
            return new DayStatus(DayStatus::HOLIDAY, DayStatus::HOLIDAY_GLOBAL, $holiday->title, $holiday->id);
        }

        // ── 6. Weekend ────────────────────────────────────────────────
        if ($carbon->isWeekend()) {
            return new DayStatus(DayStatus::WEEKEND);
        }

        // ── 7. Normal working day ─────────────────────────────────────
        return new DayStatus(DayStatus::WORKING);
    }

    // ── Shorthand aliases (clean names for call-sites) ─────────────────

    /**
     * True if $date is an active weekly off day for $employee.
     */
    public function isWeeklyOff(Employee $employee, Carbon|string $date): bool
    {
        return $this->isWeeklyOffForEmployee($employee, $date);
    }

    /**
     * True if $date falls under any holiday that applies to $employee
     * (global, branch, department, or employee-specific).
     */
    public function isHoliday(Employee $employee, Carbon|string $date): bool
    {
        return $this->isHolidayForEmployee($employee, $date);
    }

    // ── Private helpers ────────────────────────────────────────────────

    /**
     * Fetch the first matching active Holiday of a given type for the
     * employee on a given date.  Returns null when no match.
     *
     * @param  'global'|'branch'|'department'|'employee_specific'  $type
     */
    private function resolveHoliday(Employee $employee, string $dateStr, string $type): ?Holiday
    {
        $query = Holiday::active()->betweenDates($dateStr)->where('type', $type);

        return match ($type) {
            'global' => $query->first(),

            'branch' => $employee->branch_id
                ? $query->where('branch_id', $employee->branch_id)->first()
                : null,

            'department' => $employee->department_id
                ? $query->where('department_id', $employee->department_id)->first()
                : null,

            'employee_specific' => $query
                ->whereHas('employees', fn ($q) => $q->whereKey($employee->id))
                ->first(),

            default => null,
        };
    }
}
