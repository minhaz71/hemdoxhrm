<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add salary resolution audit columns to payrolls:
 *
 *  salary_resolution_mode  — which mode was used (month_start / month_end / prorated)
 *  salary_segments         — JSON breakdown of salary segments (prorated only)
 *  salary_had_mid_change   — whether a mid-month salary change was detected
 *
 * Also seeds the default `salary_change_effect_mode` system setting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('salary_resolution_mode', 20)->nullable()->after('base_salary');
            $table->boolean('salary_had_mid_change')->default(false)->after('salary_resolution_mode');
            $table->json('salary_segments')->nullable()->after('salary_had_mid_change');
        });

        // Seed the default setting
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')->upsert(
                [[
                    'key'         => 'salary_change_effect_mode',
                    'value'       => 'month_start',
                    'label'       => 'Salary Change Effect Mode',
                    'description' => 'How mid-month salary changes affect payroll: month_start, month_end, or prorated.',
                    'group'       => 'payroll',
                    'type'        => 'string',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]],
                ['key'],
                ['value', 'label', 'description', 'updated_at']
            );
        }
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['salary_resolution_mode', 'salary_had_mid_change', 'salary_segments']);
        });

        DB::table('system_settings')->where('key', 'salary_change_effect_mode')->delete();
    }
};
