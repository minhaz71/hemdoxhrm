<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hardening: add missing DB-level constraints and covering indexes.
 *
 * holiday_email_logs
 *   • UNIQUE (holiday_id, employee_id)
 *     Prevents duplicate email log rows even under concurrent cron runs.
 *     MySQL allows multiple NULL values in a unique column, so nullable
 *     employee_id rows (e.g. manually created entries) are still fine.
 *
 * holidays
 *   • INDEX (status, end_date, start_date)
 *     Covers HolidayNotificationService::resolveDueHolidays():
 *       WHERE status='active' AND end_date >= ? AND start_date - INTERVAL ? DAY <= ?
 *
 *   • INDEX (type, status, start_date, end_date)
 *     Covers the overlap detection query in StoreHolidayRequest:
 *       WHERE type=? AND status='active' AND start_date <= ? AND end_date >= ?
 *     Also useful for branch_id / department_id sub-queries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holiday_email_logs', function (Blueprint $table) {
            $table->unique(
                ['holiday_id', 'employee_id'],
                'hel_holiday_employee_unique'
            );
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->index(
                ['status', 'end_date', 'start_date'],
                'holidays_status_dates_idx'
            );

            $table->index(
                ['type', 'status', 'start_date', 'end_date'],
                'holidays_type_status_overlap_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('holiday_email_logs', function (Blueprint $table) {
            $table->dropUnique('hel_holiday_employee_unique');
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropIndex('holidays_status_dates_idx');
            $table->dropIndex('holidays_type_status_overlap_idx');
        });
    }
};
