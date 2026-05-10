<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed admin-configurable settings for the Holiday & Weekly Off module.
 *
 * Keys added
 * ──────────────────────────────────────────────────────────────────
 * holiday_email_enabled           boolean  Master switch for holiday email notifications
 * holiday_notify_before_days      integer  Default lead-time (days) before holidays get emailed
 * hr_manage_holidays              boolean  Allow HR (non-admin) to create/edit/toggle holidays
 * hr_manage_weekly_off            boolean  Allow HR to manage employee weekly-off assignments
 * registration_weekly_off_enabled boolean  Show weekly-off selector on the employee registration form
 *
 * Note: holiday_attendance_record already exists (seeded in migration 030000).
 */
return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'key'         => 'holiday_email_enabled',
                'value'       => 'true',
                'group'       => 'holiday',
                'type'        => 'boolean',
                'label'       => 'Holiday Email Notifications',
                'description' => 'When enabled, the system automatically emails employees before upcoming holidays.',
                'sort_order'  => 10,
            ],
            [
                'key'         => 'holiday_notify_before_days',
                'value'       => '3',
                'group'       => 'holiday',
                'type'        => 'integer',
                'label'       => 'Default Notify Before (days)',
                'description' => 'Days before a holiday starts when the reminder email is sent. Overridden per-holiday if set.',
                'sort_order'  => 20,
            ],
            [
                'key'         => 'hr_manage_holidays',
                'value'       => 'true',
                'group'       => 'holiday',
                'type'        => 'boolean',
                'label'       => 'Allow HR to Manage Holidays',
                'description' => 'When disabled, only Admin accounts can create, edit, or toggle holidays.',
                'sort_order'  => 30,
            ],
            [
                'key'         => 'hr_manage_weekly_off',
                'value'       => 'true',
                'group'       => 'weekly_off',
                'type'        => 'boolean',
                'label'       => 'Allow HR to Manage Weekly Offs',
                'description' => 'When disabled, only Admin accounts can assign or modify employee weekly-off schedules.',
                'sort_order'  => 10,
            ],
            [
                'key'         => 'registration_weekly_off_enabled',
                'value'       => 'true',
                'group'       => 'weekly_off',
                'type'        => 'boolean',
                'label'       => 'Employee Weekly Off During Registration',
                'description' => 'Allow new employees to choose their preferred weekly off day(s) on the invitation registration form.',
                'sort_order'  => 20,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'holiday_email_enabled',
            'holiday_notify_before_days',
            'hr_manage_holidays',
            'hr_manage_weekly_off',
            'registration_weekly_off_enabled',
        ])->delete();
    }
};
