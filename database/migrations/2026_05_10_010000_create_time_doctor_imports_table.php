<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_doctor_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('file_hash', 64)->index();
            $table->json('headers')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('created_records')->default(0);
            $table->unsignedInteger('updated_records')->default(0);
            $table->unsignedInteger('attendance_synced')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('imported_at');
            $table->timestamps();
        });

        Schema::create('time_doctor_daily_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_doctor_import_id')->nullable()->constrained('time_doctor_imports')->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->index();
            $table->string('time_doctor_employee_id')->nullable();
            $table->string('user_groups')->nullable();
            $table->date('work_date');
            $table->unsignedInteger('time_tracked_minutes')->default(0);
            $table->unsignedInteger('idle_minutes')->default(0);
            $table->decimal('idle_minutes_percent', 6, 2)->default(0);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('active_minutes')->default(0);
            $table->decimal('activity_percent', 6, 2)->default(0);
            $table->string('attendance_status', 20)->default('absent');
            $table->json('raw_row')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date'], 'td_daily_employee_date_unique');
            $table->index(['work_date', 'attendance_status']);
            $table->index('time_doctor_import_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_doctor_daily_records');
        Schema::dropIfExists('time_doctor_imports');
    }
};
