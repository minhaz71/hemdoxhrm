<?php

namespace App\Console\Commands;

use App\Services\HolidayNotificationService;
use Illuminate\Console\Command;

class SendHolidayEmails extends Command
{
    protected $signature = 'hrms:send-holiday-emails
        {--retry-failed : Re-attempt previously failed email deliveries}
        {--dry-run      : Preview without actually sending}';

    protected $description = 'Send holiday reminder emails to employees before upcoming holidays';

    public function __construct(private readonly HolidayNotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $retryFailed = (bool) $this->option('retry-failed');
        $dryRun      = (bool) $this->option('dry-run');

        $this->line('');

        // ── Retry failed mode ─────────────────────────────────────────
        if ($retryFailed) {
            $this->info('  🔄  Retrying previously failed holiday emails…');

            if ($dryRun) {
                $this->warn('  ⚠️   Dry-run: no emails will actually be sent.');
                $this->line('');
                return self::SUCCESS;
            }

            $stats = $this->notifications->retryFailed();

            $this->line('');
            $this->table(
                ['Result', 'Count'],
                [
                    ['✅ Re-sent successfully', $stats['sent']],
                    ['❌ Still failed',         $stats['failed']],
                ]
            );
            $this->line('');

            return self::SUCCESS;
        }

        // ── Normal daily run ──────────────────────────────────────────
        $this->info('  📨  Sending holiday reminders for <comment>' . today()->toDateString() . '</comment>');

        if ($dryRun) {
            $this->warn('  ⚠️   Dry-run mode — no emails will be sent.');
            $this->line('');
            return self::SUCCESS;
        }

        $stats = $this->notifications->sendDueEmails();

        $this->line('');

        if ($stats['holidays'] === 0) {
            $this->info('  ℹ️   No holidays due for notification today.');
            $this->line('');
            return self::SUCCESS;
        }

        $this->table(
            ['Category',                           'Count'],
            [
                ['🎉 Holidays processed',            $stats['holidays']],
                ['✅ Emails sent',                   $stats['sent']],
                ['❌ Failed (retried next run)',      $stats['failed']],
                ['⏭️  Skipped (already delivered)',   $stats['skipped']],
            ]
        );

        if ($stats['failed'] > 0) {
            $this->warn("  ⚠️   {$stats['failed']} email(s) failed. Run with --retry-failed to retry immediately.");
        }

        $this->line('');

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
