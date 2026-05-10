<x-app-layout>
    <x-slot name="title">My Payslips</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">My Payslips</h5>
            <small class="text-muted">{{ $employee->full_name }} — {{ $employee->employee_code }}</small>
        </div>
    </div>

    <div class="hrms-card">
        <div class="card-header"><i class="bi bi-receipt me-2 text-primary"></i>Pay History</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Period</th>
                        <th>Net Salary</th>
                        <th>Generated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $payslip)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $payslip->month_label }}</div>
                            <small class="badge bg-danger-subtle text-danger">
                                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                            </small>
                        </td>
                        <td class="fw-bold text-success">
                            {{ currency($payslip->payroll->net_salary ?? 0) }}
                        </td>
                        <td class="text-muted">
                            {{ $payslip->generated_at->format('M j, Y') }}
                        </td>
                        <td>
                            <div class="d-flex gap-2 justify-content-end">
                                @if($payslip->payroll)
                                <a href="{{ route('payroll.show', $payslip->payroll) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-cash-stack me-1"></i>Salary
                                </a>
                                @endif
                                <a href="{{ route('payslips.view', $payslip) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                                <a href="{{ route('payslips.download', $payslip) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-download me-1"></i>Download
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-pdf fs-2 d-block mb-2 opacity-50"></i>
                            No payslips available yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
        <div class="p-3 border-top">{{ $records->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</x-app-layout>
