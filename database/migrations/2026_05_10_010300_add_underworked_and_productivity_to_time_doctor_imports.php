<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendances') && DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE attendances MODIFY status ENUM('present','late','absent','underworked') NOT NULL DEFAULT 'present'");
        }

        Schema::table('time_doctor_daily_records', function (Blueprint $table) {
            if (! Schema::hasColumn('time_doctor_daily_records', 'productive_minutes')) {
                $table->unsignedInteger('productive_minutes')->default(0)->after('active_minutes');
            }

            if (! Schema::hasColumn('time_doctor_daily_records', 'productivity_percentage')) {
                $table->decimal('productivity_percentage', 6, 2)->default(0)->after('activity_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_doctor_daily_records', function (Blueprint $table) {
            if (Schema::hasColumn('time_doctor_daily_records', 'productivity_percentage')) {
                $table->dropColumn('productivity_percentage');
            }

            if (Schema::hasColumn('time_doctor_daily_records', 'productive_minutes')) {
                $table->dropColumn('productive_minutes');
            }
        });

        if (Schema::hasTable('attendances') && DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE attendances MODIFY status ENUM('present','late','absent') NOT NULL DEFAULT 'present'");
        }
    }
};
