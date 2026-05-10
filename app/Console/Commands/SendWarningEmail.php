<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendWarningEmail extends Command
{
    protected $signature = 'hrms:send-warning
                            {employee  : Employee ID or employee code (e.g. EMP-0001)}
                            {--subject= : Warning subject line}
                            {--message= : Warning body text}';

    protected $description = 'Send a formal warning email to an employee';

    public function __construct(private readonly NotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $identifier = $this->argument('employee');

        $employee = is_numeric($identifier)
            ? Employee::find($identifier)
            : Employee::where('employee_code', $identifier)->first();

        if (! $employee) {
            $this->error("Employee not found: {$identifier}");
            return self::FAILURE;
        }

        $subject = $this->option('subject')
            ?? $this->ask('Warning subject');

        $body = $this->option('message')
            ?? $this->ask('Warning message body');

        if (! $subject || ! $body) {
            $this->error('Subject and message are required.');
            return self::FAILURE;
        }

        $this->notifications->sendWarning($employee, $subject, $body);

        $this->info("Warning email sent to {$employee->full_name} ({$employee->employee_code}).");

        return self::SUCCESS;
    }
}
