<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_snapshots', function (Blueprint $table) {
            $table->id();

            // Links
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();

            // Period this snapshot covers
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');

            // Salary structure captured at snapshot time
            // These are COPIED from employee at generation — never recalculated
            $table->decimal('base_salary',    12, 2);
            $table->decimal('bonus',          12, 2)->default(0);
            $table->decimal('incentive',      12, 2)->default(0);
            $table->decimal('overtime',       12, 2)->default(0);
            $table->decimal('gross_salary',   12, 2);

            // Deductions at snapshot time
            $table->decimal('late_deduction',   12, 2)->default(0);
            $table->decimal('absent_deduction', 12, 2)->default(0);
            $table->decimal('leave_deduction',  12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);

            // Final net
            $table->decimal('net_salary', 12, 2);

            // Employee metadata copied at snapshot time (for audit — survives employee edits)
            $table->string('employee_code');
            $table->string('employee_name');
            $table->string('department');
            $table->string('designation');

            // Lock flag — set to true when payroll is marked paid
            // Once locked=true, the record becomes fully immutable
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // One snapshot per employee per period
            $table->unique(['employee_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_snapshots');
    }
};
