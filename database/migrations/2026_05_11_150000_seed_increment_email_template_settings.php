<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $defaults = [
        [
            'key'         => 'increment_email_subject',
            'value'       => 'Salary Increment Notification',
            'label'       => 'Default Email Subject',
            'description' => 'Subject line for salary increment notification emails. Supports {employee_name}, {effective_date}, {company_name}.',
            'type'        => 'string',
            'group'       => 'increment_email_template',
            'sort_order'  => 1,
        ],
        [
            'key'         => 'increment_email_intro',
            'value'       => 'We are pleased to inform you that your salary has been revised, effective {effective_date}. Please find the details of your revised compensation below.',
            'label'       => 'Email Opening Message',
            'description' => 'Opening paragraph shown before the salary table. Supports {employee_name}, {effective_date}, {previous_salary}, {new_salary}, {increment_amount}, {increment_percentage}, {company_name}.',
            'type'        => 'text',
            'group'       => 'increment_email_template',
            'sort_order'  => 2,
        ],
        [
            'key'         => 'increment_email_closing',
            'value'       => 'Thank you for your continued dedication and contribution to our organization. If you have any questions, please feel free to reach out to the HR department.',
            'label'       => 'Email Closing Message',
            'description' => 'Paragraph shown after the salary table.',
            'type'        => 'text',
            'group'       => 'increment_email_template',
            'sort_order'  => 3,
        ],
        [
            'key'         => 'increment_email_signature_name',
            'value'       => 'HR Department',
            'label'       => 'Signature — Name',
            'description' => 'Sender name shown in email signature.',
            'type'        => 'string',
            'group'       => 'increment_email_template',
            'sort_order'  => 4,
        ],
        [
            'key'         => 'increment_email_signature_title',
            'value'       => 'Human Resources',
            'label'       => 'Signature — Title / Designation',
            'description' => 'Sender title shown in email signature.',
            'type'        => 'string',
            'group'       => 'increment_email_template',
            'sort_order'  => 5,
        ],
        [
            'key'         => 'increment_email_signature_contact',
            'value'       => '',
            'label'       => 'Signature — Contact (optional)',
            'description' => 'Email or phone shown in signature.',
            'type'        => 'string',
            'group'       => 'increment_email_template',
            'sort_order'  => 6,
        ],
    ];

    public function up(): void
    {
        $now = now();
        foreach ($this->defaults as $row) {
            DB::table('system_settings')->insertOrIgnore(array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        $keys = array_column($this->defaults, 'key');
        DB::table('system_settings')->whereIn('key', $keys)->delete();
    }
};
