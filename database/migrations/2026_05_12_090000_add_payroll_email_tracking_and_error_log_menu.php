<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('payrolls', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable()->after('paid_by')->index();
            }
            if (! Schema::hasColumn('payrolls', 'email_sent_by')) {
                $table->foreignId('email_sent_by')->nullable()->after('email_sent_at')->constrained('users')->nullOnDelete();
            }
        });

        if (Schema::hasTable('menus') && ! DB::table('menus')->where('route', 'admin.error-logs.index')->exists()) {
            DB::table('menus')->insert([
                'type' => 'link',
                'label' => 'Error Logs',
                'icon' => 'bi-bug',
                'route' => 'admin.error-logs.index',
                'route_pattern' => 'admin.error-logs.*',
                'permissions' => null,
                'roles' => json_encode(['admin']),
                'sort_order' => (DB::table('menus')->max('sort_order') ?? 100) + 5,
                'is_active' => true,
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Cache::forget('menus_all');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route', 'admin.error-logs.index')->delete();
            Cache::forget('menus_all');
        }

        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'email_sent_by')) {
                $table->dropConstrainedForeignId('email_sent_by');
            }
            if (Schema::hasColumn('payrolls', 'email_sent_at')) {
                $table->dropColumn('email_sent_at');
            }
        });
    }
};
