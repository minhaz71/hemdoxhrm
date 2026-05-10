<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_id', 50)->unique()->nullable()->after('email');
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'correction_required'])
                  ->nullable()
                  ->after('login_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login_id', 'approval_status']);
        });
    }
};
