<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('email')->nullable();           // optional pre-fill
            $table->string('note')->nullable();            // internal note for HR
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();      // set when form submitted
            $table->enum('status', ['active', 'used', 'revoked', 'expired'])->default('active');
            $table->timestamps();

            $table->index('token');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_invitations');
    }
};
