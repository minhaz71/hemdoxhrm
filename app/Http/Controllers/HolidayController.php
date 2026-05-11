<?php

namespace App\Http\Controllers;

use App\Http\Requests\Holiday\StoreHolidayRequest;
use App\Http\Requests\Holiday\UpdateHolidayRequest;
use App\Models\Holiday;
use App\Services\HolidayNotificationService;
use App\Services\HolidayService;
use App\Services\SettingService;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function __construct(
        private readonly HolidayService             $holidays,
        private readonly HolidayNotificationService $notifications,
        private readonly SettingService             $settings,
    ) {}

    /**
     * Deny HR users if the admin has disabled HR holiday management.
     * Admins always pass through.
     */
    private function guardHrAccess(): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return; // admin always allowed

        $hrAllowed = $this->settings->get('hr_manage_holidays', true);
        if (! $hrAllowed || $hrAllowed === 'false') {
            abort(403, 'HR management of holidays is disabled by the administrator. Contact an Admin.');
        }
    }

    public function index(Request $request)
    {
        $holidays = $this->holidays->paginate($request->only(['year', 'status']));

        return view('holidays.index', compact('holidays'));
    }

    public function create()
    {
        $this->guardHrAccess();

        return view('holidays.create', $this->holidays->formOptions());
    }

    public function store(StoreHolidayRequest $request)
    {
        $this->guardHrAccess();

        $holiday = $this->holidays->create($request->validated(), $request->user());

        return redirect()->route('holidays.show', $holiday)->with('success', 'Holiday created successfully.');
    }

    public function show(Holiday $holiday)
    {
        $holiday->load(['branch', 'department', 'employees', 'creator', 'emailLogs.employee']);

        return view('holidays.show', compact('holiday'));
    }

    public function edit(Holiday $holiday)
    {
        $holiday->load('employees');

        return view('holidays.edit', array_merge($this->holidays->formOptions(), compact('holiday')));
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday)
    {
        $this->guardHrAccess();

        $holiday = $this->holidays->update($holiday, $request->validated(), $request->user());

        return redirect()->route('holidays.show', $holiday)->with('success', 'Holiday updated successfully.');
    }

    public function toggle(Request $request, Holiday $holiday)
    {
        $this->guardHrAccess();

        $this->holidays->toggle($holiday, $request->user());

        return back()->with('success', 'Holiday status updated.');
    }

    /**
     * Manually trigger email sending for a specific holiday (HR/Admin action).
     * Useful when a holiday was just created or emails need to be sent immediately.
     */
    public function sendEmails(Holiday $holiday)
    {
        // Build a single-holiday version using the service's internal logic
        $holiday->loadMissing(['branch', 'department']);
        $employees = $this->holidays->employeesForHoliday($holiday);

        $sent   = 0;
        $failed = 0;

        foreach ($employees as $employee) {
            // Delegate to service — respects deduplication (won't re-send already sent)
            $result = $this->dispatchSingle($holiday, $employee);
            $result === 'sent' ? $sent++ : ($result === 'failed' ? $failed++ : null);
        }

        if ($failed > 0) {
            return back()->with('warning', "Sent {$sent} email(s). {$failed} failed — check email logs.");
        }

        return back()->with('success', $sent > 0
            ? "Sent {$sent} reminder email(s) successfully."
            : 'All emails were already delivered to eligible employees.');
    }

    /**
     * Retry only failed emails for a specific holiday.
     */
    public function retryEmails(Holiday $holiday)
    {
        $stats = $this->notifications->retryFailed();

        return back()->with(
            $stats['failed'] > 0 ? 'warning' : 'success',
            "Retry complete — {$stats['sent']} sent, {$stats['failed']} still failed."
        );
    }

    /** Small inline wrapper to reuse HolidayNotificationService's private logic via reflection-free approach. */
    private function dispatchSingle(Holiday $holiday, $employee): string
    {
        $email = $employee->user?->email;
        if (! $email) return 'skipped';

        $log = \App\Models\HolidayEmailLog::where('holiday_id', $holiday->id)
            ->where('employee_id', $employee->id)
            ->where('status', 'sent')
            ->exists();

        if ($log) return 'skipped';

        $emails = collect([$employee->user?->email, $employee->user?->alternate_email])
            ->map(fn ($address) => strtolower(trim((string) $address)))
            ->filter(fn ($address) => filter_var($address, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        try {
            \Illuminate\Support\Facades\Mail::to($emails)
                ->send(new \App\Mail\HolidayReminderMail($holiday, $employee));

            \App\Models\HolidayEmailLog::updateOrCreate(
                ['holiday_id' => $holiday->id, 'employee_id' => $employee->id],
                [
                    'email'     => $email,
                    'subject'   => "[Holiday Notice] {$holiday->title}",
                    'status'    => 'sent',
                    'sent_at'   => now(),
                    'error_message' => null,
                ]
            );

            if ($holiday->email_sent_at === null) {
                $holiday->update(['email_sent_at' => now()]);
            }

            return 'sent';
        } catch (\Throwable $e) {
            \App\Models\HolidayEmailLog::updateOrCreate(
                ['holiday_id' => $holiday->id, 'employee_id' => $employee->id],
                [
                    'email'         => $email,
                    'subject'       => "[Holiday Notice] {$holiday->title}",
                    'status'        => 'failed',
                    'error_message' => substr($e->getMessage(), 0, 500),
                ]
            );
            return 'failed';
        }
    }
}
