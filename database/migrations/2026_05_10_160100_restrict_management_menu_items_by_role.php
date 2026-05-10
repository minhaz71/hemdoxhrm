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

        $adminHr = json_encode(['admin', 'hr']);
        $adminHrManager = json_encode(['admin', 'hr', 'manager']);

        DB::table('menus')->where('route', 'employees.index')->update(['roles' => $adminHr, 'updated_at' => now()]);
        DB::table('menus')->where('route', 'payroll.index')->update(['roles' => $adminHr, 'updated_at' => now()]);
        DB::table('menus')->where('route', 'payslips.index')->update(['roles' => $adminHr, 'updated_at' => now()]);
        DB::table('menus')->where('route', 'attendance.index')->update(['roles' => $adminHrManager, 'updated_at' => now()]);
        DB::table('menus')->where('route', 'reports.index')->update(['roles' => $adminHrManager, 'updated_at' => now()]);

        Cache::forget('menus_all');
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')
            ->whereIn('route', ['employees.index', 'payroll.index', 'payslips.index', 'attendance.index', 'reports.index'])
            ->update(['roles' => null, 'updated_at' => now()]);

        Cache::forget('menus_all');
    }
};
