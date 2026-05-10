<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // employees
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['status', 'deleted_at'], 'employees_status_deleted_at');
            $table->index('branch_id',      'employees_branch_id');
            $table->index('department_id',  'employees_department_id');
            $table->index('shift_id',       'employees_shift_id');
            $table->index('designation_id', 'employees_designation_id');
        });

        // attendances
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['employee_id', 'date'], 'attendances_employee_date');
            $table->index('status', 'attendances_status');
        });

        // leaves
        Schema::table('leaves', function (Blueprint $table) {
            $table->index(['employee_id', 'status'], 'leaves_employee_status');
            $table->index('start_date',    'leaves_start_date');
            $table->index('end_date',      'leaves_end_date');
            $table->index('leave_type_id', 'leaves_leave_type_id');
        });

        // payrolls
        Schema::table('payrolls', function (Blueprint $table) {
            $table->index(['year', 'month'], 'payrolls_year_month');
            $table->index('status',          'payrolls_status');
        });

        // salary_snapshots
        Schema::table('salary_snapshots', function (Blueprint $table) {
            $table->index(['year', 'month'], 'salary_snapshots_year_month');
            $table->index('is_locked',       'salary_snapshots_is_locked');
        });

        // system_settings
        Schema::table('system_settings', function (Blueprint $table) {
            $table->index('group', 'system_settings_group');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_status_deleted_at');
            $table->dropIndex('employees_branch_id');
            $table->dropIndex('employees_department_id');
            $table->dropIndex('employees_shift_id');
            $table->dropIndex('employees_designation_id');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_employee_date');
            $table->dropIndex('attendances_status');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex('leaves_employee_status');
            $table->dropIndex('leaves_start_date');
            $table->dropIndex('leaves_end_date');
            $table->dropIndex('leaves_leave_type_id');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex('payrolls_year_month');
            $table->dropIndex('payrolls_status');
        });

        Schema::table('salary_snapshots', function (Blueprint $table) {
            $table->dropIndex('salary_snapshots_year_month');
            $table->dropIndex('salary_snapshots_is_locked');
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropIndex('system_settings_group');
        });
    }
};
