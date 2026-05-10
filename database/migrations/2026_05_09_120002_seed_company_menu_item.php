<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only insert if menus table exists and company item not already there
        if (! \Illuminate\Support\Facades\Schema::hasTable('menus')) {
            return;
        }

        if (DB::table('menus')->where('route', 'company.index')->exists()) {
            return;
        }

        $now = now();

        // Insert Company Profile link into the System section (sort_order 66)
        DB::table('menus')->insert([
            'label'         => 'Company Profile',
            'icon'          => 'bi-building',
            'route'         => 'company.index',
            'route_pattern' => 'company.*',
            'permissions'   => json_encode(['settings.edit']),
            'roles'         => null,
            'sort_order'    => 66,
            'is_active'     => true,
            'is_system'     => true,
            'type'          => 'link',
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('menus')->where('route', 'company.index')->delete();
    }
};
