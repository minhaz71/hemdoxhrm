<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees') || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasColumn('employees', 'department')) {
            DB::statement('ALTER TABLE employees MODIFY department VARCHAR(255) NULL');
        }

        if (Schema::hasColumn('employees', 'designation')) {
            DB::statement('ALTER TABLE employees MODIFY designation VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees') || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasColumn('employees', 'department')) {
            DB::statement("UPDATE employees SET department = '' WHERE department IS NULL");
            DB::statement("ALTER TABLE employees MODIFY department VARCHAR(255) NOT NULL DEFAULT ''");
        }

        if (Schema::hasColumn('employees', 'designation')) {
            DB::statement("UPDATE employees SET designation = '' WHERE designation IS NULL");
            DB::statement("ALTER TABLE employees MODIFY designation VARCHAR(255) NOT NULL DEFAULT ''");
        }
    }
};
