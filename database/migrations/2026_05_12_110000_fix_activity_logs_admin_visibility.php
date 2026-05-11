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

        $menu = DB::table('menus')->where('route', 'activity.index')->first();

        if ($menu) {
            DB::table('menus')
                ->where('id', $menu->id)
                ->update([
                    'roles' => json_encode(['admin', 'hr']),
                    'permissions' => null,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
        }

        if ($menu && Schema::hasTable('user_menu_overrides')) {
            $adminUserIds = DB::table('users')
                ->join('role_user', 'role_user.user_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->where('roles.name', 'admin')
                ->pluck('users.id');

            DB::table('user_menu_overrides')
                ->where('menu_id', $menu->id)
                ->whereIn('user_id', $adminUserIds)
                ->where('visible', false)
                ->delete();

            foreach ($adminUserIds as $userId) {
                Cache::forget("menu_overrides_{$userId}");
            }
        }

        Cache::forget('menus_all');
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')
                ->where('route', 'activity.index')
                ->update(['roles' => json_encode(['admin', 'hr']), 'updated_at' => now()]);

            Cache::forget('menus_all');
        }
    }
};
