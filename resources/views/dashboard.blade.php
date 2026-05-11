<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    @php
        $statusClass = fn (?string $status) => match ($status) {
            'present' => 'success',
            'late' => 'warning',
            'underworked' => 'info',
            'holiday' => 'primary',
            'weekly_off' => 'secondary',
            'leave' => 'info',
            default => 'danger',
        };
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h5 class="fw-bold mb-0">Admin Dashboard</h5>
            <small class="text-muted">{{ $periodLabel }}</small>
        </div>
        <form method="GET" action="{{ route('dashboard') }}" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label small mb-1">Period</label>
                <select name="period_type" class="form-select form-select-sm" style="width:120px">
                    <option value="month" {{ $filters['period_type'] === 'month' ? 'selected' : '' }}>Month</option>
                    <option value="year" {{ $filters['period_type'] === 'year' ? 'selected' : '' }}>Year</option>
                    <option value="custom" {{ $filters['period_type'] === 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
            </div>
            <div>
                <label class="form-label small mb-1">Month</label>
                <select name="month" class="form-select form-select-sm" style="width:130px">
                    @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ (int) $filters['month'] === $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromDate($filters['year'], $m, 1)->format('F') }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label small mb-1">Year</label>
                <input type="number" name="year" value="{{ $filters['year'] }}" min="2020" max="{{ now()->year + 1 }}" class="form-control form-control-sm" style="width:95px">
            </div>
            <div>
                <label class="form-label small mb-1">From</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="form-label small mb-1">To</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="form-control form-control-sm">
            </div>
            <button class="btn btn-sm btn-primary">
                <i class="bi bi-funnel me-1"></i> Apply
            </button>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f4fd;"><i class="bi bi-people" style="color:#1a8fe3;"></i></div>
                <div><div class="label">Active Employees</div><div class="value">{{ $stats['total_employees'] }}</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eaf7ee;"><i class="bi bi-clock-history" style="color:#28a745;"></i></div>
                <div><div class="label">Present In Period</div><div class="value">{{ $stats['present'] }}</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e6;"><i class="bi bi-calendar-x" style="color:#ffc107;"></i></div>
                <div><div class="label">Leaves In Period</div><div class="value">{{ $stats['on_leave'] }}</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fdecea;"><i class="bi bi-person-x" style="color:#dc3545;"></i></div>
                <div><div class="label">Absent In Period</div><div class="value">{{ $stats['absent'] }}</div></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0ecff;"><i class="bi bi-cash-stack" style="color:#6f42c1;"></i></div>
                <div><div class="label">Payroll Net</div><div class="value">{{ currency($stats['payroll_total'], 0) }}</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e9f9f5;"><i class="bi bi-receipt" style="color:#20c997;"></i></div>
                <div><div class="label">Payslips Issued</div><div class="value">{{ $stats['payslips_issued'] }}</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff3e8;"><i class="bi bi-hourglass-split" style="color:#fd7e14;"></i></div>
                <div><div class="label">Pending Leaves</div><div class="value">{{ $stats['pending_leaves'] }}</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f4fd;"><i class="bi bi-person-check" style="color:#1a8fe3;"></i></div>
                <div><div class="label">New Employees</div><div class="value">{{ $stats['new_employees'] }}</div></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="hrms-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-2 text-primary"></i>Recent Attendance</span>
                    <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
                        <thead class="table-light">
                            <tr><th>Employee</th><th>Date</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentAttendances as $record)
                            <tr>
                                <td class="fw-semibold">{{ $record->employee?->full_name ?? '—' }}</td>
                                <td>{{ $record->date->format('M j, Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $statusClass($record->status) }}-subtle text-{{ $statusClass($record->status) }}">
                                        {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">No attendance records for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="hrms-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-check me-2 text-success"></i>Pending Leave Requests</span>
                    <a href="{{ route('leaves.index') }}" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
                        <thead class="table-light">
                            <tr><th>Employee</th><th>Type</th><th>Period</th></tr>
                        </thead>
                        <tbody>
                            @forelse($pendingLeaves as $leave)
                            <tr>
                                <td class="fw-semibold">{{ $leave->employee?->full_name ?? '—' }}</td>
                                <td>{{ $leave->leaveType?->name ?? 'Leave' }}</td>
                                <td>{{ $leave->start_date->format('M j') }} - {{ $leave->end_date->format('M j, Y') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">No pending leave requests for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="hrms-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people me-2 text-info"></i>New Employees</span>
                    <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-info">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Code</th><th>Department</th><th>Designation</th><th>Join Date</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentEmployees as $employee)
                            <tr>
                                <td class="fw-semibold">{{ $employee->full_name }}</td>
                                <td>{{ $employee->employee_code }}</td>
                                <td>{{ $employee->department ?: '—' }}</td>
                                <td>{{ $employee->designationModel?->name ?? $employee->designation ?? '—' }}</td>
                                <td>{{ $employee->join_date?->format('M j, Y') ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No new employees for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
