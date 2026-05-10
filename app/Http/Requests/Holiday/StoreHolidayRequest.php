<?php

namespace App\Http\Requests\Holiday;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'hr']) ?? false;
    }

    public function rules(): array
    {
        return [
            'holiday_year'       => ['required', 'integer', 'min:2000', 'max:2100'],
            'title'              => ['required', 'string', 'max:255'],
            'reason'             => ['nullable', 'string', 'max:2000'],
            'start_date'         => ['required', 'date'],
            'end_date'           => ['required', 'date', 'after_or_equal:start_date'],
            'type'               => ['required', Rule::in(['global', 'branch', 'department', 'employee_specific'])],
            'branch_id'          => ['nullable', 'required_if:type,branch', 'exists:branches,id'],
            'department_id'      => ['nullable', 'required_if:type,department', 'exists:departments,id'],
            'employee_ids'       => ['array', 'required_if:type,employee_specific'],
            'employee_ids.*'     => ['integer', 'exists:employees,id'],
            'notify_before_days' => ['required', 'integer', 'min:0', 'max:30'],
            'status'             => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * Cross-field and business-rule checks that run AFTER the base rules pass.
     * Uses the template method pattern so UpdateHolidayRequest can override
     * validateNoOverlap() to exclude the record being updated.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $this->validateYearMatchesStartDate($v);
            $this->validateNoOverlap($v);
        });
    }

    // ── Cross-field validators ──────────────────────────────────────────

    /**
     * holiday_year must equal the calendar year of start_date.
     * Prevents a holiday filed under "2025" from having a start_date in 2026.
     */
    private function validateYearMatchesStartDate(Validator $v): void
    {
        $startDate   = $this->input('start_date');
        $holidayYear = (int) $this->input('holiday_year');

        if (! $startDate || ! $holidayYear) {
            return; // base rules already flag these as required
        }

        try {
            $startYear = (int) Carbon::parse($startDate)->format('Y');
        } catch (\Throwable) {
            return; // invalid date already caught by the 'date' rule
        }

        if ($startYear !== $holidayYear) {
            $v->errors()->add(
                'holiday_year',
                "Holiday year ({$holidayYear}) must match the year of Start Date ({$startYear})."
            );
        }
    }

    /**
     * Prevent creating a holiday that overlaps an existing active holiday
     * of the same type and scope.
     *
     * Two date ranges [A, B] and [C, D] overlap when A <= D AND B >= C.
     *
     * Employee-specific holidays are excluded: the same date can legitimately
     * appear in multiple employee-specific groups simultaneously.
     *
     * @param  int|null  $ignoreId  Holiday ID to exclude (used when updating).
     */
    protected function validateNoOverlap(Validator $v, ?int $ignoreId = null): void
    {
        $type      = $this->input('type');
        $startDate = $this->input('start_date');
        $endDate   = $this->input('end_date');

        if (! $type || ! $startDate || ! $endDate) {
            return; // missing fields already flagged by base rules
        }

        // Shared dates across different employee-specific groups are allowed
        if ($type === 'employee_specific') {
            return;
        }

        $query = Holiday::where('status', 'active')
            ->where('type', $type)
            // Range overlap: existing.start <= new.end AND existing.end >= new.start
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);

        if ($type === 'branch') {
            $query->where('branch_id', $this->input('branch_id'));
        } elseif ($type === 'department') {
            $query->where('department_id', $this->input('department_id'));
        }

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            $v->errors()->add(
                'start_date',
                'An active holiday of the same type and scope already covers this date range. '
                    . 'Please adjust the dates or deactivate the conflicting holiday first.'
            );
        }
    }
}
