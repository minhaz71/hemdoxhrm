<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Nullable so existing rows are not broken before the data pass below
            $table->foreignId('designation_id')
                  ->nullable()
                  ->after('department')
                  ->constrained('designations')
                  ->nullOnDelete();
        });

        // ── Data migration ────────────────────────────────────────────
        // For every unique designation string already on employees, ensure
        // a row exists in designations, then back-fill designation_id.
        // Uses raw DB to avoid Eloquent / model-event complications.
        $now = now();

        $uniqueDesignations = DB::table('employees')
            ->whereNotNull('designation')
            ->distinct()
            ->pluck('designation');

        foreach ($uniqueDesignations as $name) {
            DB::table('designations')->insertOrIgnore([
                'name'       => $name,
                'status'     => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Link each employee to its designation row (case-insensitive match)
        DB::table('employees')
            ->whereNotNull('designation')
            ->orderBy('id')
            ->each(function ($emp) {
                $row = DB::table('designations')
                    ->whereRaw('LOWER(name) = LOWER(?)', [$emp->designation])
                    ->first();

                if ($row) {
                    DB::table('employees')
                        ->where('id', $emp->id)
                        ->update(['designation_id' => $row->id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['designation_id']);
            $table->dropColumn('designation_id');
        });
    }
};
