<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enhance salary_histories with full workflow fields:
 *  - previous_salary  : what the employee earned before this change
 *  - salary_type      : initial | increment | decrement | adjustment
 *  - reason           : short reason for the change
 *  - approved_by      : FK to users (who approved)
 *  - status           : pending | approved | rejected
 *
 * Existing rows are back-filled as salary_type=initial, status=approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_histories', function (Blueprint $table) {
            // previous_salary: what was the salary BEFORE this record
            $table->decimal('previous_salary', 12, 2)->nullable()->after('employee_id');

            // salary_type distinguishes the nature of the change
            $table->string('salary_type', 20)->default('initial')->after('base_salary');

            // reason: brief description (required for increment/decrement/adjustment)
            $table->string('reason', 300)->nullable()->after('effective_to');

            // approved_by: FK to the user who approved; null = self-approved (admin)
            $table->foreignId('approved_by')->nullable()->after('changed_by')
                  ->constrained('users')->nullOnDelete();

            // status: pending (awaiting admin approval) | approved | rejected
            $table->string('status', 20)->default('approved')->after('approved_by');

            // Index for fast status + employee lookups
            $table->index(['employee_id', 'status', 'effective_from'], 'sh_emp_status_eff');
        });

        // Back-fill existing rows
        DB::table('salary_histories')->update([
            'salary_type' => 'initial',
            'status'      => 'approved',
        ]);
    }

    public function down(): void
    {
        Schema::table('salary_histories', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex('sh_emp_status_eff');
            $table->dropColumn(['previous_salary', 'salary_type', 'reason', 'approved_by', 'status']);
        });
    }
};
