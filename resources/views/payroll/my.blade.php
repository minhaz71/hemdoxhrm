<x-app-layout>
    <x-slot name="title">My Salary</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">My Salary</h5>
            <small class="text-muted">{{ $employee->full_name }} — {{ $employee->employee_code }}</small>
        </div>
        <a href="{{ route('payslips.my') }}" class="btn btn-outline-primary">
            <i class="bi bi-receipt me-1"></i> Payslips
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-cash"></i></div>
                <div>
                    <div class="label">Current Base Salary</div>
                    <div class="value">{{ currency($summary['current_base_salary']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="label">Latest Net Salary</div>
                    <div class="value">{{ currency($summary['latest_payroll']?->net_salary ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <div class="label">Total Paid</div>
                    <div class="value">{{ currency($summary['paid_total']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="hrms-card">
        <div class="card-header">
            <i class="bi bi-cash-stack me-2 text-primary"></i>Salary History
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Period</th>
                        <th>Base</th>
                        <th>Gross</th>
                        @if($overtimeEnabled)
                        <th>Overtime</th>
                        @endif
                        <th>Deductions</th>
                        <th>Net</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $payroll)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $payroll->month_label }}</div>
                            <small class="text-muted">{{ $payroll->year }}</small>
                        </td>
                        <td>{{ currency($payroll->base_salary) }}</td>
                        <td class="text-success fw-semibold">{{ currency($payroll->gross_salary) }}</td>
                        @if($overtimeEnabled)
                        <td>{{ currency($payroll->overtime_amount) }}</td>
                        @endif
                        <td class="text-danger">{{ currency(-$payroll->total_deductions) }}</td>
                        <td class="fw-bold text-primary">{{ currency($payroll->net_salary) }}</td>
                        <td>
                            @if($payroll->status === 'paid')
                                <span class="badge bg-success-subtle text-success">Paid</span>
                            @elseif($payroll->status === 'processed')
                                <span class="badge bg-info-subtle text-info">Processed</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Draft</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('payroll.show', $payroll) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye me-1"></i>Details
                                </a>
                                @if($payroll->payslip)
                                <a href="{{ route('payslips.download', $payroll->payslip) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-download me-1"></i>Payslip
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $overtimeEnabled ? 8 : 7 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                            No salary records available yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
        <div class="p-3 border-top">{{ $records->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</x-app-layout>
