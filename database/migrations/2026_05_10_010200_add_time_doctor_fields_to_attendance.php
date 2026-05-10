<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'source')) {
                $table->string('source', 30)->default('manual')->index();
            }

            if (! Schema::hasColumn('attendances', 'time_doctor_worked_minutes')) {
                $table->unsignedInteger('time_doctor_worked_minutes')->nullable();
            }

            if (! Schema::hasColumn('attendances', 'time_doctor_idle_minutes')) {
                $table->unsignedInteger('time_doctor_idle_minutes')->nullable();
            }
        });

        Schema::table('time_doctor_daily_records', function (Blueprint $table) {
            if (! Schema::hasColumn('time_doctor_daily_records', 'leave_id')) {
                $table->foreignId('leave_id')->nullable()->constrained('leaves')->nullOnDelete();
            }

            if (! Schema::hasColumn('time_doctor_daily_records', 'worked_on_leave')) {
                $table->boolean('worked_on_leave')->default(false)->index();
            }

            if (! Schema::hasColumn('time_doctor_daily_records', 'penalty_warning_sent_at')) {
                $table->timestamp('penalty_warning_sent_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_doctor_daily_records', function (Blueprint $table) {
            if (Schema::hasColumn('time_doctor_daily_records', 'leave_id')) {
                $table->dropConstrainedForeignId('leave_id');
            }

            if (Schema::hasColumn('time_doctor_daily_records', 'worked_on_leave')) {
                $table->dropColumn('worked_on_leave');
            }

            if (Schema::hasColumn('time_doctor_daily_records', 'penalty_warning_sent_at')) {
                $table->dropColumn('penalty_warning_sent_at');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'source')) {
                $table->dropColumn('source');
            }

            if (Schema::hasColumn('attendances', 'time_doctor_worked_minutes')) {
                $table->dropColumn('time_doctor_worked_minutes');
            }

            if (Schema::hasColumn('attendances', 'time_doctor_idle_minutes')) {
                $table->dropColumn('time_doctor_idle_minutes');
            }
        });
    }
};
