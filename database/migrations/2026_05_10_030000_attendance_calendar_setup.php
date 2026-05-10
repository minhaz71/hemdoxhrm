<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 1. Extend the `attendances.status` enum with 'holiday' and 'weekly_off'.
 * 2. Seed the `holiday_attendance_record` system setting that controls
 *    whether the absent cron creates attendance records for non-working days
 *    (holiday / weekly_off) or silently skips them.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Extend status enum ────────────────────────────────────────
        // MySQL enforces enum values at the column level; SQLite stores enums as
        // plain text so no DDL change is needed — any string value is accepted.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE attendances
                MODIFY COLUMN status
                ENUM('present','late','absent','underworked','holiday','weekly_off')
                NOT NULL DEFAULT 'present'
            ");
        }

        // ── Seed admin setting ────────────────────────────────────────
        // Value 'record'  → cron creates a holiday/weekly_off attendance record.
        // Value 'skip'    → cron silently skips non-working days (legacy behaviour).
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'holiday_attendance_record'],
            [
                'key'         => 'holiday_attendance_record',
                'value'       => 'skip',
                'group'       => 'attendance',
                'type'        => 'text',
                'label'       => 'Non-Working Day Attendance Recording',
                'description' => 'skip = cron silently ignores non-working days. record = cron creates a holiday/weekly_off attendance row.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE attendances
                MODIFY COLUMN status
                ENUM('present','late','absent','underworked')
                NOT NULL DEFAULT 'present'
            ");
        }

        DB::table('system_settings')->where('key', 'holiday_attendance_record')->delete();
    }
};
