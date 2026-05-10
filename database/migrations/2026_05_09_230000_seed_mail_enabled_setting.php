<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add mail_enabled setting (default false until SMTP is configured)
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'mail_enabled'],
            [
                'key'         => 'mail_enabled',
                'group'       => 'smtp',
                'type'        => 'boolean',
                'label'       => 'Enable Mail',
                'description' => 'Enable or disable all outgoing email from the system.',
                'value'       => 'false',
                'sort_order'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );

        // Ensure all smtp settings are in the smtp group with sort_order
        $smtpSettings = [
            ['key' => 'smtp_host',         'sort_order' => 1,  'label' => 'SMTP Host',         'type' => 'string'],
            ['key' => 'smtp_port',         'sort_order' => 2,  'label' => 'SMTP Port',         'type' => 'integer'],
            ['key' => 'smtp_encryption',   'sort_order' => 3,  'label' => 'Encryption',        'type' => 'string'],
            ['key' => 'smtp_username',     'sort_order' => 4,  'label' => 'SMTP Username',     'type' => 'string'],
            ['key' => 'smtp_password',     'sort_order' => 5,  'label' => 'SMTP Password',     'type' => 'encrypted'],
            ['key' => 'mail_from_name',    'sort_order' => 6,  'label' => 'Sender Name',       'type' => 'string'],
            ['key' => 'mail_from_address', 'sort_order' => 7,  'label' => 'Sender Email',      'type' => 'string'],
        ];

        foreach ($smtpSettings as $setting) {
            DB::table('system_settings')
                ->where('key', $setting['key'])
                ->update([
                    'group'      => 'smtp',
                    'type'       => $setting['type'],
                    'label'      => $setting['label'],
                    'sort_order' => $setting['sort_order'],
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'mail_enabled')->delete();
    }
};
