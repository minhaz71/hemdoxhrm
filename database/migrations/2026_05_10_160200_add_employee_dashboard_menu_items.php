<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        $employee = json_encode(['employee']);
        $adminStaff = json_encode(['admin', 'hr', 'manager']);
        $now = now();

        DB::table('menus')
            ->where('route', 'dashboard')
            ->update(['roles' => $adminStaff, 'updated_at' => $now]);

        $items = [
            [
                'sort_order' => 13,
                'type' => 'link',
                'label' => 'CRM Dashboard',
                'icon' => 'bi-grid-1x2',
                'route' => 'dashboard.crm',
                'route_pattern' => 'dashboard.crm',
                'permissions' => null,
                'roles' => $employee,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'sort_order' => 14,
                'type' => 'link',
                'label' => 'Time Doctor Dashboard',
                'icon' => 'bi-speedometer2',
                'route' => 'dashboard.time-doctor',
                'route_pattern' => 'dashboard.time-doctor',
                'permissions' => null,
                'roles' => $employee,
                'is_active' => true,
                'is_system' => true,
            ],
        ];

        foreach ($items as $item) {
            DB::table('menus')->updateOrInsert(
                ['route' => $item['route']],
                $item + ['created_at' => $now, 'updated_at' => $now],
            );
        }

        DB::table('menus')->where('route', 'employees.me')->update(['sort_order' => 15, 'updated_at' => $now]);
        DB::table('menus')->where('route', 'attendance.my')->update(['sort_order' => 16, 'updated_at' => $now]);
        DB::table('menus')->where('route', 'payroll.my')->update(['sort_order' => 17, 'updated_at' => $now]);
        DB::table('menus')->where('route', 'payslips.my')->update(['sort_order' => 18, 'updated_at' => $now]);

        Cache::forget('menus_all');
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')->whereIn('route', ['dashboard.crm', 'dashboard.time-doctor'])->delete();
        DB::table('menus')->where('route', 'dashboard')->update(['roles' => null, 'updated_at' => now()]);

        Cache::forget('menus_all');
    }
};
