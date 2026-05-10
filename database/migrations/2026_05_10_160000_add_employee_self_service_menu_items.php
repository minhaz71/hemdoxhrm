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

        $now = now();
        $items = [
            [
                'sort_order'     => 12,
                'type'           => 'header',
                'label'          => 'Self Service',
                'icon'           => null,
                'route'          => null,
                'route_pattern'  => null,
                'permissions'    => null,
                'roles'          => json_encode(['employee']),
                'is_active'      => true,
                'is_system'      => true,
            ],
            [
                'sort_order'     => 13,
                'type'           => 'link',
                'label'          => 'My Profile',
                'icon'           => 'bi-person-vcard',
                'route'          => 'employees.me',
                'route_pattern'  => 'employees.me',
                'permissions'    => null,
                'roles'          => json_encode(['employee']),
                'is_active'      => true,
                'is_system'      => true,
            ],
            [
                'sort_order'     => 14,
                'type'           => 'link',
                'label'          => 'My Attendance',
                'icon'           => 'bi-clock-history',
                'route'          => 'attendance.my',
                'route_pattern'  => 'attendance.*',
                'permissions'    => null,
                'roles'          => json_encode(['employee']),
                'is_active'      => true,
                'is_system'      => true,
            ],
            [
                'sort_order'     => 15,
                'type'           => 'link',
                'label'          => 'My Salary',
                'icon'           => 'bi-cash-stack',
                'route'          => 'payroll.my',
                'route_pattern'  => 'payroll.*',
                'permissions'    => null,
                'roles'          => json_encode(['employee']),
                'is_active'      => true,
                'is_system'      => true,
            ],
            [
                'sort_order'     => 16,
                'type'           => 'link',
                'label'          => 'My Payslips',
                'icon'           => 'bi-receipt',
                'route'          => 'payslips.my',
                'route_pattern'  => 'payslips.*',
                'permissions'    => null,
                'roles'          => json_encode(['employee']),
                'is_active'      => true,
                'is_system'      => true,
            ],
        ];

        foreach ($items as $item) {
            DB::table('menus')->updateOrInsert(
                ['route' => $item['route'], 'label' => $item['label']],
                $item + ['created_at' => $now, 'updated_at' => $now],
            );
        }

        Cache::forget('menus_all');
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')
            ->whereIn('route', ['employees.me', 'attendance.my', 'payroll.my', 'payslips.my'])
            ->orWhere(function ($query) {
                $query->whereNull('route')->where('label', 'Self Service');
            })
            ->delete();

        Cache::forget('menus_all');
    }
};
