<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pending_registration_id')
                  ->constrained('pending_registrations')
                  ->cascadeOnDelete();
            $table->foreignId('actor_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->enum('action', [
                'submitted',
                'edited',
                'approved',
                'rejected',
                'correction_requested',
                'resubmitted',
            ]);
            $table->text('note')->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->index(['pending_registration_id', 'created_at'], 'reg_activity_logs_reg_id_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_activity_logs');
    }
};
