<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harden registration_invitations token storage.
 *
 * - Adds `token_hash` (SHA-256 of the raw token) for secure lookup.
 * - Backfills existing rows so no invite breaks.
 * - Adds `abuse_blocked_at` timestamp for invite abuse detection.
 * - Adds `last_viewed_ip` and `view_count` for rate-abuse visibility.
 *
 * After this migration InvitationService should:
 *   - Store hash(token) in token_hash instead of raw token.
 *   - Look up by token_hash instead of token.
 *   - Optionally null-out the plain `token` column after cutover.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_invitations', function (Blueprint $table) {
            // SHA-256 hex (64 chars) of the raw token — for secure DB lookup
            $table->string('token_hash', 64)->nullable()->after('token');

            // Abuse / rate-abuse visibility columns
            $table->timestamp('abuse_blocked_at')->nullable()->after('expires_at');
            $table->unsignedSmallInteger('view_count')->default(0)->after('abuse_blocked_at');
            $table->string('last_viewed_ip', 45)->nullable()->after('view_count');
        });

        // ── Backfill token_hash for existing rows ─────────────────────
        // We can only compute a deterministic hash if the raw token is still present.
        DB::table('registration_invitations')
            ->whereNotNull('token')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('registration_invitations')
                        ->where('id', $row->id)
                        ->update(['token_hash' => hash('sha256', $row->token)]);
                }
            });

        // ── Add unique index AFTER backfill so there are no nulls ─────
        Schema::table('registration_invitations', function (Blueprint $table) {
            $table->unique('token_hash');
            $table->index('abuse_blocked_at');
        });
    }

    public function down(): void
    {
        Schema::table('registration_invitations', function (Blueprint $table) {
            $table->dropUnique(['token_hash']);
            $table->dropIndex(['abuse_blocked_at']);
            $table->dropColumn(['token_hash', 'abuse_blocked_at', 'view_count', 'last_viewed_ip']);
        });
    }
};
