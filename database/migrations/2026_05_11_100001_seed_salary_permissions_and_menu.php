<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Add salary-history permissions (employees.salary.view, employees.salary.manage)
 * 2. Grant them to hr role
 * 3. Add "Salary History" menu item under employee management
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ── 1. Permissions ─────────────────────────────────────────
        DB::table('permissions')->upsert([
            [
                'name'        => 'employees.salary.view',
                'label'       => 'View Salary History',
                'module'      => 'employee',
                'is_system'   => true,
                'description' => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'employees.salary.manage',
                'label'       => 'Manage Salary Changes',
                'module'      => 'employee',
                'is_system'   => true,
                'description' => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ], ['name'], ['label', 'module', 'is_system', 'updated_at']);

        // ── 2. Grant to HR ─────────────────────────────────────────
        $hrRoleId = DB::table('roles')->where('name', 'hr')->value('id');
        if ($hrRoleId) {
            $permIds = DB::table('permissions')
                ->whereIn('name', ['employees.salary.view', 'employees.salary.manage'])
                ->pluck('id');

            $rows = $permIds->map(fn ($pid) => ['role_id' => $hrRoleId, 'permission_id' => $pid])->all();
            if ($rows) {
                DB::table('role_permission')->insertOrIgnore($rows);
            }
        }

        // ── 3. Menu item ───────────────────────────────────────────
        if (! Schema::hasTable('menus')) return;
        if (DB::table('menus')->where('route', 'salary-history.index')->exists()) return;

        // Find sort_order of the employees menu group to insert after it
        $maxOrder = DB::table('menus')->max('sort_order') ?? 100;

        DB::table('menus')->insert([
            'label'       => 'Salary History',
            'route'       => 'salary-history.index',
            'route_pattern' => 'salary-history.*',
            'icon'        => 'bi-cash-stack',
            'sort_order'  => $maxOrder + 10,
            'roles'       => json_encode(['admin', 'hr']),
            'permissions' => json_encode(['employees.salary.view']),
            'is_active'   => true,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('name', ['employees.salary.view', 'employees.salary.manage'])
            ->delete();

        DB::table('menus')->whereIn('route', ['employees.salary-history.index', 'salary-history.index'])->delete();
    }
};
