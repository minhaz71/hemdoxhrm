<?php

namespace App\Support;

/**
 * Immutable value object describing what a calendar date means for a
 * specific employee — working day, weekend, weekly off, or holiday.
 *
 * Used by AttendanceCalendarService::getDayStatus().
 */
readonly class DayStatus
{
    // ── Status constants ───────────────────────────────────────────────
    public const WORKING    = 'working';
    public const WEEKEND    = 'weekend';
    public const WEEKLY_OFF = 'weekly_off';
    public const HOLIDAY    = 'holiday';

    // ── Holiday type constants (mirrors Holiday::type enum) ───────────
    public const HOLIDAY_GLOBAL    = 'global';
    public const HOLIDAY_BRANCH    = 'branch';
    public const HOLIDAY_DEPT      = 'department';
    public const HOLIDAY_EMPLOYEE  = 'employee_specific';

    /** Whether this day is a normal working day for the employee. */
    public bool $isWorkingDay;

    /**
     * @param  string       $status       One of the STATUS_* constants above.
     * @param  string|null  $holidayType  Non-null only when $status === 'holiday'.
     *                                   One of the HOLIDAY_* constants.
     * @param  string|null  $holidayTitle Human-readable holiday name (nullable).
     * @param  int|null     $holidayId    PK of the matching Holiday row (nullable).
     */
    public function __construct(
        public readonly string  $status,
        public readonly ?string $holidayType  = null,
        public readonly ?string $holidayTitle = null,
        public readonly ?int    $holidayId    = null,
    ) {
        $this->isWorkingDay = ($status === self::WORKING);
    }

    // ── Convenience predicates ─────────────────────────────────────────

    public function isWorking(): bool    { return $this->status === self::WORKING; }
    public function isWeekend(): bool    { return $this->status === self::WEEKEND; }
    public function isWeeklyOff(): bool  { return $this->status === self::WEEKLY_OFF; }
    public function isHoliday(): bool    { return $this->status === self::HOLIDAY; }

    /**
     * Returns the attendance record status that maps to this day type.
     * Used by the cron to create the correct attendance row when
     * holiday_attendance_record = 'record'.
     */
    public function toAttendanceStatus(): string
    {
        return match ($this->status) {
            self::HOLIDAY    => 'holiday',
            self::WEEKLY_OFF => 'weekly_off',
            self::WEEKEND    => 'weekly_off',   // weekends recorded the same as weekly off
            default          => 'absent',
        };
    }

    /**
     * A human-readable note to store on the auto-created attendance row.
     */
    public function toAttendanceNote(): string
    {
        return match ($this->status) {
            self::HOLIDAY => trim(implode(' ', array_filter([
                'Holiday:',
                $this->holidayTitle,
                $this->holidayType ? "({$this->holidayType})" : null,
            ]))),
            self::WEEKLY_OFF => 'Weekly off day',
            self::WEEKEND    => 'Weekend',
            default          => '',
        };
    }
}
