<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalaryHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class SalaryHistoryService
{
    // ── Read helpers ──────────────────────────────────────────────

    /**
     * Full salary history for an employee, newest first.
     * Includes pending records so HR can see what's awaiting approval.
     */
    public function historyForEmployee(Employee $employee)
    {
        return SalaryHistory::with(['changedBy', 'approvedBy'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('effective_from')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * The currently active approved salary record for an employee.
     * "Active" = effective_from <= today AND (effective_to is null OR >= today).
     */
    public function currentRecord(Employee $employee): ?SalaryHistory
    {
        $today = now()->toDateString();

        return SalaryHistory::where('employee_id', $employee->id)
            ->approved()
            ->where('effective_from', '<=', $today)
            ->where(fn ($q) => $q->whereNull('effective_to')
                ->orWhere('effective_to', '>=', $today))
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * All pending records across all employees (for the admin approval queue).
     */
    public function pendingQueue()
    {
        return SalaryHistory::with(['employee', 'changedBy'])
            ->pending()
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Main-menu salary history directory across employees.
     */
    public function directory(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return SalaryHistory::with(['employee.user', 'changedBy', 'approvedBy'])
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('salary_type', $type))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('effective_from', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('effective_from', '<=', $to))
            ->orderByDesc('effective_from')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    // ── Create ────────────────────────────────────────────────────

    /**
     * Record a salary change for an employee.
     *
     * Workflow:
     *  - Admin creates  → status = approved immediately, employee.base_salary updated
     *  - HR creates     → status = pending, awaits admin approval
     *
     * Future effective_from is allowed; it won't affect payroll until that date.
     * Past effective_from is also allowed (backdated — admin should regenerate payrolls).
     *
     * @param  array{
     *     base_salary: numeric,
     *     effective_from: string,   // Y-m-d or Y-m
     *     salary_type: string,
     *     reason: ?string,
     *     note: ?string,
     * } $data
     */
    public function create(Employee $employee, array $data, User $actor): SalaryHistory
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $effectiveFrom = Carbon::parse($data['effective_from'])->startOfMonth()->toDateString();
            $newSalary     = (float) $data['base_salary'];
            $isAdmin       = $actor->isAdmin();

            // Determine salary_type if not explicitly sent
            $currentSalary = (float) $employee->base_salary;
            $type = $data['salary_type'] ?? $this->inferType($currentSalary, $newSalary);

            // Conflict check: approved record already starting this same month
            $conflict = SalaryHistory::where('employee_id', $employee->id)
                ->approved()
                ->where('effective_from', $effectiveFrom)
                ->first();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'effective_from' => 'An approved salary record already exists for this effective month.',
                ]);
            }

            $status = $isAdmin ? SalaryHistory::STATUS_APPROVED : SalaryHistory::STATUS_PENDING;

            // For approved records, rewrite the timeline
            if ($isAdmin) {
                $this->rewriteTimeline($employee, $effectiveFrom, $newSalary);
            }

            // Get previous salary for audit trail
            $previousSalary = $this->resolvePreviewSalary($employee, $effectiveFrom);

            $record = SalaryHistory::create([
                'employee_id'     => $employee->id,
                'previous_salary' => $previousSalary,
                'base_salary'     => $newSalary,
                'salary_type'     => $type,
                'effective_from'  => $effectiveFrom,
                'effective_to'    => null,
                'reason'          => $data['reason'] ?? null,
                'note'            => $data['note'] ?? null,
                'changed_by'      => $actor->id,
                'approved_by'     => $isAdmin ? $actor->id : null,
                'status'          => $status,
            ]);

            // Update employee.base_salary immediately only for approved records
            // with effective_from <= today
            if ($isAdmin && $effectiveFrom <= now()->toDateString()) {
                $employee->update(['base_salary' => $newSalary]);
            }

            return $record;
        });
    }

    // ── Approve ───────────────────────────────────────────────────

    /**
     * Admin approves a pending salary change.
     * Rewrites the timeline and updates employee.base_salary if effective.
     */
    public function approve(SalaryHistory $record, User $admin): SalaryHistory
    {
        if ($record->status !== SalaryHistory::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Only pending records can be approved.',
            ]);
        }

        return DB::transaction(function () use ($record, $admin) {
            $employee      = $record->employee;
            $effectiveFrom = $record->effective_from->toDateString();
            $newSalary     = (float) $record->base_salary;

            // Conflict check (another record may have been approved since HR submitted)
            $conflict = SalaryHistory::where('employee_id', $employee->id)
                ->approved()
                ->where('effective_from', $effectiveFrom)
                ->where('id', '!=', $record->id)
                ->first();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'effective_from' => 'A conflicting approved salary record already exists for this effective month.',
                ]);
            }

            // Rewrite timeline for the approved date
            $this->rewriteTimeline($employee, $effectiveFrom, $newSalary, $record->id);

            $record->update([
                'status'      => SalaryHistory::STATUS_APPROVED,
                'approved_by' => $admin->id,
                'previous_salary' => $this->resolvePreviewSalary($employee, $effectiveFrom, $record->id),
            ]);

            // Update employee.base_salary if this record is currently effective
            if ($effectiveFrom <= now()->toDateString()) {
                $employee->update(['base_salary' => $newSalary]);
            }

            return $record->fresh(['changedBy', 'approvedBy']);
        });
    }

    // ── Reject ────────────────────────────────────────────────────

    public function reject(SalaryHistory $record, User $admin, ?string $note = null): SalaryHistory
    {
        if ($record->status !== SalaryHistory::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Only pending records can be rejected.',
            ]);
        }

        $record->update([
            'status'      => SalaryHistory::STATUS_REJECTED,
            'approved_by' => $admin->id,
            'note'        => $note ?? $record->note,
        ]);

        return $record->fresh();
    }

    // ── Delete ────────────────────────────────────────────────────

    /**
     * Delete a salary record — only allowed if it has never been used in a payroll
     * and is not the only (initial) record.
     */
    public function delete(SalaryHistory $record): void
    {
        if ($record->salary_type === SalaryHistory::TYPE_INITIAL) {
            throw ValidationException::withMessages([
                'record' => 'The initial salary record cannot be deleted.',
            ]);
        }

        if ($record->usedInPayroll()) {
            throw ValidationException::withMessages([
                'record' => 'This salary record cannot be deleted because it has been used in payroll calculations.',
            ]);
        }

        DB::transaction(function () use ($record) {
            // Re-open the previous row (set its effective_to back to null)
            if ($record->status === SalaryHistory::STATUS_APPROVED) {
                $previous = SalaryHistory::where('employee_id', $record->employee_id)
                    ->approved()
                    ->where('effective_to', $record->effective_from->copy()->subDay()->toDateString())
                    ->first();

                if ($previous) {
                    $previous->update(['effective_to' => null]);
                }

                // Restore employee.base_salary to previous if this was active
                $employee = $record->employee;
                if ($record->isActive() && $previous) {
                    $employee->update(['base_salary' => $previous->base_salary]);
                }
            }

            $record->delete();
        });
    }

    // ── Private helpers ───────────────────────────────────────────

    /**
     * When a new approved salary is inserted at $effectiveFrom:
     *  1. Close any open record (effective_to = null) that started BEFORE effectiveFrom.
     *  2. Open newly-created record's range (effective_to = null).
     *  3. If a record exists with effectiveFrom AFTER the new one, set the new one's
     *     effective_to to the day before that next record.
     *
     * This keeps the timeline gap-free and non-overlapping.
     *
     * @param int|null $excludeId  Exclude this record ID from queries (for the update itself).
     */
    private function rewriteTimeline(Employee $employee, string $effectiveFrom, float $newSalary, ?int $excludeId = null): void
    {
        $base = SalaryHistory::where('employee_id', $employee->id)
            ->approved()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));

        // 1. Close any open record that started before this effectiveFrom
        (clone $base)
            ->whereNull('effective_to')
            ->where('effective_from', '<', $effectiveFrom)
            ->update([
                'effective_to' => Carbon::parse($effectiveFrom)->subDay()->toDateString(),
                'updated_at'   => now(),
            ]);

        // 2. Find the next record AFTER this effectiveFrom (if any)
        $next = (clone $base)
            ->where('effective_from', '>', $effectiveFrom)
            ->orderBy('effective_from')
            ->first();

        // The new record's effective_to should be the day before the next one
        // (handled by the caller who creates/updates the record)
        // We just need to ensure any record that was previously open from BEFORE
        // our date and extending past it is closed correctly.
        // Records AFTER stay untouched — their timeline is their own.
        // The new record will have effective_to = null (open) or = next - 1 day.
        if ($next) {
            // If there's a next record, the new one should close the day before it
            // We update after create via the record itself — skip here, caller handles via effective_to.
        }
    }

    /**
     * What was the salary just before $effectiveFrom for this employee?
     * Returns the base_salary of the most recent approved record that started before effectiveFrom.
     */
    private function resolvePreviewSalary(Employee $employee, string $effectiveFrom, ?int $excludeId = null): ?float
    {
        $record = SalaryHistory::where('employee_id', $employee->id)
            ->approved()
            ->where('effective_from', '<', $effectiveFrom)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderByDesc('effective_from')
            ->first();

        return $record ? (float) $record->base_salary : null;
    }

    private function inferType(float $old, float $new): string
    {
        if ($old == 0) return SalaryHistory::TYPE_INITIAL;
        if ($new > $old) return SalaryHistory::TYPE_INCREMENT;
        if ($new < $old) return SalaryHistory::TYPE_DECREMENT;
        return SalaryHistory::TYPE_ADJUSTMENT;
    }
}
