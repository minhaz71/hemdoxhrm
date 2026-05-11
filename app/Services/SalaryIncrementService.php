<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalaryHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalaryIncrementService
{
    // ── Read helpers ──────────────────────────────────────────────

    /**
     * Paginated list of non-initial salary_histories.
     * Filters: employee_id, status, salary_type, per_page.
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = SalaryHistory::with(['employee', 'changedBy', 'approvedBy'])
            ->whereIn('salary_type', [
                SalaryHistory::TYPE_INCREMENT,
                SalaryHistory::TYPE_DECREMENT,
                SalaryHistory::TYPE_ADJUSTMENT,
            ]);

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['salary_type'])) {
            $query->where('salary_type', $filters['salary_type']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * All pending records, newest first, with employee/changedBy loaded.
     */
    public function pendingApprovals(): Collection
    {
        return SalaryHistory::with(['employee', 'changedBy'])
            ->whereIn('salary_type', [
                SalaryHistory::TYPE_INCREMENT,
                SalaryHistory::TYPE_DECREMENT,
                SalaryHistory::TYPE_ADJUSTMENT,
            ])
            ->where('status', SalaryHistory::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get the most recent approved salary_history base_salary for an employee.
     * Does NOT use employee.base_salary.
     */
    public function currentSalaryFromHistory(Employee $employee): float
    {
        $record = SalaryHistory::where('employee_id', $employee->id)
            ->where('status', SalaryHistory::STATUS_APPROVED)
            ->orderByDesc('effective_from')
            ->orderByDesc('created_at')
            ->first();

        return $record ? (float) $record->base_salary : 0.0;
    }

    /**
     * Calculate new salary from a fixed increment amount.
     */
    public function calculateFromAmount(float $prevSalary, float $incrementAmount): array
    {
        $newSalary = $prevSalary + $incrementAmount;
        $pct = $prevSalary > 0 ? ($incrementAmount / $prevSalary) * 100 : 0.0;

        return [
            'new_salary'           => round($newSalary, 2),
            'increment_amount'     => round($incrementAmount, 2),
            'increment_percentage' => round($pct, 4),
        ];
    }

    /**
     * Calculate new salary from an increment percentage.
     */
    public function calculateFromPercentage(float $prevSalary, float $pct): array
    {
        $incrementAmount = $prevSalary * ($pct / 100);
        $newSalary = $prevSalary + $incrementAmount;

        return [
            'new_salary'           => round($newSalary, 2),
            'increment_amount'     => round($incrementAmount, 2),
            'increment_percentage' => round($pct, 4),
        ];
    }

    /**
     * Create a new salary increment/decrement/adjustment record.
     *
     * Logic:
     * - current salary comes from salary_histories (NOT employee.base_salary)
     * - admin → status=approved, timeline rewritten immediately
     * - HR → status=pending, no timeline change
     * - DO NOT update employees.base_salary
     */
    public function store(Employee $employee, array $data, User $actor): SalaryHistory
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $effectiveFrom = Carbon::parse($data['effective_from'])->startOfMonth()->toDateString();
            $isAdmin       = $actor->isAdmin();

            // Get current salary from history (not employee.base_salary)
            $currentSalary = $this->currentSalaryFromHistory($employee);

            // Resolve new salary, amount, percentage
            if (!empty($data['increment_amount'])) {
                $calc = $this->calculateFromAmount($currentSalary, (float) $data['increment_amount']);
            } elseif (!empty($data['increment_percentage'])) {
                $calc = $this->calculateFromPercentage($currentSalary, (float) $data['increment_percentage']);
            } elseif (!empty($data['new_salary'])) {
                $newSalary       = (float) $data['new_salary'];
                $incrementAmount = $newSalary - $currentSalary;
                $pct             = $currentSalary > 0 ? ($incrementAmount / $currentSalary) * 100 : 0.0;
                $calc = [
                    'new_salary'           => round($newSalary, 2),
                    'increment_amount'     => round($incrementAmount, 2),
                    'increment_percentage' => round($pct, 4),
                ];
            } else {
                throw ValidationException::withMessages([
                    'new_salary' => 'Provide new_salary, increment_amount, or increment_percentage.',
                ]);
            }

            $status = $isAdmin ? SalaryHistory::STATUS_APPROVED : SalaryHistory::STATUS_PENDING;

            // For admin-approved records, rewrite the timeline
            if ($isAdmin) {
                $this->rewriteTimeline($employee, $effectiveFrom);
            }

            $record = SalaryHistory::create([
                'employee_id'          => $employee->id,
                'previous_salary'      => $currentSalary ?: null,
                'base_salary'          => $calc['new_salary'],
                'increment_amount'     => $calc['increment_amount'],
                'increment_percentage' => $calc['increment_percentage'],
                'salary_type'          => $data['salary_type'],
                'effective_from'       => $effectiveFrom,
                'effective_to'         => null,
                'reason'               => $data['reason'] ?? null,
                'note'                 => $data['note'] ?? null,
                'changed_by'           => $actor->id,
                'approved_by'          => $isAdmin ? $actor->id : null,
                'status'               => $status,
            ]);

            return $record->load(['employee', 'changedBy', 'approvedBy']);
        });
    }

    /**
     * Update a PENDING record. Only pending records can be edited.
     */
    public function update(SalaryHistory $record, array $data, User $actor): SalaryHistory
    {
        if ($record->status !== SalaryHistory::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Only pending records can be edited.',
            ]);
        }

        return DB::transaction(function () use ($record, $data, $actor) {
            $currentSalary = (float) ($record->previous_salary ?? 0.0);

            if (!empty($data['increment_amount'])) {
                $calc = $this->calculateFromAmount($currentSalary, (float) $data['increment_amount']);
            } elseif (!empty($data['increment_percentage'])) {
                $calc = $this->calculateFromPercentage($currentSalary, (float) $data['increment_percentage']);
            } elseif (!empty($data['new_salary'])) {
                $newSalary       = (float) $data['new_salary'];
                $incrementAmount = $newSalary - $currentSalary;
                $pct             = $currentSalary > 0 ? ($incrementAmount / $currentSalary) * 100 : 0.0;
                $calc = [
                    'new_salary'           => round($newSalary, 2),
                    'increment_amount'     => round($incrementAmount, 2),
                    'increment_percentage' => round($pct, 4),
                ];
            } else {
                throw ValidationException::withMessages([
                    'new_salary' => 'Provide new_salary, increment_amount, or increment_percentage.',
                ]);
            }

            $effectiveFrom = isset($data['effective_from'])
                ? Carbon::parse($data['effective_from'])->startOfMonth()->toDateString()
                : $record->effective_from->toDateString();

            $record->update([
                'base_salary'          => $calc['new_salary'],
                'increment_amount'     => $calc['increment_amount'],
                'increment_percentage' => $calc['increment_percentage'],
                'salary_type'          => $data['salary_type'] ?? $record->salary_type,
                'effective_from'       => $effectiveFrom,
                'reason'               => $data['reason'] ?? $record->reason,
                'note'                 => $data['note'] ?? $record->note,
            ]);

            return $record->fresh(['employee', 'changedBy', 'approvedBy']);
        });
    }

    /**
     * Approve a pending record (admin only).
     * Rewrites the salary timeline. Does NOT touch employees.base_salary.
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

            // Close any open approved record before this one
            $this->rewriteTimeline($employee, $effectiveFrom, $record->id);

            // Recalculate previous_salary at approval time
            $previousSalary = $this->resolvePreviewSalary($employee, $effectiveFrom, $record->id);

            // Recalculate increment based on resolved previous salary
            $newSalary = (float) $record->base_salary;
            if ($previousSalary !== null) {
                $incrementAmount     = $newSalary - $previousSalary;
                $incrementPercentage = $previousSalary > 0 ? ($incrementAmount / $previousSalary) * 100 : 0.0;
            } else {
                $incrementAmount     = $record->increment_amount;
                $incrementPercentage = $record->increment_percentage;
            }

            $record->update([
                'status'               => SalaryHistory::STATUS_APPROVED,
                'approved_by'          => $admin->id,
                'previous_salary'      => $previousSalary,
                'increment_amount'     => round($incrementAmount, 2),
                'increment_percentage' => round($incrementPercentage, 4),
            ]);

            return $record->fresh(['employee', 'changedBy', 'approvedBy']);
        });
    }

    /**
     * Reject a pending record.
     */
    public function reject(SalaryHistory $record, User $admin, ?string $note): SalaryHistory
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

        return $record->fresh(['employee', 'changedBy', 'approvedBy']);
    }

    // ── Private helpers ───────────────────────────────────────────

    /**
     * Close the currently open approved record for the employee
     * that started before $effectiveFrom.
     */
    private function rewriteTimeline(Employee $employee, string $effectiveFrom, ?int $excludeId = null): void
    {
        $base = SalaryHistory::where('employee_id', $employee->id)
            ->where('status', SalaryHistory::STATUS_APPROVED)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));

        // Close any open record that started before this effectiveFrom
        (clone $base)
            ->whereNull('effective_to')
            ->where('effective_from', '<', $effectiveFrom)
            ->update([
                'effective_to' => Carbon::parse($effectiveFrom)->subDay()->toDateString(),
                'updated_at'   => now(),
            ]);
    }

    /**
     * What was the salary just before $effectiveFrom for this employee?
     */
    private function resolvePreviewSalary(Employee $employee, string $effectiveFrom, ?int $excludeId = null): ?float
    {
        $record = SalaryHistory::where('employee_id', $employee->id)
            ->where('status', SalaryHistory::STATUS_APPROVED)
            ->where('effective_from', '<', $effectiveFrom)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderByDesc('effective_from')
            ->first();

        return $record ? (float) $record->base_salary : null;
    }
}
