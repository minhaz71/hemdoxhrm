<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds optional note/reason fields for weekly off preferences:
 *
 * • pending_registrations.weekly_off_note
 *     The applicant's written reason for requesting a particular weekly off day
 *     (e.g. "Friday is my religious observance day").
 *     HR sees this during approval and can accept, change, or acknowledge it.
 *
 * • employee_weekly_offs.note
 *     A free-text note stored on the final weekly-off record.
 *     Populated from the registration's weekly_off_note on approval,
 *     or written by HR/Admin when assigning/editing weekly offs directly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->text('weekly_off_note')->nullable()->after('weekly_off_days');
        });

        Schema::table('employee_weekly_offs', function (Blueprint $table) {
            $table->text('note')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn('weekly_off_note');
        });

        Schema::table('employee_weekly_offs', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
