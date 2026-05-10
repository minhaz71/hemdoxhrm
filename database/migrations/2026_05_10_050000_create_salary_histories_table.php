<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Create salary_histories — tracks every base-salary change with
 *    an effective_from date so payroll can use the correct salary for
 *    any historical month.
 * 2. Seed existing employees' current salary as the initial history row
 *    effective from their join_date (or 2000-01-01 as fallback).
 * 3. Extend attendances.status enum to include 'leave' so the absent
 *    cron can record approved-leave days properly instead of marking
 *    them absent.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. salary_histories table ─────────────────────────────────
        Schema::create('salary_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_salary', 12, 2);
            $table->date('effective_from');   // Y-m-01 = first day of effective month
            $table->date('effective_to')->nullable(); // null = still current
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
        });

        // ── 2. Seed initial history for every existing employee ───────
        $employees = DB::table('employees')->whereNull('deleted_at')->get(['id', 'base_salary', 'join_date']);

        foreach ($employees as $emp) {
            $effectiveFrom = $emp->join_date
                ? \Carbon\Carbon::parse($emp->join_date)->startOfMonth()->toDateString()
                : '2000-01-01';

            DB::table('salary_histories')->insert([
                'employee_id'   => $emp->id,
                'base_salary'   => $emp->base_salary ?? 0,
                'effective_from' => $effectiveFrom,
                'effective_to'  => null,
                'changed_by'    => null,
                'note'          => 'Initial salary seeded from employee record.',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        // ── 3. Add 'leave' to attendances.status enum ─────────────────
        // MySQL enforces enum values at the column level; SQLite stores enums as
        // plain text so no DDL change is needed.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE attendances
                MODIFY COLUMN status
                ENUM('present','late','absent','underworked','holiday','weekly_off','leave')
                NOT NULL DEFAULT 'present'
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_histories');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE attendances
                MODIFY COLUMN status
                ENUM('present','late','absent','underworked','holiday','weekly_off')
                NOT NULL DEFAULT 'present'
            ");
        }
    }
};
