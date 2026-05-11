<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'alternate_email')) {
                $table->string('alternate_email')->nullable()->after('email');
            }
        });

        Schema::table('payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('payrolls', 'management_working_days')) {
                $table->unsignedInteger('management_working_days')->nullable()->after('working_days');
            }
            if (! Schema::hasColumn('payrolls', 'calendar_working_days')) {
                $table->unsignedInteger('calendar_working_days')->nullable()->after('management_working_days');
            }
            if (! Schema::hasColumn('payrolls', 'late_penalty_enabled')) {
                $table->boolean('late_penalty_enabled')->default(true)->after('late_days');
            }
            if (! Schema::hasColumn('payrolls', 'late_penalty_amount')) {
                $table->decimal('late_penalty_amount', 10, 2)->default(10)->after('late_penalty_enabled');
            }
            if (! Schema::hasColumn('payrolls', 'leave_penalty_enabled')) {
                $table->boolean('leave_penalty_enabled')->default(true)->after('unpaid_leave_days');
            }
            if (! Schema::hasColumn('payrolls', 'leave_penalty_rate')) {
                $table->decimal('leave_penalty_rate', 8, 4)->default(1)->after('leave_penalty_enabled');
            }
            if (! Schema::hasColumn('payrolls', 'overtime_enabled')) {
                $table->boolean('overtime_enabled')->default(false)->after('overtime_amount');
            }
        });

        $now = now();
        DB::table('system_settings')->upsert([
            [
                'key' => 'late_penalty_enabled',
                'value' => 'true',
                'group' => 'payroll',
                'type' => 'boolean',
                'label' => 'Late Penalty Enabled',
                'description' => 'When disabled, late attendance does not deduct salary.',
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'late_penalty_amount',
                'value' => '10',
                'group' => 'payroll',
                'type' => 'text',
                'label' => 'Late Penalty Amount',
                'description' => 'Flat deduction per late day.',
                'sort_order' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'leave_penalty_enabled',
                'value' => 'true',
                'group' => 'payroll',
                'type' => 'boolean',
                'label' => 'Leave Penalty Enabled',
                'description' => 'When disabled, unpaid leave does not deduct salary automatically.',
                'sort_order' => 8,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'leave_penalty_rate',
                'value' => '1',
                'group' => 'payroll',
                'type' => 'text',
                'label' => 'Leave Penalty Rate',
                'description' => 'Daily salary multiplier for unpaid leave deduction.',
                'sort_order' => 9,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'overtime_pay_enabled',
                'value' => 'false',
                'group' => 'payroll',
                'type' => 'boolean',
                'label' => 'Overtime Pay Enabled',
                'description' => 'When disabled, overtime is hidden and never added to payroll.',
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['key'], ['value', 'group', 'type', 'label', 'description', 'sort_order', 'updated_at']);
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            foreach ([
                'management_working_days',
                'calendar_working_days',
                'late_penalty_enabled',
                'late_penalty_amount',
                'leave_penalty_enabled',
                'leave_penalty_rate',
                'overtime_enabled',
            ] as $column) {
                if (Schema::hasColumn('payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'alternate_email')) {
                $table->dropColumn('alternate_email');
            }
        });

        DB::table('system_settings')
            ->whereIn('key', [
                'late_penalty_enabled',
                'late_penalty_amount',
                'leave_penalty_enabled',
                'leave_penalty_rate',
                'overtime_pay_enabled',
            ])
            ->delete();
    }
};
