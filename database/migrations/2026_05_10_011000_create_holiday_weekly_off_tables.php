<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('holiday_email_logs');
        Schema::dropIfExists('employee_weekly_offs');
        Schema::dropIfExists('holiday_employees');
        Schema::dropIfExists('holidays');

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('reason')->nullable();
            $table->unsignedSmallInteger('holiday_year')->index();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->enum('type', ['global', 'branch', 'department', 'employee_specific'])->default('global')->index();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->unsignedInteger('notify_before_days')->default(1);
            $table->timestamp('email_sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['holiday_year', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index(['branch_id', 'status']);
            $table->index(['department_id', 'status']);
        });

        Schema::create('holiday_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['holiday_id', 'employee_id']);
            $table->index('employee_id');
        });

        Schema::create('employee_weekly_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('day_of_week', ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
            $table->date('effective_from')->index();
            $table->date('effective_to')->nullable()->index();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();

            $table->index(['employee_id', 'day_of_week', 'status'], 'ewo_employee_day_status_idx');
            $table->index(['employee_id', 'effective_from', 'effective_to'], 'ewo_employee_range_idx');
        });

        Schema::create('holiday_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('subject');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['holiday_id', 'status']);
            $table->index('employee_id');
        });

        Schema::table('pending_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('pending_registrations', 'weekly_off_days')) {
                $table->json('weekly_off_days')->nullable()->after('shift_id');
            }
        });

        if (Schema::hasTable('menus')) {
            DB::table('menus')->updateOrInsert(
                ['route' => 'holidays.index'],
                [
                    'label' => 'Holidays',
                    'icon' => 'bi-calendar-event',
                    'route_pattern' => 'holidays.*',
                    'permissions' => null,
                    'roles' => json_encode(['admin', 'hr']),
                    'sort_order' => 34,
                    'is_active' => true,
                    'is_system' => true,
                    'type' => 'link',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            Cache::forget('menus_all');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pending_registrations') && Schema::hasColumn('pending_registrations', 'weekly_off_days')) {
            Schema::table('pending_registrations', function (Blueprint $table) {
                $table->dropColumn('weekly_off_days');
            });
        }

        Schema::dropIfExists('holiday_email_logs');
        Schema::dropIfExists('employee_weekly_offs');
        Schema::dropIfExists('holiday_employees');
        Schema::dropIfExists('holidays');

        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route', 'holidays.index')->delete();
            Cache::forget('menus_all');
        }
    }
};
