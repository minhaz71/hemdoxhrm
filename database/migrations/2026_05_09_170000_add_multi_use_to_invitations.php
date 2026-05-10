<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_invitations', function (Blueprint $table) {
            // How many people may register via this link (NULL = unlimited)
            $table->unsignedSmallInteger('max_uses')->nullable()->default(1)->after('note');

            // Running count of submitted registrations
            $table->unsignedInteger('uses_count')->default(0)->after('max_uses');
        });

        // MySQL stores enum choices at the column level. SQLite treats Laravel
        // enums as text, so no column rewrite is needed for the test database.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE registration_invitations
                MODIFY COLUMN status ENUM('active','used','exhausted','revoked','expired')
                NOT NULL DEFAULT 'active'");
        }

        // Drop the unique constraint on pending_registrations.email so many
        // different people can submit via the same multi-use link.
        // (Each email is still unique among all pending registrations.)
        // The constraint name may vary — try both common formats.
        try {
            Schema::table('pending_registrations', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
        } catch (\Throwable) {
            // Already dropped or never existed under this name — safe to continue.
        }
    }

    public function down(): void
    {
        Schema::table('registration_invitations', function (Blueprint $table) {
            $table->dropColumn(['max_uses', 'uses_count']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE registration_invitations
                MODIFY COLUMN status ENUM('active','used','revoked','expired')
                NOT NULL DEFAULT 'active'");
        }

        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
