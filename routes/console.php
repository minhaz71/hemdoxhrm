<?php

use Illuminate\Support\Facades\Schedule;

// ── Daily: mark employees who never checked in as absent ──────────────────
// Runs at 00:05 every day (after midnight, capturing the full previous day)
Schedule::command('hrms:mark-absent')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// ── Monthly: generate payroll for last month ──────────────────────────────
// Runs at 01:00 on the 1st of every month
Schedule::command('hrms:generate-payroll')
    ->monthlyOn(1, '01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// ── Monthly: generate payslip PDFs for last month (after payroll) ─────────
// Runs at 02:00 on the 1st — 1 hour after payroll generation completes
Schedule::command('hrms:generate-payslips')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// ── Daily: send upcoming holiday notifications ───────────────────────────
// Main run at 08:00 — sends reminders for holidays due today
Schedule::command('hrms:send-holiday-emails')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Retry run at 10:00 — re-attempts any emails that failed at 08:00
// (e.g. transient SMTP errors, rate limits, etc.)
Schedule::command('hrms:send-holiday-emails --retry-failed')
    ->dailyAt('10:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
