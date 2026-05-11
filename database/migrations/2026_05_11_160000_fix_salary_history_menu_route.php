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

        DB::table('menus')
            ->where('route', 'employees.salary-history.index')
            ->update([
                'route' => 'salary-history.index',
                'route_pattern' => 'salary-history.*',
                'updated_at' => now(),
            ]);

        Cache::forget('menus_all');
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')
            ->where('route', 'salary-history.index')
            ->update([
                'route' => 'employees.salary-history.index',
                'route_pattern' => 'employees.salary-history.*',
                'updated_at' => now(),
            ]);

        Cache::forget('menus_all');
    }
};
