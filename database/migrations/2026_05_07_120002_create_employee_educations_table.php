<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('degree_type_id')->constrained();
            $table->foreignId('degree_name_id')->constrained();
            $table->string('institute_name', 200);
            $table->unsignedSmallInteger('passing_year');
            $table->string('result', 50)->nullable();
            $table->string('board_university', 200)->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index(['employee_id', 'passing_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_educations');
    }
};
