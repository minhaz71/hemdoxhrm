<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->string('login_id', 50)->unique()->after('email');
            $table->string('password', 255)->after('login_id');
            $table->string('nid', 50)->nullable()->after('address');
            $table->string('nid_document', 500)->nullable()->after('nid');
            $table->string('certificate', 500)->nullable()->after('nid_document');
            $table->string('photo', 500)->nullable()->after('certificate');
            $table->text('correction_note')->nullable()->after('rejection_note');
        });

        // MySQL stores enum choices at the column level. SQLite treats Laravel
        // enums as text, so no column rewrite is needed for the test database.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pending_registrations MODIFY COLUMN status ENUM('pending','approved','rejected','correction_required') DEFAULT 'pending'");
        }

        // Drop email unique index if it exists (may have been removed in a prior migration)
        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE pending_registrations DROP INDEX IF EXISTS pending_registrations_email_unique");
            } else {
                Schema::table('pending_registrations', function (Blueprint $table) {
                    $table->dropUnique(['email']);
                });
            }
        } catch (\Throwable $e) {
            // Index may not exist — safe to ignore
        }
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn(['login_id', 'password', 'nid', 'nid_document', 'certificate', 'photo', 'correction_note']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pending_registrations MODIFY COLUMN status ENUM('pending','approved','rejected') DEFAULT 'pending'");
        }
    }
};
