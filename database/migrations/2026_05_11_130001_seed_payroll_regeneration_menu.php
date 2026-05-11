<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add "Payroll Regeneration" to the sidebar — admin-only tool
        DB::table('menus')->insertOrIgnore([
            'label'         => 'Payroll Regeneration',
            'icon'          => 'bi-arrow-repeat',
            'route'         => 'payroll-regeneration.index',
            'route_pattern' => 'payroll-regeneration.*',
            'permissions'   => json_encode([]),            // controlled by policy (admin/hr see it)
            'roles'         => json_encode(['admin', 'hr']),
            'sort_order'    => 42,
            'is_active'     => 1,
            'is_system'     => 1,
            'type'          => 'link',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menus')->where('route', 'payroll-regeneration.index')->delete();
    }
};
