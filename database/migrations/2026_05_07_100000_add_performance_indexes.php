<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── attendances ───────────────────────────────────────────────
        // unique(employee_id, date) already covers employee+date lookups.
        // Add status index for filter queries and make check_in nullable
        // so absent records (no check-in) can be stored without error.
        Schema::table('attendances', function (Blueprint $table) {
            $table->time('check_in')->nullable()->change();
            $table->index('status', 'att_status_idx');
        });

        // ── leaves ────────────────────────────────────────────────────
        // employee_id FK has an implicit index; add composites for the
        // two heavy query patterns: usedDays() and overlap detection.
        Schema::table('leaves', function (Blueprint $table) {
            $table->index(['employee_id', 'status'],             'leaves_emp_status_idx');
            $table->index(['employee_id', 'leave_type_id'],     'leaves_emp_type_idx');
            $table->index(['start_date', 'end_date'],           'leaves_date_range_idx');
            $table->index('status',                             'leaves_status_idx');
        });

        // ── payrolls ──────────────────────────────────────────────────
        // unique(employee_id, month, year) covers per-employee lookups.
        // Add composite for period-wide queries (reports, bulk-pay).
        Schema::table('payrolls', function (Blueprint $table) {
            $table->index(['month', 'year'], 'payrolls_period_idx');
            $table->index('status',         'payrolls_status_idx');
        });

        // ── employees ─────────────────────────────────────────────────
        // active() scope and department filter are used on every report.
        Schema::table('employees', function (Blueprint $table) {
            $table->index('status',     'employees_status_idx');
            $table->index('department', 'employees_dept_idx');
        });

        // ── users ─────────────────────────────────────────────────────
        // BlockTerminated middleware checks status on every web request.
        Schema::table('users', function (Blueprint $table) {
            $table->index('status', 'users_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->time('check_in')->nullable(false)->change();
            $table->dropIndex('att_status_idx');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex('leaves_emp_status_idx');
            $table->dropIndex('leaves_emp_type_idx');
            $table->dropIndex('leaves_date_range_idx');
            $table->dropIndex('leaves_status_idx');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex('payrolls_period_idx');
            $table->dropIndex('payrolls_status_idx');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_status_idx');
            $table->dropIndex('employees_dept_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_status_idx');
        });
    }
};
