<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'organization_employee_code')) {
                $table->string('organization_employee_code', 80)
                    ->nullable()
                    ->unique()
                    ->after('employee_code');
            }
        });

        Schema::table('pending_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('pending_registrations', 'organization_employee_code')) {
                $table->string('organization_employee_code', 80)
                    ->nullable()
                    ->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('pending_registrations', 'organization_employee_code')) {
                $table->dropColumn('organization_employee_code');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'organization_employee_code')) {
                $table->dropUnique(['organization_employee_code']);
                $table->dropColumn('organization_employee_code');
            }
        });
    }
};
