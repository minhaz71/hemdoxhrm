<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'team_leader_id')) {
                $table->foreignId('team_leader_id')
                    ->nullable()
                    ->after('shift_id')
                    ->constrained('employees')
                    ->nullOnDelete();
            }
        });

        $now = now();
        $settings = [
            [
                'key' => 'important_update_emails',
                'value' => '',
                'label' => 'Important Update Emails',
                'description' => 'Extra recipients for important HRMS updates such as new leave applications.',
                'group' => 'notifications',
                'type' => 'textarea',
                'sort_order' => 10,
            ],
            [
                'key' => 'leave_approval_cc_emails',
                'value' => '',
                'label' => 'Leave Approval CC Emails',
                'description' => 'Extra recipients notified when a leave request is approved.',
                'group' => 'notifications',
                'type' => 'textarea',
                'sort_order' => 20,
            ],
            [
                'key' => 'payslip_cc_emails',
                'value' => '',
                'label' => 'Payslip CC Emails',
                'description' => 'Extra recipients notified when payslips or salary emails are generated.',
                'group' => 'notifications',
                'type' => 'textarea',
                'sort_order' => 30,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting + ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', ['important_update_emails', 'leave_approval_cc_emails', 'payslip_cc_emails'])
            ->delete();

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'team_leader_id')) {
                $table->dropConstrainedForeignId('team_leader_id');
            }
        });
    }
};
