<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two schema fixes needed for the approval workflow to function correctly:
 *
 * 1. pending_registrations — three columns referenced by the application
 *    code were missing from every migration: organization_employee_code,
 *    weekly_off_days (JSON), and weekly_off_note.  Without them MySQL strict
 *    mode throws "Unknown column" on registration submission.
 *
 * 2. employees — the `designation` and `department` string columns were
 *    created NOT NULL with no default.  Because department_id is optional
 *    on a pending registration, the name lookup may return null, causing
 *    the approval INSERT to fail with a NOT NULL constraint violation.
 *    Making them nullable lets the FK columns (designation_id / department_id)
 *    be the authoritative reference.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add missing columns to pending_registrations ───────────
        Schema::table('pending_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('pending_registrations', 'organization_employee_code')) {
                $table->string('organization_employee_code', 80)->nullable()->after('email');
            }
            if (! Schema::hasColumn('pending_registrations', 'weekly_off_days')) {
                $table->json('weekly_off_days')->nullable()->after('shift_id');
            }
            if (! Schema::hasColumn('pending_registrations', 'weekly_off_note')) {
                $table->string('weekly_off_note', 500)->nullable()->after('weekly_off_days');
            }
        });

        // ── 2. Make employees.designation and department nullable ─────
        // MySQL requires MODIFY COLUMN to change nullability; SQLite stores
        // everything as text so no DDL change is needed.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE employees MODIFY COLUMN designation VARCHAR(255) NULL");
            DB::statement("ALTER TABLE employees MODIFY COLUMN department  VARCHAR(255) NULL");
        }
    }

    public function down(): void
    {
        // Revert employees columns to NOT NULL (best-effort — data must allow it)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE employees MODIFY COLUMN designation VARCHAR(255) NOT NULL");
            DB::statement("ALTER TABLE employees MODIFY COLUMN department  VARCHAR(255) NOT NULL");
        }

        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('pending_registrations', 'organization_employee_code') ? 'organization_employee_code' : null,
                Schema::hasColumn('pending_registrations', 'weekly_off_days')           ? 'weekly_off_days'           : null,
                Schema::hasColumn('pending_registrations', 'weekly_off_note')           ? 'weekly_off_note'           : null,
            ]));
        });
    }
};
