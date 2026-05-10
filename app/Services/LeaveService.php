<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function __construct(private readonly NotificationService $notifications) {}

    // ── Apply ─────────────────────────────────────────────────────

    public function apply(Employee $employee, array $data): Leave
    {
        $leaveType = LeaveType::findOrFail($data['leave_type_id']);

        $startDate = Carbon::parse($data['start_date']);
        $endDate   = Carbon::parse($data['end_date']);
        $totalDays = $this->countWorkingDays($startDate, $endDate);

        // Block overlapping pending/approved leaves
        $this->guardOverlap($employee->id, $data['start_date'], $data['end_date']);

        // Check annual limit — auto-downgrade to unpaid if exceeded
        $isUnpaidOverride = false;
        if ($leaveType->days_allowed > 0) {
            $used      = $this->usedDays($employee->id, $leaveType->id, $startDate->year);
            $remaining = $leaveType->days_allowed - $used;

            if ($totalDays > $remaining) {
                $isUnpaidOverride = true;
            }
        }

        $leave = Leave::create([
            'employee_id'        => $employee->id,
            'leave_type_id'      => $leaveType->id,
            'start_date'         => $data['start_date'],
            'end_date'           => $data['end_date'],
            'total_days'         => $totalDays,
            'is_unpaid_override' => $isUnpaidOverride,
            'reason'             => $data['reason'] ?? null,
            'status'             => 'pending',
        ]);

        $this->notifications->leaveApplied($leave->fresh(['employee.user', 'employee.teamLeader.user', 'leaveType']));

        return $leave;
    }

    // ── Approve ───────────────────────────────────────────────────

    public function approve(Leave $leave, User $approver): Leave
    {
        $this->guardAction($leave);

        $leave->update([
            'status'       => 'approved',
            'approved_by'  => $approver->id,
            'actioned_at'  => now(),
            'rejection_note' => null,
        ]);

        $fresh = $leave->fresh(['employee.user', 'leaveType', 'approvedBy']);
        $this->notifications->leaveApproved($fresh);

        return $fresh;
    }

    // ── Reject ────────────────────────────────────────────────────

    public function reject(Leave $leave, User $approver, string $note): Leave
    {
        $this->guardAction($leave);

        $leave->update([
            'status'         => 'rejected',
            'approved_by'    => $approver->id,
            'actioned_at'    => now(),
            'rejection_note' => $note,
        ]);

        $fresh = $leave->fresh(['employee.user', 'leaveType', 'approvedBy']);
        $this->notifications->leaveRejected($fresh);

        return $fresh;
    }

    // ── Cancel (by employee, only when pending) ───────────────────

    public function cancel(Leave $leave): void
    {
        if ($leave->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending leaves can be cancelled.',
            ]);
        }

        $leave->delete();
    }

    // ── Queries ───────────────────────────────────────────────────

    public function paginateAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Leave::with(['employee', 'leaveType', 'approvedBy'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['employee_id'] ?? null, fn ($q, $id) => $q->forEmployee($id))
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForEmployee(Employee $employee, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Leave::with(['leaveType', 'approvedBy'])
            ->forEmployee($employee->id)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($perPage);
    }

    public function balanceFor(Employee $employee, int $year): array
    {
        $types = LeaveType::active()->get();

        // Load all used days for this employee+year in a single grouped query
        // instead of hitting the DB once per leave type.
        $usedMap = Leave::forEmployee($employee->id)
            ->forYear($year)
            ->whereIn('status', ['pending', 'approved'])
            ->selectRaw('leave_type_id, SUM(total_days) as used')
            ->groupBy('leave_type_id')
            ->pluck('used', 'leave_type_id');

        return $types->map(function (LeaveType $type) use ($usedMap) {
            $used      = (int) ($usedMap[$type->id] ?? 0);
            $allowed   = $type->days_allowed;
            $remaining = $allowed > 0 ? max(0, $allowed - $used) : null;

            return [
                'type'      => $type->name,
                'slug'      => $type->slug,
                'allowed'   => $allowed,
                'used'      => $used,
                'remaining' => $remaining,
                'is_paid'   => $type->is_paid,
            ];
        })->toArray();
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function usedDays(int $employeeId, int $leaveTypeId, int $year): int
    {
        return (int) Leave::forEmployee($employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->forYear($year)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('total_days');
    }

    private function countWorkingDays(Carbon $start, Carbon $end): int
    {
        $days  = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if (! $current->isWeekend()) {
                $days++;
            }
            $current->addDay();
        }

        return max(1, $days);
    }

    private function guardOverlap(int $employeeId, string $start, string $end): void
    {
        $overlap = Leave::forEmployee($employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($q) use ($start, $end) {
                      $q->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $end);
                  });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => 'You already have a leave application overlapping these dates.',
            ]);
        }
    }

    private function guardAction(Leave $leave): void
    {
        if ($leave->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending leaves can be actioned.',
            ]);
        }
    }
}
