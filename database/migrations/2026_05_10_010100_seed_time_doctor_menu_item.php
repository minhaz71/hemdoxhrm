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

        DB::table('menus')->updateOrInsert(
            ['route' => 'time-doctor.imports.index'],
            [
                'label' => 'Time Doctor Import',
                'icon' => 'bi-cloud-upload',
                'route_pattern' => 'time-doctor.*',
                'permissions' => null,
                'roles' => json_encode(['admin', 'hr']),
                'sort_order' => 33,
                'is_active' => true,
                'is_system' => true,
                'type' => 'link',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Cache::forget('menus_all');
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')
            ->where('route', 'time-doctor.imports.index')
            ->delete();

        Cache::forget('menus_all');
    }
};
