<x-app-layout>
    <x-slot name="title">CRM Dashboard</x-slot>

    @php
        $statusClass = fn (?string $status) => match ($status) {
            'present' => 'success',
            'late' => 'warning',
            'underworked' => 'info',
            'holiday' => 'primary',
            'weekly_off' => 'secondary',
            default => 'danger',
        };
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">CRM Dashboard</h5>
            <small class="text-muted">{{ $employee->full_name }} — {{ $employee->employee_code }}</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dashboard.time-doctor') }}" class="btn btn-outline-primary">
                <i class="bi bi-speedometer2 me-1"></i> Time Doctor
            </a>
            <a href="{{ route('employees.me') }}" class="btn btn-primary">
                <i class="bi bi-person-vcard me-1"></i> My Profile
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-briefcase"></i></div>
                <div>
                    <div class="label">Designation</div>
                    <div class="value" style="font-size:1rem;">{{ $employee->designation ?? '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="label">Today</div>
                    <div class="value" style="font-size:1rem;">
                        {{ $attendanceToday ? ucfirst(str_replace('_', ' ', $attendanceToday->status)) : 'Not marked' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="label">Approved Leaves</div>
                    <div class="value">{{ $leaveStats['approved'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="label">Latest Net Salary</div>
                    <div class="value" style="font-size:1.15rem;">{{ currency($latestPayroll?->net_salary ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="hrms-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-2 text-primary"></i>Recent Attendance</span>
                    <a href="{{ route('attendance.my') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAttendances as $record)
                            <tr>
                                <td class="fw-semibold">{{ $record->date->format('M j, Y') }}</td>
                                <td>{{ $record->check_in ? substr($record->check_in, 0, 5) : '—' }}</td>
                                <td>{{ $record->check_out ? substr($record->check_out, 0, 5) : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $statusClass($record->status) }}-subtle text-{{ $statusClass($record->status) }}">
                                        {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No attendance records yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="hrms-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-check me-2 text-success"></i>Leave Requests</span>
                    <a href="{{ route('leaves.index') }}" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Period</th>
                                <th>Days</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLeaves as $leave)
                            <tr>
                                <td class="fw-semibold">{{ $leave->leaveType?->name ?? 'Leave' }}</td>
                                <td>{{ $leave->start_date->format('M j') }} - {{ $leave->end_date->format('M j, Y') }}</td>
                                <td>{{ $leave->total_days }}</td>
                                <td>
                                    <span class="badge bg-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'pending' ? 'warning' : 'danger') }}-subtle text-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'pending' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($leave->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No leave requests yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">Employment</h6>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Department</span>
                    <span class="fw-semibold">{{ $employee->department ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Branch</span>
                    <span class="fw-semibold">{{ $employee->branch?->name ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Shift</span>
                    <span class="fw-semibold">{{ $employee->shift?->name ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Join Date</span>
                    <span class="fw-semibold">{{ $employee->join_date?->format('M j, Y') ?? '—' }}</span>
                </div>
            </div>

            <div class="hrms-card p-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">Salary & Payslip</h6>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Base Salary</span>
                    <span class="fw-semibold text-success">{{ currency($employee->base_salary) }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Last Period</span>
                    <span class="fw-semibold">{{ $latestPayroll?->month_label ?? '—' }}</span>
                </div>
                <div class="d-grid gap-2 mt-3">
                    <a href="{{ route('payroll.my') }}" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-cash-stack me-1"></i> Salary History
                    </a>
                    <a href="{{ route('payslips.my') }}" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-receipt me-1"></i> Payslips
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
