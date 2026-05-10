<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Period
            $table->unsignedSmallInteger('month');   // 1–12
            $table->unsignedSmallInteger('year');

            // ── Earnings (snapshot — never recalculated) ──────────
            $table->decimal('base_salary',     12, 2)->default(0);
            $table->decimal('bonus',           12, 2)->default(0);
            $table->decimal('incentive',       12, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->decimal('gross_salary',    12, 2)->default(0); // base+bonus+incentive+overtime

            // ── Deductions ────────────────────────────────────────
            $table->decimal('late_deduction',    12, 2)->default(0);
            $table->decimal('absent_deduction',  12, 2)->default(0);
            $table->decimal('leave_deduction',   12, 2)->default(0);
            $table->decimal('total_deductions',  12, 2)->default(0);

            // ── Net ───────────────────────────────────────────────
            $table->decimal('net_salary', 12, 2)->default(0); // gross - deductions

            // ── Attendance snapshot (for audit trail) ─────────────
            $table->unsignedInteger('working_days')->default(0);  // total working days in period
            $table->unsignedInteger('present_days')->default(0);
            $table->unsignedInteger('absent_days')->default(0);
            $table->unsignedInteger('late_days')->default(0);
            $table->unsignedInteger('unpaid_leave_days')->default(0);

            // ── Manual adjustments (admin notes) ─────────────────
            $table->text('note')->nullable();

            // ── Status lifecycle ──────────────────────────────────
            // draft → processed → paid (locked)
            $table->enum('status', ['draft', 'processed', 'paid'])->default('draft');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // One payroll per employee per month/year
            $table->unique(['employee_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
