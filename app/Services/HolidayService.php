<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class HolidayService
{
    public const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    public function __construct(private readonly SettingService $settings) {}

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Holiday::with(['branch', 'department', 'creator'])
            ->when($filters['year'] ?? null, fn ($q, $year) => $q->forYear((int) $year))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest('start_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, User $actor): Holiday
    {
        return DB::transaction(function () use ($data, $actor) {
            $holiday = Holiday::create($this->payload($data) + [
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $holiday->employees()->sync($data['employee_ids'] ?? []);

            return $holiday->fresh(['employees', 'branch', 'department']);
        });
    }

    public function update(Holiday $holiday, array $data, User $actor): Holiday
    {
        return DB::transaction(function () use ($holiday, $data, $actor) {
            $holiday->update($this->payload($data) + ['updated_by' => $actor->id]);
            $holiday->employees()->sync($data['employee_ids'] ?? []);

            return $holiday->fresh(['employees', 'branch', 'department']);
        });
    }

    public function toggle(Holiday $holiday, User $actor): Holiday
    {
        $holiday->update([
            'status'     => $holiday->status === 'active' ? 'inactive' : 'active',
            'updated_by' => $actor->id,
        ]);

        return $holiday->fresh();
    }

    public function employeesForHoliday(Holiday $holiday)
    {
        return Employee::with('user')
            ->active()
            ->when($holiday->type === 'branch',    fn ($q) => $q->where('branch_id', $holiday->branch_id))
            ->when($holiday->type === 'department', fn ($q) => $q->where('department_id', $holiday->department_id))
            ->when($holiday->type === 'employee_specific',
                fn ($q) => $q->whereHas('holidays', fn ($h) => $h->whereKey($holiday->id))
            )
            ->whereHas('user')
            ->get();
    }

    /**
     * @deprecated Use HolidayNotificationService::sendDueEmails() directly.
     *             Kept for backward compatibility with any existing callers.
     */
    public function sendDueHolidayEmails(): int
    {
        $stats = app(HolidayNotificationService::class)->sendDueEmails();

        return $stats['sent'];
    }

    public function formOptions(): array
    {
        return [
            'branches'    => Branch::active()->orderBy('name')->get(),
            'departments' => Department::active()->orderBy('name')->get(),
            'employees'   => Employee::active()->orderBy('first_name')->get(),
            'days'        => self::DAYS,
        ];
    }

    // ── Private helpers ────────────────────────────────────────────────

    private function payload(array $data): array
    {
        // Fall back to the admin-configured default rather than a hardcoded 1.
        $defaultNotifyDays = (int) $this->settings->get('holiday_notify_before_days', 3);

        return [
            'title'              => $data['title'],
            'reason'             => $data['reason'] ?? null,
            'holiday_year'       => (int) $data['holiday_year'],
            'start_date'         => $data['start_date'],
            'end_date'           => $data['end_date'],
            'type'               => $data['type'],
            'branch_id'          => $data['type'] === 'branch'      ? ($data['branch_id']     ?? null) : null,
            'department_id'      => $data['type'] === 'department'   ? ($data['department_id'] ?? null) : null,
            'status'             => $data['status'] ?? 'active',
            'notify_before_days' => (int) ($data['notify_before_days'] ?? $defaultNotifyDays),
        ];
    }
}
