<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PayslipService
{
    public function __construct(private readonly NotificationService $notifications) {}

    // ── Generate and store one payslip PDF ───────────────────────

    public function generate(Payroll $payroll, User $generatedBy): Payslip
    {
        // Only generate payslips for paid (locked) payrolls
        if (! $payroll->isLocked()) {
            throw ValidationException::withMessages([
                'payroll' => 'Payslip can only be generated after payroll is marked as paid.',
            ]);
        }

        // Regenerate if already exists (overwrite old file)
        if ($payroll->payslip) {
            $this->deleteFile($payroll->payslip);
            $payroll->payslip->delete();
        }

        $payroll->load(['employee', 'paidBy', 'salarySnapshot']);

        $pdf      = $this->renderPdf($payroll);
        $path     = $this->buildPath($payroll);
        $fileName = $this->buildFileName($payroll);
        $content  = $pdf->output();

        Storage::disk('local')->put($path, $content);

        $payslip = Payslip::create([
            'payroll_id'   => $payroll->id,
            'employee_id'  => $payroll->employee_id,
            'month'        => $payroll->month,
            'year'         => $payroll->year,
            'file_path'    => $path,
            'file_name'    => $fileName,
            'file_size'    => strlen($content),
            'generated_by' => $generatedBy->id,
            'generated_at' => now(),
        ]);

        $this->notifications->payrollGenerated($payroll);

        return $payslip;
    }

    // ── Bulk generate for entire period ──────────────────────────

    public function generateBulk(int $month, int $year, User $generatedBy): array
    {
        $payrolls = Payroll::with(['employee', 'salarySnapshot'])
            ->forPeriod($month, $year)
            ->paid()
            ->get();

        $results = ['created' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($payrolls as $payroll) {
            try {
                $this->generate($payroll, $generatedBy);
                $results['created']++;
            } catch (\Throwable $e) {
                $results['errors'][] = ($payroll->employee->full_name ?? '?') . ': ' . $e->getMessage();
            }
        }

        return $results;
    }

    // ── Stream PDF to browser (preview / download) ────────────────

    public function stream(Payslip $payslip, bool $download = false): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($payslip->fileExists(), 404, 'Payslip file not found.');

        $content     = Storage::disk('local')->get($payslip->file_path);
        $disposition = $download ? 'attachment' : 'inline';

        return response()->streamDownload(
            fn () => print($content),
            $payslip->file_name,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "{$disposition}; filename=\"{$payslip->file_name}\"",
            ]
        );
    }

    // ── Queries ───────────────────────────────────────────────────

    public function paginateForEmployee(Employee $employee, int $perPage = 15): LengthAwarePaginator
    {
        return Payslip::forEmployee($employee->id)
            ->with('payroll')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate($perPage);
    }

    public function paginateByPeriod(int $month, int $year, int $perPage = 20): LengthAwarePaginator
    {
        return Payslip::with(['employee', 'generatedBy'])
            ->forPeriod($month, $year)
            ->latest()
            ->paginate($perPage);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function renderPdf(Payroll $payroll): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('payslips.pdf', ['payroll' => $payroll])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'sans-serif',
            ]);
    }

    private function buildPath(Payroll $payroll): string
    {
        return sprintf(
            'payslips/%d/%02d/%s.pdf',
            $payroll->year,
            $payroll->month,
            $payroll->employee->employee_code
        );
    }

    private function buildFileName(Payroll $payroll): string
    {
        $month = \Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->format('M');
        return sprintf(
            '%s_%s_%d.pdf',
            $payroll->employee->employee_code,
            $month,
            $payroll->year
        );
    }

    private function deleteFile(Payslip $payslip): void
    {
        if ($payslip->fileExists()) {
            Storage::disk('local')->delete($payslip->file_path);
        }
    }
}
