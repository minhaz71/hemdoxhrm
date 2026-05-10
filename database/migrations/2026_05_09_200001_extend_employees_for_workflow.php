<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('login_id', 50)->unique()->nullable()->after('user_id');
            $table->string('nid', 50)->nullable()->after('address');
            $table->string('nid_document', 500)->nullable()->after('nid');
            $table->string('certificate', 500)->nullable()->after('nid_document');
            $table->string('photo', 500)->nullable()->after('certificate');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['login_id', 'nid', 'nid_document', 'certificate', 'photo']);
        });
    }
};
