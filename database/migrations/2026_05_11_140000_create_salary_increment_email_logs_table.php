<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_increment_email_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->cascadeOnDelete();

            $table->foreignId('salary_history_id')
                  ->constrained('salary_histories')
                  ->cascadeOnDelete();

            $table->string('email');
            $table->string('subject');
            $table->longText('body');

            $table->enum('status', ['pending', 'sent', 'failed'])
                  ->default('pending');

            $table->foreignId('sent_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['employee_id'],       'siel_employee');
            $table->index(['salary_history_id'], 'siel_salary_history');
            $table->index(['status'],            'siel_status');
            $table->index(['sent_by'],           'siel_sent_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_increment_email_logs');
    }
};
