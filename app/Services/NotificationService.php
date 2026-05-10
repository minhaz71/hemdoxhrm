<?php

namespace App\Services;

use App\Mail\LeaveAppliedMail;
use App\Mail\LeaveApprovedMail;
use App\Mail\LeaveRejectedMail;
use App\Mail\PayrollGeneratedMail;
use App\Mail\WarningMail;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function leaveApplied(Leave $leave): void
    {
        $leave->loadMissing(['employee.user', 'employee.teamLeader.user', 'leaveType']);

        foreach ($this->leaveApplicationRecipients() as $email) {
            $this->send($email, new LeaveAppliedMail($leave));
        }
    }

    public function leaveApproved(Leave $leave): void
    {
        $leave->loadMissing(['employee.user', 'employee.teamLeader.user', 'leaveType', 'approvedBy']);
        $email = $this->employeeEmail($leave->employee);
        if ($email) {
            $this->send($email, new LeaveApprovedMail($leave));
        }

        foreach ($this->teamLeaderAndConfiguredRecipients($leave->employee, 'leave_approval_cc_emails', [$email]) as $recipient) {
            $this->send($recipient, new LeaveApprovedMail($leave));
        }
    }

    public function leaveRejected(Leave $leave): void
    {
        $email = $this->employeeEmail($leave->employee);
        if (! $email) return;

        $this->send($email, new LeaveRejectedMail($leave));
    }

    public function payrollGenerated(Payroll $payroll): void
    {
        $payroll->loadMissing(['employee.user', 'employee.teamLeader.user']);
        $email = $this->employeeEmail($payroll->employee);
        if ($email) {
            $this->send($email, new PayrollGeneratedMail($payroll));
        }

        foreach ($this->teamLeaderAndConfiguredRecipients($payroll->employee, 'payslip_cc_emails', [$email]) as $recipient) {
            $this->send($recipient, new PayrollGeneratedMail($payroll));
        }
    }

    public function sendWarning(Employee $employee, string $subject, string $body): void
    {
        $email = $this->employeeEmail($employee);
        if (! $email) {
            Log::warning("Warning email skipped — no email for employee {$employee->employee_code}");
            return;
        }

        $this->send($email, new WarningMail($employee, $subject, $body));
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function employeeEmail(?Employee $employee): ?string
    {
        return $employee?->user?->email;
    }

    private function leaveApplicationRecipients(): array
    {
        $adminEmails = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->where('status', 'active')
            ->pluck('email')
            ->all();

        return $this->dedupe(array_merge(
            $adminEmails,
            $this->configuredEmails('important_update_emails'),
        ));
    }

    private function teamLeaderAndConfiguredRecipients(Employee $employee, string $settingKey, array $exclude = []): array
    {
        $leaderEmail = $employee->teamLeader?->user?->email;

        return array_values(array_diff($this->dedupe(array_merge(
            $leaderEmail ? [$leaderEmail] : [],
            $this->configuredEmails($settingKey),
        )), $this->dedupe($exclude)));
    }

    private function configuredEmails(string $key): array
    {
        $raw = (string) app(SettingService::class)->get($key, '');

        return collect(preg_split('/[\s,;]+/', $raw) ?: [])
            ->map(fn ($email) => strtolower(trim($email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }

    private function dedupe(array $emails): array
    {
        return collect($emails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    private function send(string $to, \Illuminate\Mail\Mailable $mail): void
    {
        try {
            Mail::to($to)->send($mail);
        } catch (\Throwable $e) {
            // Log but never let a mail failure break the primary action
            Log::error("Mail send failed to {$to}: {$e->getMessage()}");
        }
    }
}
