<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;

class HolidayCalendarService
{
    public function isHolidayForEmployee(Employee $employee, Carbon|string $date): bool
    {
        $date = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return Holiday::active()
            ->betweenDates($date)
            ->where(function ($query) use ($employee) {
                $query->where('type', 'global')
                    ->orWhere(fn ($q) => $q->where('type', 'branch')->where('branch_id', $employee->branch_id))
                    ->orWhere(fn ($q) => $q->where('type', 'department')->where('department_id', $employee->department_id))
                    ->orWhere(fn ($q) => $q->where('type', 'employee_specific')->whereHas('employees', fn ($e) => $e->whereKey($employee->id)));
            })
            ->exists();
    }

    public function isWeeklyOffForEmployee(Employee $employee, Carbon|string $date): bool
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $employee->weeklyOffs()
            ->active()
            ->forDate($carbon->toDateString())
            ->where('day_of_week', strtolower($carbon->format('l')))
            ->exists();
    }

    public function isNonWorkingDay(Employee $employee, Carbon|string $date): bool
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $carbon->isWeekend()
            || $this->isHolidayForEmployee($employee, $carbon)
            || $this->isWeeklyOffForEmployee($employee, $carbon);
    }

    public function countWorkingDaysForEmployee(Employee $employee, Carbon $start, Carbon $end): int
    {
        $days = 0;
        $cursor = $start->copy()->startOfDay();

        while ($cursor->lte($end)) {
            if (! $this->isNonWorkingDay($employee, $cursor)) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }
}
