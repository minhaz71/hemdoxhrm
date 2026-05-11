<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_regeneration_logs', function (Blueprint $table) {
            $table->id();

            // Payroll being regenerated (nullable — old paid payroll may be archived)
            $table->foreignId('payroll_id')
                  ->nullable()
                  ->constrained('payrolls')
                  ->nullOnDelete();

            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->cascadeOnDelete();

            $table->unsignedSmallInteger('month');
            $table->unsignedSmallInteger('year');

            // Whether the payroll was locked/paid at the time of regeneration
            $table->boolean('was_locked')->default(false);

            // Full snapshot of the old payroll data before regeneration
            $table->json('old_snapshot')->nullable();

            // Full snapshot of the new payroll data after regeneration
            $table->json('new_snapshot')->nullable();

            // Admin-provided reason (required for locked payrolls)
            $table->text('reason')->nullable();

            $table->foreignId('regenerated_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();

            $table->index(['employee_id', 'year', 'month'], 'prlog_emp_period');
            $table->index(['payroll_id'],                   'prlog_payroll');
            $table->index(['regenerated_by'],               'prlog_regen_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_regeneration_logs');
    }
};
