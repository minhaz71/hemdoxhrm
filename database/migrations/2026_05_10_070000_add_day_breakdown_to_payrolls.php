<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add holiday_days, weekly_off_days, and leave_days to payrolls so the
 * salary report can display a full attendance breakdown per employee.
 * These columns are informational — they carry no deduction logic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->unsignedInteger('holiday_days')    ->default(0)->after('working_days');
            $table->unsignedInteger('weekly_off_days') ->default(0)->after('holiday_days');
            $table->unsignedInteger('leave_days')      ->default(0)->after('weekly_off_days');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['holiday_days', 'weekly_off_days', 'leave_days']);
        });
    }
};
