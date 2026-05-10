<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Menus ─────────────────────────────────────────────────────
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100);
            $table->string('icon', 60)->nullable();           // bi-people, bi-gear …
            $table->string('route', 120)->nullable();         // Laravel route name
            $table->string('route_pattern', 120)->nullable(); // for routeIs() active-state
            $table->json('permissions')->nullable();           // ["employee.view"] canAny
            $table->json('roles')->nullable();                 // ["admin","hr"] canAny; null = any
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);     // seeded items, not deletable
            $table->string('type', 20)->default('link');      // link | header | divider
            $table->timestamps();
        });

        // ── User-level menu overrides ─────────────────────────────────
        Schema::create('user_menu_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->boolean('visible');                        // true=force-show, false=force-hide
            $table->timestamps();
            $table->unique(['user_id', 'menu_id']);
        });

        // ── Seed initial menu structure ────────────────────────────────
        $now  = now();
        $sys  = true;    // is_system

        $menus = [
            // ── Main ──────────────────────────────────────────────────
            ['sort_order' => 10,  'type' => 'header', 'label' => 'Main',             'icon' => null,                  'route' => null,                    'route_pattern' => null,            'permissions' => null,                                      'roles' => null,   'is_system' => $sys],
            ['sort_order' => 11,  'type' => 'link',   'label' => 'Dashboard',         'icon' => 'bi-grid-1x2',        'route' => 'dashboard',              'route_pattern' => 'dashboard',     'permissions' => null,                                      'roles' => null,   'is_system' => $sys],

            // ── People ────────────────────────────────────────────────
            ['sort_order' => 20,  'type' => 'header', 'label' => 'People',            'icon' => null,                  'route' => null,                    'route_pattern' => null,            'permissions' => json_encode(['employee.view','designations.manage']),  'roles' => null,   'is_system' => $sys],
            ['sort_order' => 21,  'type' => 'link',   'label' => 'Employees',         'icon' => 'bi-people',          'route' => 'employees.index',        'route_pattern' => 'employees.*',   'permissions' => json_encode(['employee.view']),             'roles' => null,   'is_system' => $sys],
            ['sort_order' => 22,  'type' => 'link',   'label' => 'Designations',      'icon' => 'bi-briefcase',       'route' => 'designations.index',     'route_pattern' => 'designations.*','permissions' => json_encode(['designations.manage']),       'roles' => null,   'is_system' => $sys],

            // ── Time & Leave ──────────────────────────────────────────
            ['sort_order' => 30,  'type' => 'header', 'label' => 'Time & Leave',      'icon' => null,                  'route' => null,                    'route_pattern' => null,            'permissions' => json_encode(['attendance.view','leave.view','leave.apply']), 'roles' => null, 'is_system' => $sys],
            ['sort_order' => 31,  'type' => 'link',   'label' => 'Attendance',        'icon' => 'bi-clock-history',   'route' => 'attendance.index',       'route_pattern' => 'attendance.*',  'permissions' => json_encode(['attendance.view']),           'roles' => null,   'is_system' => $sys],
            ['sort_order' => 32,  'type' => 'link',   'label' => 'Leave',             'icon' => 'bi-calendar-check',  'route' => 'leaves.index',           'route_pattern' => 'leaves.*',      'permissions' => json_encode(['leave.view','leave.apply']),  'roles' => null,   'is_system' => $sys],

            // ── Finance ───────────────────────────────────────────────
            ['sort_order' => 40,  'type' => 'header', 'label' => 'Finance',           'icon' => null,                  'route' => null,                    'route_pattern' => null,            'permissions' => json_encode(['payroll.view','payslip.view','payslip.download']), 'roles' => null, 'is_system' => $sys],
            ['sort_order' => 41,  'type' => 'link',   'label' => 'Payroll',           'icon' => 'bi-cash-stack',      'route' => 'payroll.index',          'route_pattern' => 'payroll.*',     'permissions' => json_encode(['payroll.view']),              'roles' => null,   'is_system' => $sys],
            ['sort_order' => 42,  'type' => 'link',   'label' => 'Payslips',          'icon' => 'bi-receipt',         'route' => 'payslips.index',         'route_pattern' => 'payslips.*',    'permissions' => json_encode(['payslip.view','payslip.download']), 'roles' => null, 'is_system' => $sys],

            // ── Education Config ──────────────────────────────────────
            ['sort_order' => 50,  'type' => 'header', 'label' => 'Education Config',  'icon' => null,                  'route' => null,                    'route_pattern' => null,            'permissions' => json_encode(['degree.manage']),             'roles' => null,   'is_system' => $sys],
            ['sort_order' => 51,  'type' => 'link',   'label' => 'Degree Types',      'icon' => 'bi-tags',            'route' => 'degree-types.index',     'route_pattern' => 'degree-types.*','permissions' => json_encode(['degree.manage']),             'roles' => null,   'is_system' => $sys],
            ['sort_order' => 52,  'type' => 'link',   'label' => 'Degree Names',      'icon' => 'bi-mortarboard',     'route' => 'degree-names.index',     'route_pattern' => 'degree-names.*','permissions' => json_encode(['degree.manage']),             'roles' => null,   'is_system' => $sys],

            // ── System ────────────────────────────────────────────────
            ['sort_order' => 60,  'type' => 'header', 'label' => 'System',            'icon' => null,                  'route' => null,                    'route_pattern' => null,            'permissions' => json_encode(['reports.view','roles.view','settings.view','settings.edit']), 'roles' => null, 'is_system' => $sys],
            ['sort_order' => 61,  'type' => 'link',   'label' => 'Reports',           'icon' => 'bi-bar-chart',       'route' => 'reports.index',          'route_pattern' => 'reports.*',     'permissions' => json_encode(['reports.view']),              'roles' => null,   'is_system' => $sys],
            ['sort_order' => 62,  'type' => 'link',   'label' => 'Roles & Permissions','icon'=> 'bi-shield-lock',     'route' => 'rbac.roles.index',       'route_pattern' => 'rbac.roles.*',  'permissions' => json_encode(['roles.view']),                'roles' => null,   'is_system' => $sys],
            ['sort_order' => 63,  'type' => 'link',   'label' => 'User Access',       'icon' => 'bi-people-fill',     'route' => 'rbac.users.index',       'route_pattern' => 'rbac.users.*',  'permissions' => json_encode(['roles.manage']),              'roles' => null,   'is_system' => $sys],
            ['sort_order' => 64,  'type' => 'link',   'label' => 'Settings',          'icon' => 'bi-gear',            'route' => 'settings.index',         'route_pattern' => 'settings.*',    'permissions' => json_encode(['settings.view']),             'roles' => null,   'is_system' => $sys],
            ['sort_order' => 65,  'type' => 'link',   'label' => 'Menu Manager',      'icon' => 'bi-list-nested',     'route' => 'menus.index',            'route_pattern' => 'menus.*',       'permissions' => json_encode(['settings.edit']),             'roles' => null,   'is_system' => $sys],

            // ── Always-visible ────────────────────────────────────────
            ['sort_order' => 70,  'type' => 'divider','label' => '',                  'icon' => null,                  'route' => null,                    'route_pattern' => null,            'permissions' => null,                                      'roles' => null,   'is_system' => $sys],
            ['sort_order' => 71,  'type' => 'link',   'label' => 'Profile',           'icon' => 'bi-person-gear',     'route' => 'profile.edit',           'route_pattern' => 'profile.*',     'permissions' => null,                                      'roles' => null,   'is_system' => $sys],
        ];

        foreach ($menus as &$m) {
            $m['is_active']   = true;
            $m['created_at']  = $now;
            $m['updated_at']  = $now;
        }

        DB::table('menus')->insert($menus);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_menu_overrides');
        Schema::dropIfExists('menus');
    }
};
