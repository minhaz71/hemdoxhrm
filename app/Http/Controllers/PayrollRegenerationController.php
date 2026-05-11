<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollRegenerationLog;
use App\Services\PayrollRegenerationService;
use Illuminate\Http\Request;

class PayrollRegenerationController extends Controller
{
    public function __construct(
        private readonly PayrollRegenerationService $service,
    ) {}

    // ── GET /payroll-regeneration — main admin UI ─────────────────

    public function index(Request $request)
    {
        $this->authorize('viewAny', PayrollRegenerationLog::class);

        $employees = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $logs      = $this->service->paginateLogs();

        return view('payroll-regeneration.index', compact('employees', 'logs'));
    }

    // ── POST /payroll-regeneration — regenerate one employee ──────

    public function store(Request $request)
    {
        $this->authorize('create', PayrollRegenerationLog::class);

        $data = $request->validate([
            'employee_id'           => ['required', 'exists:employees,id'],
            'month'                 => ['required', 'integer', 'min:1', 'max:12'],
            'year'                  => ['required', 'integer', 'min:2020'],
            'reason'                => ['nullable', 'string', 'max:1000'],
            'force_locked_override' => ['nullable', 'boolean'],
        ]);

        $employee       = Employee::findOrFail($data['employee_id']);
        $month          = (int) $data['month'];
        $year           = (int) $data['year'];
        $reason         = $data['reason'] ?? null;
        $forceLocked    = (bool) ($data['force_locked_override'] ?? false);

        // Check if there's an existing payroll so we can warn the user
        $existing = Payroll::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        // If locked and user didn't explicitly tick the override, bounce back with confirmation
        if ($existing && $existing->isLocked() && ! $forceLocked) {
            return redirect()->back()
                ->withInput()
                ->with('confirm_locked', [
                    'employee_id' => $employee->id,
                    'month'       => $month,
                    'year'        => $year,
                    'employee'    => $employee->full_name,
                    'period'      => $existing->month_label,
                    'paid_at'     => $existing->paid_at?->format('M j, Y'),
                ]);
        }

        try {
            $newPayroll = $this->service->regenerate(
                employee:             $employee,
                month:                $month,
                year:                 $year,
                actor:                auth()->user(),
                reason:               $reason,
                forceOverrideLocked:  $forceLocked,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        }

        return redirect()->route('payroll-regeneration.index')
            ->with('success', "Payroll regenerated for {$employee->full_name} ({$newPayroll->month_label}).");
    }

    // ── POST /payroll-regeneration/bulk — regenerate full period ──

    public function bulk(Request $request)
    {
        $this->authorize('create', PayrollRegenerationLog::class);

        $data = $request->validate([
            'month'          => ['required', 'integer', 'min:1', 'max:12'],
            'year'           => ['required', 'integer', 'min:2020'],
            'include_locked' => ['nullable', 'boolean'],
            'reason'         => ['nullable', 'string', 'max:1000'],
        ]);

        $month         = (int) $data['month'];
        $year          = (int) $data['year'];
        $includeLocked = (bool) ($data['include_locked'] ?? false);
        $reason        = $data['reason'] ?? null;

        if ($includeLocked && ! auth()->user()->isAdmin()) {
            return redirect()->back()->with('error', 'Only admins can force-regenerate locked payrolls.');
        }

        $results = $this->service->regenerateBulk(
            month:                      $month,
            year:                       $year,
            actor:                      auth()->user(),
            includeLockedWithReason:    $includeLocked,
            reason:                     $reason,
        );

        $msg = "{$results['regenerated']} payroll(s) regenerated, {$results['skipped']} skipped.";
        if (! empty($results['errors'])) {
            $msg .= ' Errors: ' . implode('; ', $results['errors']);
        }

        return redirect()->route('payroll-regeneration.index')->with('success', $msg);
    }

    // ── GET /payroll-regeneration/logs — audit log list ───────────

    public function logs(Request $request)
    {
        $this->authorize('viewAny', PayrollRegenerationLog::class);

        $logs = $this->service->paginateLogs(30);

        return view('payroll-regeneration.logs', compact('logs'));
    }

    // ── GET /payroll-regeneration/logs/{log} — log detail ─────────

    public function logShow(PayrollRegenerationLog $log)
    {
        $this->authorize('viewAny', PayrollRegenerationLog::class);

        $log->load(['employee', 'regeneratedBy', 'payroll']);

        return view('payroll-regeneration.log-show', compact('log'));
    }
}
