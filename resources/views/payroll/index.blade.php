<x-app-layout>
    <x-slot name="title">Payroll</x-slot>

    <x-alert />

    {{-- Header + period picker --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Payroll</h5>
            <small class="text-muted">
                {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
            </small>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('payroll.index') }}" class="d-flex gap-2">
                <select name="month" class="form-select form-select-sm">
                    @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}
                    </option>
                    @endforeach
                </select>
                <select name="year" class="form-select form-select-sm">
                    @foreach (range(now()->year, now()->year - 3, -1) as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-primary">Go</button>
            </form>
            <a href="{{ route('payroll.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Generate
            </a>
        </div>
    </div>

    {{-- Period Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f4fd;"><i class="bi bi-people" style="color:#1a8fe3;"></i></div>
                <div><div class="label">Employees</div><div class="value">{{ $summary['total_employees'] }}</div></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eaf7ee;"><i class="bi bi-cash" style="color:#28a745;"></i></div>
                <div><div class="label">Gross Total</div><div class="value">{{ currency($summary['total_gross'], 0) }}</div></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fdecea;"><i class="bi bi-dash-circle" style="color:#dc3545;"></i></div>
                <div><div class="label">Deductions</div><div class="value">{{ currency($summary['total_deductions'], 0) }}</div></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0ecff;"><i class="bi bi-wallet2" style="color:#6f42c1;"></i></div>
                <div><div class="label">Net Payable</div><div class="value">{{ currency($summary['total_net'], 0) }}</div></div>
            </div>
        </div>
    </div>

    {{-- Bulk Pay --}}
    @if ($summary['draft_count'] > 0)
    <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4">
        <span>
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>{{ $summary['draft_count'] }}</strong> payroll(s) pending payment.
        </span>
        <form method="POST" action="{{ route('payroll.bulk-pay') }}"
              onsubmit="return confirm('Mark all unpaid payrolls as PAID? This cannot be undone.')">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year"  value="{{ $year }}">
            <button class="btn btn-sm btn-warning">
                <i class="bi bi-check2-all me-1"></i> Mark All Paid
            </button>
        </form>
    </div>
    @endif

    {{-- Payroll Table --}}
    <div class="hrms-card">
        <div class="card-header"><i class="bi bi-cash-stack me-2 text-primary"></i>Payroll Records</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Base Salary</th>
                        <th>Gross</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Attendance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $payroll)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $payroll->employee->full_name }}</div>
                            <small class="text-muted">{{ $payroll->employee->department }}</small>
                        </td>
                        <td>{{ currency($payroll->base_salary) }}</td>
                        <td class="text-success fw-semibold">{{ currency($payroll->gross_salary) }}</td>
                        <td class="text-danger">{{ currency(-$payroll->total_deductions) }}</td>
                        <td class="fw-bold">{{ currency($payroll->net_salary) }}</td>
                        <td>
                            <small>
                                <span class="text-success">{{ $payroll->present_days }}P</span> ·
                                <span class="text-danger">{{ $payroll->absent_days }}A</span> ·
                                <span class="text-warning">{{ $payroll->late_days }}L</span>
                            </small>
                        </td>
                        <td>
                            @if ($payroll->status === 'paid')
                                <span class="badge bg-success-subtle text-success">Paid</span>
                            @elseif ($payroll->status === 'processed')
                                <span class="badge bg-info-subtle text-info">Processed</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Draft</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('payroll.show', $payroll) }}"
                                   class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if (! $payroll->isLocked())
                                <a href="{{ route('payroll.edit', $payroll) }}"
                                   class="btn btn-sm btn-outline-primary" title="Adjust">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('payroll.pay', $payroll) }}"
                                      onsubmit="return confirm('Mark as PAID? This will lock the record.')">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-success" title="Mark Paid">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                            No payroll records for this period.
                            <a href="{{ route('payroll.create') }}">Generate now.</a>
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
