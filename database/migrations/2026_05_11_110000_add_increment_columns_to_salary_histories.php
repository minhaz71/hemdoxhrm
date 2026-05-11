<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_histories', function (Blueprint $table) {
            $table->decimal('increment_amount', 12, 2)->nullable()->after('base_salary');
            $table->decimal('increment_percentage', 7, 4)->nullable()->after('increment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('salary_histories', function (Blueprint $table) {
            $table->dropColumn(['increment_amount', 'increment_percentage']);
        });
    }
};
