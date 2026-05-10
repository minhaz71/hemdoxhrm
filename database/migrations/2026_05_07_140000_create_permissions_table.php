<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();       // e.g. employee.create
            $table->string('label', 150);                // e.g. Create Employee
            $table->string('module', 50)->index();       // e.g. employee
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(true); // system perms cannot be deleted
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
