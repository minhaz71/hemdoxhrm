<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed the initial designation access setting
        DB::table('system_settings')->insert([
            'key'         => 'designation_hr_access',
            'value'       => 'false',
            'label'       => 'Allow HR to manage designations',
            'description' => 'When enabled, HR users can create, edit and delete designations in addition to admins.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
