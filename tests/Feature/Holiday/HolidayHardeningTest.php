<?php

namespace Tests\Feature\Holiday;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeWeeklyOff;
use App\Models\Holiday;
use App\Models\HolidayEmailLog;
use App\Models\User;
use App\Services\HolidayNotificationService;
use App\Services\HolidayService;
use App\Services\SettingService;
use App\Services\WeeklyOffService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Feature tests for Holiday & Weekly Off system hardening.
 *
 * Coverage:
 *   ✓ Permission security (HR guard, admin bypass)
 *   ✓ Duplicate holiday prevention (same-scope date-range overlap)
 *   ✓ Cross-field: holiday_year must match start_date year
 *   ✓ Date range validation (end >= start, etc.)
 *   ✓ Employee-specific holidays: no overlap restriction
 *   ✓ Weekly off effective-date range overlap detection
 *   ✓ Weekly off open-ended range correctly blocked
 *   ✓ Cron email duplicate prevention (unique index + race-condition path)
 *   ✓ notify_before_days falls back to admin setting, not hardcoded 1
 *   ✓ UpdateHolidayRequest excludes self from overlap check
 *   ✓ Edge cases: single-day holidays, zero-day notify, boundary overlap
 */
class HolidayHardeningTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ────────────────────────────────────────────────────────

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function hrUser(): User
    {
        return User::factory()->create(['role' => 'hr']);
    }

    /** Create a minimal active holiday. */
    private function makeHoliday(array $overrides = []): Holiday
    {
        return Holiday::factory()->create(array_merge([
            'type'               => 'global',
            'status'             => 'active',
            'start_date'         => '2026-12-25',
            'end_date'           => '2026-12-26',
            'holiday_year'       => 2026,
            'notify_before_days' => 3,
        ], $overrides));
    }

    /** Create a minimal active employee with a linked user. */
    private function makeEmployee(array $overrides = []): Employee
    {
        $user     = User::factory()->create(['role' => 'employee']);
        $employee = Employee::factory()->create(array_merge(['user_id' => $user->id], $overrides));

        return $employee;
    }

    // ═══════════════════════════════════════════════════════════════════
    // 1. Permission Security
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function admin_can_always_create_holidays_regardless_of_setting(): void
    {
        $admin = $this->adminUser();

        app(SettingService::class)->set('hr_manage_holidays', 'false', '', '', 'holiday');

        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), $this->validHolidayPayload());

        $response->assertRedirect(); // not 403
        $this->assertDatabaseHas('holidays', ['title' => 'Test Holiday']);
    }

    /** @test */
    public function hr_is_blocked_when_hr_manage_holidays_is_disabled(): void
    {
        $hr = $this->hrUser();

        app(SettingService::class)->set('hr_manage_holidays', 'false', '', '', 'holiday');

        $response = $this->actingAs($hr)
            ->post(route('holidays.store'), $this->validHolidayPayload());

        $response->assertStatus(403);
        $this->assertDatabaseMissing('holidays', ['title' => 'Test Holiday']);
    }

    /** @test */
    public function hr_can_create_holidays_when_hr_manage_holidays_is_enabled(): void
    {
        $hr = $this->hrUser();

        app(SettingService::class)->set('hr_manage_holidays', 'true', '', '', 'holiday');

        $response = $this->actingAs($hr)
            ->post(route('holidays.store'), $this->validHolidayPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('holidays', ['title' => 'Test Holiday']);
    }

    /** @test */
    public function admin_can_manage_weekly_offs_regardless_of_setting(): void
    {
        $admin    = $this->adminUser();
        $employee = $this->makeEmployee();

        app(SettingService::class)->set('hr_manage_weekly_off', 'false', '', '', 'weekly_off');

        $response = $this->actingAs($admin)
            ->post(route('weekly-offs.store'), [
                'employee_id'    => $employee->id,
                'day_of_week'    => 'friday',
                'effective_from' => '2026-01-01',
                'status'         => 'active',
            ]);

        $response->assertRedirect();
    }

    /** @test */
    public function hr_is_blocked_from_managing_weekly_offs_when_disabled(): void
    {
        $hr       = $this->hrUser();
        $employee = $this->makeEmployee();

        app(SettingService::class)->set('hr_manage_weekly_off', 'false', '', '', 'weekly_off');

        $response = $this->actingAs($hr)
            ->post(route('weekly-offs.store'), [
                'employee_id'    => $employee->id,
                'day_of_week'    => 'friday',
                'effective_from' => '2026-01-01',
                'status'         => 'active',
            ]);

        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════════
    // 2. Duplicate Holiday Prevention (Date-Range Overlap)
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function creating_a_global_holiday_that_overlaps_existing_one_fails_validation(): void
    {
        $admin = $this->adminUser();
        $this->makeHoliday([
            'type'       => 'global',
            'start_date' => '2026-12-24',
            'end_date'   => '2026-12-26',
        ]);

        // New holiday overlaps Dec 25 → should be rejected
        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), array_merge($this->validHolidayPayload(), [
                'type'       => 'global',
                'start_date' => '2026-12-25',
                'end_date'   => '2026-12-25',
            ]));

        $response->assertSessionHasErrors('start_date');
        $this->assertDatabaseCount('holidays', 1);
    }

    /** @test */
    public function adjacent_global_holidays_that_do_not_overlap_are_allowed(): void
    {
        $admin = $this->adminUser();
        $this->makeHoliday([
            'type'       => 'global',
            'start_date' => '2026-12-24',
            'end_date'   => '2026-12-25',
        ]);

        // New holiday starts Dec 26 — no overlap
        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), array_merge($this->validHolidayPayload(), [
                'type'       => 'global',
                'start_date' => '2026-12-26',
                'end_date'   => '2026-12-27',
            ]));

        $response->assertRedirect();
        $this->assertDatabaseCount('holidays', 2);
    }

    /** @test */
    public function branch_holidays_only_check_overlap_within_same_branch(): void
    {
        $admin    = $this->adminUser();
        $branch1  = Branch::factory()->create();
        $branch2  = Branch::factory()->create();

        $this->makeHoliday([
            'type'       => 'branch',
            'branch_id'  => $branch1->id,
            'start_date' => '2026-12-24',
            'end_date'   => '2026-12-26',
        ]);

        // Same date, different branch → allowed
        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), array_merge($this->validHolidayPayload(), [
                'type'       => 'branch',
                'branch_id'  => $branch2->id,
                'start_date' => '2026-12-25',
                'end_date'   => '2026-12-25',
            ]));

        $response->assertRedirect();
        $this->assertDatabaseCount('holidays', 2);
    }

    /** @test */
    public function employee_specific_holidays_are_never_blocked_by_overlap(): void
    {
        $admin = $this->adminUser();
        $this->makeHoliday([
            'type'       => 'employee_specific',
            'start_date' => '2026-12-24',
            'end_date'   => '2026-12-26',
        ]);

        // Same dates, employee_specific → no overlap restriction
        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), array_merge($this->validHolidayPayload(), [
                'type'       => 'employee_specific',
                'start_date' => '2026-12-25',
                'end_date'   => '2026-12-25',
                'employee_ids' => [],
            ]));

        $response->assertRedirect();
        $this->assertDatabaseCount('holidays', 2);
    }

    /** @test */
    public function updating_a_holiday_to_the_same_dates_is_allowed_self_exclusion(): void
    {
        $admin   = $this->adminUser();
        $holiday = $this->makeHoliday([
            'type'       => 'global',
            'start_date' => '2026-12-25',
            'end_date'   => '2026-12-25',
        ]);

        // Updating the same holiday with the same dates should NOT trigger overlap
        $response = $this->actingAs($admin)
            ->put(route('holidays.update', $holiday), array_merge($this->validHolidayPayload(), [
                'start_date' => '2026-12-25',
                'end_date'   => '2026-12-25',
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function inactive_holidays_do_not_block_new_holidays_on_same_dates(): void
    {
        $admin = $this->adminUser();
        $this->makeHoliday([
            'type'       => 'global',
            'status'     => 'inactive',
            'start_date' => '2026-12-25',
            'end_date'   => '2026-12-26',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), array_merge($this->validHolidayPayload(), [
                'type'       => 'global',
                'start_date' => '2026-12-25',
                'end_date'   => '2026-12-25',
            ]));

        $response->assertRedirect();
        $this->assertDatabaseCount('holidays', 2);
    }

    // ═══════════════════════════════════════════════════════════════════
    // 3. Cross-field Date Validation
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function holiday_year_mismatch_with_start_date_fails_validation(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), array_merge($this->validHolidayPayload(), [
                'holiday_year' => 2025,          // wrong year
                'start_date'   => '2026-12-25',  // actually 2026
            ]));

        $response->assertSessionHasErrors('holiday_year');
    }

    /** @test */
    public function holiday_year_matching_start_date_year_passes(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), $this->validHolidayPayload([
                'holiday_year' => 2026,
                'start_date'   => '2026-12-25',
                'end_date'     => '2026-12-26',
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function end_date_before_start_date_fails_validation(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), array_merge($this->validHolidayPayload(), [
                'start_date' => '2026-12-26',
                'end_date'   => '2026-12-24',
            ]));

        $response->assertSessionHasErrors('end_date');
    }

    /** @test */
    public function single_day_holiday_start_equals_end_is_valid(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), $this->validHolidayPayload([
                'start_date' => '2026-12-25',
                'end_date'   => '2026-12-25',
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    // ═══════════════════════════════════════════════════════════════════
    // 4. Weekly Off Effective-Date Range Overlap
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function creating_weekly_off_with_overlapping_open_ended_range_is_rejected(): void
    {
        $employee = $this->makeEmployee();

        EmployeeWeeklyOff::factory()->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'friday',
            'effective_from' => '2026-01-01',
            'effective_to'   => null,           // open-ended
            'status'         => 'active',
        ]);

        $this->expectException(ValidationException::class);

        app(WeeklyOffService::class)->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'friday',
            'effective_from' => '2026-06-01',   // starts within the open-ended range
            'effective_to'   => null,
            'status'         => 'active',
        ], $this->adminUser());
    }

    /** @test */
    public function creating_weekly_off_with_adjacent_non_overlapping_range_is_allowed(): void
    {
        $employee = $this->makeEmployee();

        EmployeeWeeklyOff::factory()->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'friday',
            'effective_from' => '2026-01-01',
            'effective_to'   => '2026-05-31',   // ends May 31
            'status'         => 'active',
        ]);

        // New range starts June 1 — no overlap
        $result = app(WeeklyOffService::class)->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'friday',
            'effective_from' => '2026-06-01',
            'effective_to'   => null,
            'status'         => 'active',
        ], $this->adminUser());

        $this->assertInstanceOf(EmployeeWeeklyOff::class, $result);
        $this->assertDatabaseCount('employee_weekly_offs', 2);
    }

    /** @test */
    public function different_days_of_week_can_have_overlapping_ranges(): void
    {
        $employee = $this->makeEmployee();

        EmployeeWeeklyOff::factory()->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'friday',
            'effective_from' => '2026-01-01',
            'effective_to'   => null,
            'status'         => 'active',
        ]);

        // Saturday is a different day — no conflict
        $result = app(WeeklyOffService::class)->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'saturday',
            'effective_from' => '2026-01-01',
            'effective_to'   => null,
            'status'         => 'active',
        ], $this->adminUser());

        $this->assertInstanceOf(EmployeeWeeklyOff::class, $result);
    }

    /** @test */
    public function partially_overlapping_weekly_off_ranges_are_rejected(): void
    {
        $employee = $this->makeEmployee();

        EmployeeWeeklyOff::factory()->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'saturday',
            'effective_from' => '2026-01-01',
            'effective_to'   => '2026-06-30',
            'status'         => 'active',
        ]);

        $this->expectException(ValidationException::class);

        app(WeeklyOffService::class)->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'saturday',
            'effective_from' => '2026-06-01',   // overlaps June 1-30
            'effective_to'   => '2026-12-31',
            'status'         => 'active',
        ], $this->adminUser());
    }

    /** @test */
    public function updating_weekly_off_excludes_self_from_overlap_check(): void
    {
        $employee = $this->makeEmployee();

        $existing = EmployeeWeeklyOff::factory()->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'friday',
            'effective_from' => '2026-01-01',
            'effective_to'   => null,
            'status'         => 'active',
        ]);

        // Updating the same record to a slightly different note — should not self-conflict
        $result = app(WeeklyOffService::class)->update($existing, [
            'employee_id'    => $employee->id,
            'day_of_week'    => 'friday',
            'effective_from' => '2026-01-01',
            'effective_to'   => null,
            'status'         => 'active',
            'note'           => 'Updated note',
        ], $this->adminUser());

        $this->assertEquals('Updated note', $result->note);
    }

    /** @test */
    public function inactive_weekly_off_does_not_block_new_active_one_on_same_range(): void
    {
        $employee = $this->makeEmployee();

        EmployeeWeeklyOff::factory()->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'friday',
            'effective_from' => '2026-01-01',
            'effective_to'   => null,
            'status'         => 'inactive',     // inactive — should not block
        ]);

        $result = app(WeeklyOffService::class)->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'friday',
            'effective_from' => '2026-01-01',
            'effective_to'   => null,
            'status'         => 'active',
        ], $this->adminUser());

        $this->assertInstanceOf(EmployeeWeeklyOff::class, $result);
    }

    // ═══════════════════════════════════════════════════════════════════
    // 5. Email Log Deduplication (Cron Safety)
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function email_is_not_sent_twice_when_log_already_shows_sent(): void
    {
        Mail::fake();

        $holiday  = $this->makeHoliday();
        $employee = $this->makeEmployee();

        // Pre-existing 'sent' log
        HolidayEmailLog::create([
            'holiday_id'  => $holiday->id,
            'employee_id' => $employee->id,
            'email'       => $employee->user->email,
            'subject'     => 'Test',
            'status'      => 'sent',
            'sent_at'     => now(),
        ]);

        $stats = app(HolidayNotificationService::class)->sendDueEmails();

        Mail::assertNothingSent();
        $this->assertEquals(0, $stats['sent']);
        $this->assertEquals(1, $stats['skipped']);
    }

    /** @test */
    public function db_unique_constraint_prevents_duplicate_email_log_rows(): void
    {
        $holiday  = $this->makeHoliday();
        $employee = $this->makeEmployee();

        HolidayEmailLog::create([
            'holiday_id'  => $holiday->id,
            'employee_id' => $employee->id,
            'email'       => 'test@example.com',
            'subject'     => 'Test',
            'status'      => 'pending',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        HolidayEmailLog::create([
            'holiday_id'  => $holiday->id,
            'employee_id' => $employee->id,
            'email'       => 'test@example.com',
            'subject'     => 'Test 2',
            'status'      => 'pending',
        ]);
    }

    /** @test */
    public function failed_email_is_retried_when_retry_flag_is_true(): void
    {
        Mail::fake();

        $holiday  = $this->makeHoliday(['start_date' => today()->toDateString(), 'end_date' => today()->toDateString()]);
        $employee = $this->makeEmployee();

        HolidayEmailLog::create([
            'holiday_id'    => $holiday->id,
            'employee_id'   => $employee->id,
            'email'         => $employee->user->email,
            'subject'       => 'Test',
            'status'        => 'failed',
            'error_message' => 'Connection refused',
        ]);

        $stats = app(HolidayNotificationService::class)->sendDueEmails(retryFailed: true);

        // Should have attempted (either sent or failed again, not skipped)
        $this->assertEquals(0, $stats['skipped']);
    }

    /** @test */
    public function failed_email_is_skipped_when_retry_flag_is_false(): void
    {
        Mail::fake();

        $holiday  = $this->makeHoliday(['start_date' => today()->toDateString(), 'end_date' => today()->toDateString()]);
        $employee = $this->makeEmployee();

        HolidayEmailLog::create([
            'holiday_id'    => $holiday->id,
            'employee_id'   => $employee->id,
            'email'         => $employee->user->email,
            'subject'       => 'Test',
            'status'        => 'failed',
            'error_message' => 'SMTP error',
        ]);

        $stats = app(HolidayNotificationService::class)->sendDueEmails(retryFailed: false);

        Mail::assertNothingSent();
        $this->assertEquals(0, $stats['sent']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // 6. notify_before_days Falls Back to Admin Setting
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function holiday_service_uses_admin_setting_as_default_for_notify_before_days(): void
    {
        app(SettingService::class)->set('holiday_notify_before_days', '7', '', '', 'holiday');

        $admin   = $this->adminUser();
        $payload = $this->validHolidayPayload();

        // Remove notify_before_days to trigger fallback
        unset($payload['notify_before_days']);

        $service = app(HolidayService::class);
        $holiday = $service->create(array_merge($payload, ['notify_before_days' => null]) + ['notify_before_days' => 7], $admin);

        // When the form omits notify_before_days (submit as 0 edge-case),
        // the service's payload() must read the setting not use hardcoded 1.
        // We test directly via the service:
        $holiday2 = $service->create(
            array_merge($this->validHolidayPayload(), ['notify_before_days' => null, 'title' => 'Holiday2']),
            $admin
        );

        $this->assertEquals(7, $holiday2->notify_before_days);
    }

    /** @test */
    public function email_notification_disabled_by_setting_returns_disabled_flag(): void
    {
        app(SettingService::class)->set('holiday_email_enabled', 'false', '', '', 'holiday');

        $stats = app(HolidayNotificationService::class)->sendDueEmails();

        $this->assertTrue($stats['disabled'] ?? false);
        $this->assertEquals(0, $stats['sent']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // 7. Edge Cases
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function zero_notify_before_days_is_valid(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), $this->validHolidayPayload(['notify_before_days' => 0]));

        $response->assertRedirect();
        $this->assertDatabaseHas('holidays', ['notify_before_days' => 0]);
    }

    /** @test */
    public function notify_before_days_above_30_is_rejected(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), $this->validHolidayPayload(['notify_before_days' => 31]));

        $response->assertSessionHasErrors('notify_before_days');
    }

    /** @test */
    public function new_year_holiday_spanning_year_boundary_rejects_mismatched_year(): void
    {
        $admin = $this->adminUser();

        // Dec 31 → Jan 1 span: holiday_year should match start_date year (2026)
        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), array_merge($this->validHolidayPayload(), [
                'holiday_year' => 2027,          // wrong — start_date is in 2026
                'start_date'   => '2026-12-31',
                'end_date'     => '2027-01-01',
            ]));

        $response->assertSessionHasErrors('holiday_year');
    }

    /** @test */
    public function weekly_off_with_null_effective_to_overlaps_all_future_dates(): void
    {
        $employee = $this->makeEmployee();

        // Open-ended from 2026-01-01
        EmployeeWeeklyOff::factory()->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'sunday',
            'effective_from' => '2026-01-01',
            'effective_to'   => null,
            'status'         => 'active',
        ]);

        // New range starts 10 years later — still blocked by open-ended existing
        $this->expectException(ValidationException::class);

        app(WeeklyOffService::class)->create([
            'employee_id'    => $employee->id,
            'day_of_week'    => 'sunday',
            'effective_from' => '2036-01-01',
            'effective_to'   => null,
            'status'         => 'active',
        ], $this->adminUser());
    }

    /** @test */
    public function boundary_holiday_overlap_exactly_touching_end_date_is_caught(): void
    {
        $admin = $this->adminUser();

        $this->makeHoliday([
            'type'       => 'global',
            'start_date' => '2026-12-20',
            'end_date'   => '2026-12-25',
        ]);

        // New holiday start_date == existing end_date (Dec 25) — this IS an overlap
        $response = $this->actingAs($admin)
            ->post(route('holidays.store'), array_merge($this->validHolidayPayload(), [
                'type'       => 'global',
                'start_date' => '2026-12-25',
                'end_date'   => '2026-12-28',
            ]));

        $response->assertSessionHasErrors('start_date');
    }

    // ═══════════════════════════════════════════════════════════════════
    // 8. Employee-Specific Holiday Logic
    // ═══════════════════════════════════════════════════════════════════

    /** @test */
    public function employee_specific_holiday_only_notifies_assigned_employees(): void
    {
        Mail::fake();

        $employee1 = $this->makeEmployee();
        $employee2 = $this->makeEmployee();

        $holiday = $this->makeHoliday([
            'type'       => 'employee_specific',
            'start_date' => today()->toDateString(),
            'end_date'   => today()->toDateString(),
        ]);

        // Only employee1 is attached
        $holiday->employees()->attach($employee1->id);

        $stats = app(HolidayNotificationService::class)->sendDueEmails();

        // employee2 should not receive email
        $this->assertEquals(1, $stats['sent'] + $stats['failed'] + $stats['skipped']);
    }

    /** @test */
    public function employee_with_no_linked_user_is_skipped_for_email(): void
    {
        Mail::fake();

        $employee = Employee::factory()->create(['user_id' => null]);

        $holiday = $this->makeHoliday([
            'type'       => 'employee_specific',
            'start_date' => today()->toDateString(),
            'end_date'   => today()->toDateString(),
        ]);

        $holiday->employees()->attach($employee->id);

        $stats = app(HolidayNotificationService::class)->sendDueEmails();

        Mail::assertNothingSent();
        $this->assertEquals(0, $stats['sent']);
    }

    // ── Private fixtures ───────────────────────────────────────────────

    private function validHolidayPayload(array $overrides = []): array
    {
        return array_merge([
            'title'              => 'Test Holiday',
            'reason'             => 'Annual celebration',
            'holiday_year'       => 2026,
            'start_date'         => '2026-12-25',
            'end_date'           => '2026-12-26',
            'type'               => 'global',
            'branch_id'          => null,
            'department_id'      => null,
            'employee_ids'       => [],
            'notify_before_days' => 3,
            'status'             => 'active',
        ], $overrides);
    }
}
