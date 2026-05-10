<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('system_settings')->where('key', 'enable_branches')->exists()) {
            return;
        }

        DB::table('system_settings')->insert([
            'key'         => 'enable_branches',
            'group'       => 'company',
            'type'        => 'boolean',
            'sort_order'  => 99,
            'value'       => '0',
            'label'       => 'Enable Multiple Branches',
            'description' => 'Allow branch management and branch-wise employee filtering.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'enable_branches')->delete();
    }
};
