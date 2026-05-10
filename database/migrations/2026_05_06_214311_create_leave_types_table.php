<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        // Paid Leave, Sick Leave, Unpaid Leave
            $table->string('slug')->unique();              // paid, sick, unpaid
            $table->integer('days_allowed')->default(0);  // annual entitlement (0 = unlimited)
            $table->boolean('is_paid')->default(true);    // affects payroll deduction
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
