<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Services\PayslipService;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function __construct(private readonly PayslipService $payslipService) {}

    // GET /payslips — list by period (admin/hr view)
    public function index(Request $request)
    {
        $month   = (int) $request->get('month', now()->month);
        $year    = (int) $request->get('year',  now()->year);
        $records = $this->payslipService->paginateByPeriod($month, $year);

        return view('payslips.index', compact('records', 'month', 'year'));
    }

    // POST /payslips/generate — generate single payslip from payroll
    public function generate(Request $request)
    {
        $request->validate([
            'payroll_id' => ['required', 'integer', 'exists:payrolls,id'],
        ]);

        $payroll  = Payroll::with('employee')->findOrFail($request->payroll_id);
        $payslip  = $this->payslipService->generate($payroll, auth()->user());

        return redirect()->back()
            ->with('success', "Payslip generated for {$payroll->employee->full_name}.");
    }

    // POST /payslips/bulk-generate — generate for entire paid period
    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year'  => ['required', 'integer', 'min:2020'],
        ]);

        $result = $this->payslipService->generateBulk(
            (int) $request->month,
            (int) $request->year,
            auth()->user()
        );

        $msg = "{$result['created']} payslip(s) generated.";
        if (! empty($result['errors'])) {
            $msg .= ' Errors: ' . implode('; ', $result['errors']);
        }

        return redirect()->back()->with('success', $msg);
    }

    // GET /payslips/{payslip}/view — stream inline in browser
    public function view(Payslip $payslip)
    {
        $this->authorize('view', $payslip);

        return $this->payslipService->stream($payslip, download: false);
    }

    // GET /payslips/{payslip}/download — force download
    public function download(Payslip $payslip)
    {
        $this->authorize('view', $payslip);

        return $this->payslipService->stream($payslip, download: true);
    }

    // GET /payslips/my — employee's own payslip history
    public function my(Request $request)
    {
        $employee = auth()->user()->employee;

        abort_unless($employee, 404, 'No employee record linked to your account.');

        $records = $this->payslipService->paginateForEmployee($employee);

        return view('payslips.my', compact('records', 'employee'));
    }
}
