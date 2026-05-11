<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('menus')->insertOrIgnore([
            'label'         => 'Salary Increment Emails',
            'icon'          => 'bi-envelope-paper',
            'route'         => 'increment-emails.index',
            'route_pattern' => 'increment-emails.*',
            'permissions'   => json_encode([]),
            'roles'         => json_encode(['admin', 'hr']),
            'sort_order'    => 55,
            'is_active'     => 1,
            'is_system'     => 1,
            'type'          => 'link',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menus')->where('route', 'increment-emails.index')->delete();
    }
};
