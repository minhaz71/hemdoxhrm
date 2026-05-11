<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollRegenerationLog;
use App\Models\SalarySnapshot;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PayrollRegenerationService
{
    public function __construct(
        private readonly PayrollService  $payrollService,
        private readonly PayslipService  $payslipService,
    ) {}

    // ── Single employee regeneration ──────────────────────────────

    /**
     * Regenerate payroll for one employee in a given period.
     *
     * Unpaid (draft/processed): delete old record and regenerate cleanly.
     * Paid/locked:              requires admin + reason; preserves old data in log,
     *                           resets payroll to draft for re-review & re-payment.
     *
     * @throws ValidationException
     */
    public function regenerate(
        Employee $employee,
        int      $month,
        int      $year,
        User     $actor,
        ?string  $reason = null,
        bool     $forceOverrideLocked = false,
    ): Payroll {
        $existing = Payroll::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (! $existing) {
            // No existing payroll — just generate fresh
            return $this->payrollService->generate($employee, $month, $year, []);
        }

        if ($existing->isLocked()) {
            // Locked payrolls require explicit admin override
            if (! $forceOverrideLocked) {
                throw ValidationException::withMessages([
                    'payroll' => 'This payroll is paid/locked. Admin override with a reason is required to regenerate.',
                ]);
            }

            if (! $actor->isAdmin()) {
                throw ValidationException::withMessages([
                    'payroll' => 'Only administrators can regenerate a paid/locked payroll.',
                ]);
            }

            if (empty(trim($reason ?? ''))) {
                throw ValidationException::withMessages([
                    'reason' => 'A reason is required when regenerating a locked payroll.',
                ]);
            }

            return $this->regenerateLocked($existing, $employee, $month, $year, $actor, $reason);
        }

        // Unpaid — normal regeneration (delete + recreate)
        return $this->regenerateUnpaid($existing, $employee, $month, $year, $actor);
    }

    // ── Bulk regeneration for entire period ───────────────────────

    /**
     * Regenerate payroll for ALL employees in a period.
     * Only regenerates unpaid (draft/processed) by default.
     * Pass $includeLockedWithReason to also force-regenerate locked ones.
     */
    public function regenerateBulk(
        int     $month,
        int     $year,
        User    $actor,
        bool    $includeLockedWithReason = false,
        ?string $reason = null,
    ): array {
        $payrolls = Payroll::with('employee')
            ->forPeriod($month, $year)
            ->get();

        $results = ['regenerated' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($payrolls as $payroll) {
            $employee = $payroll->employee;
            if (! $employee) {
                $results['skipped']++;
                continue;
            }

            if ($payroll->isLocked() && ! $includeLockedWithReason) {
                $results['skipped']++;
                continue;
            }

            try {
                $this->regenerate(
                    $employee,
                    $month,
                    $year,
                    $actor,
                    $reason,
                    $includeLockedWithReason && $payroll->isLocked(),
                );
                $results['regenerated']++;
            } catch (\Throwable $e) {
                $results['errors'][] = "{$employee->full_name}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    // ── Audit log query ───────────────────────────────────────────

    public function paginateLogs(int $perPage = 20): LengthAwarePaginator
    {
        return PayrollRegenerationLog::with(['employee', 'regeneratedBy', 'payroll'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function logsForEmployee(Employee $employee, int $perPage = 15): LengthAwarePaginator
    {
        return PayrollRegenerationLog::forEmployee($employee->id)
            ->with(['regeneratedBy', 'payroll'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    // ── Private helpers ───────────────────────────────────────────

    /**
     * Regenerate an UNPAID payroll.
     * Deletes old record + snapshot, creates fresh one, logs it.
     */
    private function regenerateUnpaid(
        Payroll  $existing,
        Employee $employee,
        int      $month,
        int      $year,
        User     $actor,
    ): Payroll {
        $oldSnapshot = $this->captureSnapshot($existing);

        // Delete any associated payslip first
        $this->deletePayslipForPayroll($existing);

        // Delegate to PayrollService (force=true — it handles delete + recreate)
        $newPayroll = $this->payrollService->regenerate($employee, $month, $year);

        $this->logRegeneration(
            payroll:    $newPayroll,
            employee:   $employee,
            month:      $month,
            year:       $year,
            wasLocked:  false,
            oldSnapshot: $oldSnapshot,
            newPayroll: $newPayroll,
            reason:     null,
            actor:      $actor,
        );

        return $newPayroll;
    }

    /**
     * Regenerate a PAID/LOCKED payroll.
     * Keeps the existing payroll record but resets it with new data.
     * Preserves old data in regeneration log.
     * Does NOT delete the old payroll — updates it in-place, resetting to draft.
     */
    private function regenerateLocked(
        Payroll  $existing,
        Employee $employee,
        int      $month,
        int      $year,
        User     $actor,
        string   $reason,
    ): Payroll {
        $oldSnapshot = $this->captureSnapshot($existing);

        // Delete associated payslip file (the PDF is outdated after regeneration)
        $this->deletePayslipForPayroll($existing);

        // Delete old salary snapshot so it can be recreated cleanly
        $existing->salarySnapshot?->delete();

        // Use PayrollService's force-regenerate (which resets the payroll in-place)
        // We need to bypass the isLocked guard — do this by directly calling generate with force
        // PayrollService::generate(force=true) will throw for locked — we call it knowing
        // the existing record is locked, so we temporarily unlock it here
        $existing->update([
            'status'  => 'draft',   // Temporarily unlock so generate() can delete it
            'paid_at' => null,
            'paid_by' => null,
        ]);

        // Now force-regenerate (the record is now a draft, so it won't be rejected)
        $newPayroll = $this->payrollService->regenerate($employee, $month, $year);

        $this->logRegeneration(
            payroll:     $newPayroll,
            employee:    $employee,
            month:       $month,
            year:        $year,
            wasLocked:   true,
            oldSnapshot: $oldSnapshot,
            newPayroll:  $newPayroll,
            reason:      $reason,
            actor:       $actor,
        );

        return $newPayroll;
    }

    /**
     * Capture all payroll fields as a snapshot array for audit.
     */
    private function captureSnapshot(Payroll $payroll): array
    {
        return $payroll->toArray();
    }

    /**
     * Delete payslip record and its PDF file (if exists) for this payroll.
     */
    private function deletePayslipForPayroll(Payroll $payroll): void
    {
        $payslip = $payroll->payslip;
        if (! $payslip) {
            return;
        }

        if ($payslip->fileExists()) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($payslip->file_path);
        }

        $payslip->delete();
    }

    /**
     * Write an entry to payroll_regeneration_logs.
     */
    private function logRegeneration(
        Payroll  $payroll,
        Employee $employee,
        int      $month,
        int      $year,
        bool     $wasLocked,
        array    $oldSnapshot,
        Payroll  $newPayroll,
        ?string  $reason,
        User     $actor,
    ): void {
        PayrollRegenerationLog::create([
            'payroll_id'     => $payroll->id,
            'employee_id'    => $employee->id,
            'month'          => $month,
            'year'           => $year,
            'was_locked'     => $wasLocked,
            'old_snapshot'   => $oldSnapshot,
            'new_snapshot'   => $newPayroll->toArray(),
            'reason'         => $reason,
            'regenerated_by' => $actor->id,
        ]);
    }
}
