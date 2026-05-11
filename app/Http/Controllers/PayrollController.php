<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payroll\AdjustPayrollRequest;
use App\Http\Requests\Payroll\GeneratePayrollRequest;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollService;
use App\Services\SettingService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService,
        private readonly SettingService $settings,
    ) {}

    // GET /payroll — list by period
    public function index(Request $request)
    {
        $month   = (int) $request->get('month', now()->month);
        $year    = (int) $request->get('year',  now()->year);
        $summary = $this->payrollService->periodSummary($month, $year);
        $records = $this->payrollService->paginateByPeriod($month, $year);
        $overtimeEnabled = $this->isOvertimeEnabled();

        return view('payroll.index', compact('records', 'summary', 'month', 'year', 'overtimeEnabled'));
    }

    // GET /payroll/create — generate form
    public function create()
    {
        $employees = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'base_salary']);
        $overtimeEnabled = $this->isOvertimeEnabled();

        return view('payroll.create', compact('employees', 'overtimeEnabled'));
    }

    // POST /payroll — generate single or bulk
    public function store(GeneratePayrollRequest $request)
    {
        $data  = $request->validated();
        $month = (int) $data['month'];
        $year  = (int) $data['year'];

        if (! empty($data['employee_id'])) {
            // Single employee
            $employee = Employee::findOrFail($data['employee_id']);
            $this->payrollService->generate($employee, $month, $year, $data);
            $msg = "Payroll generated for {$employee->full_name}.";
        } else {
            // Bulk — all active employees
            $result = $this->payrollService->generateBulk($month, $year);
            $msg = "Bulk payroll: {$result['created']} generated, {$result['skipped']} skipped.";
            if (! empty($result['errors'])) {
                $msg .= ' Errors: ' . implode('; ', $result['errors']);
            }
        }

        return redirect()->route('payroll.index', compact('month', 'year'))
            ->with('success', $msg);
    }

    // GET /payroll/{payroll} — detail view
    public function show(Payroll $payroll)
    {
        $this->authorize('view', $payroll);

        $payroll->load(['employee', 'paidBy']);
        $overtimeEnabled = $payroll->overtime_enabled || $this->isOvertimeEnabled();

        return view('payroll.show', compact('payroll', 'overtimeEnabled'));
    }

    // GET /payroll/my — employee self-service salary history
    public function my(Request $request)
    {
        $employee = auth()->user()->employee;

        abort_unless($employee, 404, 'No employee record linked to your account.');

        $records = $this->payrollService->paginateForEmployee($employee);
        $summary = $this->payrollService->employeeSummary($employee);
        $overtimeEnabled = $this->isOvertimeEnabled();

        return view('payroll.my', compact('employee', 'records', 'summary', 'overtimeEnabled'));
    }

    // GET /payroll/{payroll}/edit — adjust form (before payment)
    public function edit(Payroll $payroll)
    {
        $this->authorize('update', $payroll);

        $payroll->load('employee');
        $overtimeEnabled = $payroll->overtime_enabled || $this->isOvertimeEnabled();

        return view('payroll.edit', compact('payroll', 'overtimeEnabled'));
    }

    // PATCH /payroll/{payroll} — apply adjustments
    public function update(AdjustPayrollRequest $request, Payroll $payroll)
    {
        $this->payrollService->adjust($payroll, $request->validated());

        return redirect()->route('payroll.show', $payroll)
            ->with('success', 'Payroll updated successfully.');
    }

    // PATCH /payroll/{payroll}/pay — mark as paid (lock)
    public function pay(Payroll $payroll)
    {
        $this->authorize('pay', $payroll);

        $this->payrollService->markPaid($payroll, auth()->user());

        return redirect()->route('payroll.show', $payroll)
            ->with('success', 'Payroll marked as paid and locked.');
    }

    // POST /payroll/{payroll}/regenerate — delete draft and recalculate
    public function regenerate(Payroll $payroll)
    {
        $employee = $payroll->employee;

        try {
            $new = $this->payrollService->regenerate($employee, $payroll->month, $payroll->year);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('payroll.show', $new)
            ->with('success', "Payroll regenerated for {$employee->full_name} ({$new->month_label}) using the correct salary.");
    }

    // POST /payroll/bulk-pay — pay all in a period
    public function bulkPay(Request $request)
    {
        $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year'  => ['required', 'integer', 'min:2020'],
        ]);

        $count = $this->payrollService->bulkPay(
            (int) $request->month,
            (int) $request->year,
            auth()->user()
        );

        return redirect()->back()->with('success', "{$count} payroll(s) marked as paid.");
    }

    private function isOvertimeEnabled(): bool
    {
        return in_array($this->settings->get('overtime_pay_enabled', false), [true, 'true', '1', 1], true);
    }
}
