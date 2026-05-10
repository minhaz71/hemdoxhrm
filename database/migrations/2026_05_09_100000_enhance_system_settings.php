<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Add group column + type column (guard for partial runs) ──
        Schema::table('system_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('system_settings', 'group')) {
                $table->string('group', 50)->default('general')->after('key')->index();
            }
            if (! Schema::hasColumn('system_settings', 'type')) {
                $table->string('type', 20)->default('text')->after('group');
            }
            if (! Schema::hasColumn('system_settings', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('type');
            }
        });

        $now = now();

        // ── Assign groups/types to existing records ───────────────────
        DB::table('system_settings')
            ->whereIn('key', ['currency_code','currency_symbol','currency_position','decimal_separator','thousand_separator'])
            ->update(['group' => 'currency', 'type' => 'text']);

        DB::table('system_settings')
            ->where('key', 'designation_hr_access')
            ->update(['group' => 'access', 'type' => 'boolean']);

        // ── All new settings ──────────────────────────────────────────
        $settings = [

            // ── Company ───────────────────────────────────────────────
            ['key' => 'company_name',     'group' => 'company', 'type' => 'text',     'sort_order' => 1,  'label' => 'Company Name',         'description' => 'Displayed in the sidebar and email headers.',  'value' => 'Hemdox HRMS'],
            ['key' => 'company_tagline',  'group' => 'company', 'type' => 'text',     'sort_order' => 2,  'label' => 'Tagline / Sub-brand',  'description' => 'Short line shown beneath the company name.',   'value' => 'by Hemdox Digital'],
            ['key' => 'company_address',  'group' => 'company', 'type' => 'textarea', 'sort_order' => 3,  'label' => 'Company Address',      'description' => 'Printed on payslips and reports.',             'value' => ''],
            ['key' => 'company_phone',    'group' => 'company', 'type' => 'text',     'sort_order' => 4,  'label' => 'Phone Number',         'description' => null,                                           'value' => ''],
            ['key' => 'company_email',    'group' => 'company', 'type' => 'text',     'sort_order' => 5,  'label' => 'Company Email',        'description' => null,                                           'value' => ''],
            ['key' => 'company_website',  'group' => 'company', 'type' => 'text',     'sort_order' => 6,  'label' => 'Website URL',          'description' => null,                                           'value' => ''],
            ['key' => 'company_logo',     'group' => 'company', 'type' => 'file',     'sort_order' => 7,  'label' => 'Company Logo',         'description' => 'PNG or JPG, displayed in sidebar & payslips.', 'value' => ''],

            // ── Localization ──────────────────────────────────────────
            ['key' => 'timezone',         'group' => 'localization', 'type' => 'select', 'sort_order' => 1, 'label' => 'Timezone',       'description' => 'All date/time calculations use this timezone.',           'value' => 'UTC'],
            ['key' => 'date_format',      'group' => 'localization', 'type' => 'select', 'sort_order' => 2, 'label' => 'Date Format',    'description' => 'Applied to all date fields across the application.',     'value' => 'Y-m-d'],
            ['key' => 'time_format',      'group' => 'localization', 'type' => 'select', 'sort_order' => 3, 'label' => 'Time Format',    'description' => '24-hour or 12-hour clock.',                              'value' => 'H:i'],
            ['key' => 'week_starts_on',   'group' => 'localization', 'type' => 'select', 'sort_order' => 4, 'label' => 'Week Starts On', 'description' => 'First day of the week used in calendars.',               'value' => '1'],

            // ── Attendance ────────────────────────────────────────────
            ['key' => 'work_hours_per_day',        'group' => 'attendance', 'type' => 'integer', 'sort_order' => 1, 'label' => 'Work Hours Per Day',          'description' => 'Standard daily work hours.',                      'value' => '8'],
            ['key' => 'late_grace_minutes',         'group' => 'attendance', 'type' => 'integer', 'sort_order' => 2, 'label' => 'Late Arrival Grace (minutes)', 'description' => 'Minutes after shift start before marking late.',  'value' => '15'],
            ['key' => 'overtime_threshold_hours',   'group' => 'attendance', 'type' => 'text',    'sort_order' => 3, 'label' => 'Overtime After (hours)',       'description' => 'Hours worked beyond this are counted as overtime.','value' => '8'],
            ['key' => 'work_days',                  'group' => 'attendance', 'type' => 'json',    'sort_order' => 4, 'label' => 'Work Days',                   'description' => 'Days of the week considered working days.',        'value' => '["Mon","Tue","Wed","Thu","Fri"]'],

            // ── Leave ──────────────────────────────────────────────────
            ['key' => 'annual_leave_days',      'group' => 'leave', 'type' => 'integer', 'sort_order' => 1, 'label' => 'Annual Leave Days',           'description' => 'Default annual paid leave entitlement per year.',            'value' => '20'],
            ['key' => 'sick_leave_days',         'group' => 'leave', 'type' => 'integer', 'sort_order' => 2, 'label' => 'Sick Leave Days',             'description' => 'Default sick leave entitlement per year.',                  'value' => '10'],
            ['key' => 'casual_leave_days',       'group' => 'leave', 'type' => 'integer', 'sort_order' => 3, 'label' => 'Casual Leave Days',           'description' => 'Default casual leave entitlement per year.',                'value' => '10'],
            ['key' => 'carry_forward_days',      'group' => 'leave', 'type' => 'integer', 'sort_order' => 4, 'label' => 'Carry-Forward Days (max)',     'description' => 'Maximum unused leave days carried to the next year.',      'value' => '5'],
            ['key' => 'max_leave_per_request',   'group' => 'leave', 'type' => 'integer', 'sort_order' => 5, 'label' => 'Max Days Per Request',         'description' => 'Maximum number of days in a single leave application.',    'value' => '30'],

            // ── Payroll ───────────────────────────────────────────────
            ['key' => 'pay_cycle',             'group' => 'payroll', 'type' => 'select',   'sort_order' => 1, 'label' => 'Pay Cycle',               'description' => 'How often payroll is processed.',                        'value' => 'monthly'],
            ['key' => 'tax_percentage',        'group' => 'payroll', 'type' => 'text',     'sort_order' => 2, 'label' => 'Default Tax Rate (%)',     'description' => 'Default income tax percentage applied to gross salary.', 'value' => '0'],
            ['key' => 'overtime_multiplier',   'group' => 'payroll', 'type' => 'text',     'sort_order' => 3, 'label' => 'Overtime Pay Multiplier',  'description' => 'Multiplier applied to hourly rate for overtime hours.',  'value' => '1.5'],
            ['key' => 'payslip_footer_note',   'group' => 'payroll', 'type' => 'textarea', 'sort_order' => 4, 'label' => 'Payslip Footer Note',      'description' => 'Text printed at the bottom of every payslip.',          'value' => 'This is a system-generated payslip and does not require a signature.'],

            // ── SMTP / Email ──────────────────────────────────────────
            ['key' => 'smtp_host',          'group' => 'smtp', 'type' => 'text',     'sort_order' => 1, 'label' => 'SMTP Host',             'description' => null, 'value' => ''],
            ['key' => 'smtp_port',          'group' => 'smtp', 'type' => 'integer',  'sort_order' => 2, 'label' => 'SMTP Port',             'description' => null, 'value' => '587'],
            ['key' => 'smtp_username',      'group' => 'smtp', 'type' => 'text',     'sort_order' => 3, 'label' => 'SMTP Username',         'description' => null, 'value' => ''],
            ['key' => 'smtp_password',      'group' => 'smtp', 'type' => 'password', 'sort_order' => 4, 'label' => 'SMTP Password',         'description' => null, 'value' => ''],
            ['key' => 'smtp_encryption',    'group' => 'smtp', 'type' => 'select',   'sort_order' => 5, 'label' => 'Encryption',            'description' => null, 'value' => 'tls'],
            ['key' => 'mail_from_name',     'group' => 'smtp', 'type' => 'text',     'sort_order' => 6, 'label' => 'From Name',             'description' => 'Sender name shown in outgoing emails.', 'value' => 'Hemdox HRMS'],
            ['key' => 'mail_from_address',  'group' => 'smtp', 'type' => 'text',     'sort_order' => 7, 'label' => 'From Email Address',    'description' => 'Sender address for all system emails.', 'value' => ''],

            // ── Theme ─────────────────────────────────────────────────
            ['key' => 'theme_primary_color',  'group' => 'theme', 'type' => 'color', 'sort_order' => 1, 'label' => 'Primary / Accent Color',  'description' => 'Used for buttons, links, and active sidebar items.', 'value' => '#4e9af1'],
            ['key' => 'theme_sidebar_color',  'group' => 'theme', 'type' => 'color', 'sort_order' => 2, 'label' => 'Sidebar Background Color','description' => 'Background color of the navigation sidebar.',       'value' => '#1a1f2e'],
            ['key' => 'theme_logo_text',      'group' => 'theme', 'type' => 'text',  'sort_order' => 3, 'label' => 'Logo Brand Text',         'description' => 'Bold text before the HRMS part of the sidebar logo.','value' => 'Hemdox'],
        ];

        foreach ($settings as &$s) {
            $s['created_at'] = $now;
            $s['updated_at'] = $now;
        }

        DB::table('system_settings')->upsert(
            $settings,
            ['key'],
            ['group', 'type', 'sort_order', 'label', 'description', 'value']
        );
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['group', 'type', 'sort_order']);
        });
    }
};
