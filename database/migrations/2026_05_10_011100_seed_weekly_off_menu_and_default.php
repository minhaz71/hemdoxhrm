<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => 'default_weekly_off_days'],
                [
                    'group' => 'attendance',
                    'type' => 'text',
                    'sort_order' => 12,
                    'label' => 'Default Weekly Off Days',
                    'description' => 'Comma-separated days preselected on employee creation and registration forms.',
                    'value' => '',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        if (Schema::hasTable('menus')) {
            DB::table('menus')->updateOrInsert(
                ['route' => 'weekly-offs.index'],
                [
                    'label' => 'Weekly Offs',
                    'icon' => 'bi-calendar2-week',
                    'route_pattern' => 'weekly-offs.*',
                    'permissions' => null,
                    'roles' => json_encode(['admin', 'hr']),
                    'sort_order' => 35,
                    'is_active' => true,
                    'is_system' => true,
                    'type' => 'link',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        Cache::forget('menus_all');
        Cache::forget('system_settings_all');
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route', 'weekly-offs.index')->delete();
        }

        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')->where('key', 'default_weekly_off_days')->delete();
        }

        Cache::forget('menus_all');
        Cache::forget('system_settings_all');
    }
};
