<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add paid_leave_days to payrolls so the payroll snapshot distinguishes
 * between paid leave (no deduction) and unpaid leave (full-day deduction).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->unsignedInteger('paid_leave_days')->default(0)->after('unpaid_leave_days');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('paid_leave_days');
        });
    }
};
