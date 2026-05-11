<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }

        $now = now();

        // "Salary Increment" menu item
        if (!DB::table('menus')->where('route', 'salary-increments.index')->exists()) {
            DB::table('menus')->insert([
                'label'         => 'Salary Increment',
                'icon'          => 'bi-graph-up-arrow',
                'route'         => 'salary-increments.index',
                'route_pattern' => 'salary-increments.*',
                'permissions'   => json_encode(['employees.salary.manage']),
                'roles'         => null,
                'sort_order'    => 43,
                'is_active'     => true,
                'is_system'     => true,
                'type'          => 'link',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        // "Pending Approvals" menu item (admin only)
        if (!DB::table('menus')->where('route', 'salary-increments.approval')->exists()) {
            DB::table('menus')->insert([
                'label'         => 'Pending Approvals',
                'icon'          => 'bi-hourglass-split',
                'route'         => 'salary-increments.approval',
                'route_pattern' => null,
                'permissions'   => null,
                'roles'         => json_encode(['admin']),
                'sort_order'    => 44,
                'is_active'     => true,
                'is_system'     => true,
                'type'          => 'link',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('menus')->whereIn('route', [
            'salary-increments.index',
            'salary-increments.approval',
        ])->delete();
    }
};
