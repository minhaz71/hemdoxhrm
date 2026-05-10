<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');

            $table->string('file_path');           // storage path, e.g. payslips/2025/05/EMP-0001.pdf
            $table->string('file_name');           // human label, e.g. EMP-0001_May_2025.pdf
            $table->unsignedInteger('file_size')->nullable();  // bytes

            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');

            $table->timestamps();

            $table->unique(['payroll_id']);                     // one payslip per payroll
            $table->unique(['employee_id', 'month', 'year']);  // one per employee per period
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
