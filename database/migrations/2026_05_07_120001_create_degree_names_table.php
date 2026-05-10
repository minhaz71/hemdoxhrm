<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('degree_names', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            // Optional link: if set, this name is only shown when that type is selected
            $table->foreignId('degree_type_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('degree_type_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('degree_names');
    }
};
