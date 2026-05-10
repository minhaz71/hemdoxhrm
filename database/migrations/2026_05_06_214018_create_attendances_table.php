<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->time('check_in');
            $table->time('check_out')->nullable();
            $table->enum('status', ['present', 'late', 'absent'])->default('present');
            $table->string('note')->nullable();

            $table->timestamps();

            // One attendance per employee per day — enforced at DB level
            $table->unique(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
