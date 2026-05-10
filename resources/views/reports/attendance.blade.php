<x-app-layout>
    <x-slot name="title">Attendance Report</x-slot>

    <div class="mb-4 d-flex align-items-center justify-content-between">
        <div>
            <h5 class="fw-bold mb-0">Attendance Report</h5>
            <small class="text-muted">
                {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }} &mdash; {{ $workingDays }} working days
            </small>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    {{-- Filters --}}
    <div class="hrms-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-2">
                <label class="form-label form-label-sm">Month</label>
                <select name="month" class="form-select form-select-sm">
                    @foreach(range(1,12) as $m)
                        <option value="{{ $m }}" @selected($m == $month)>
                            {{ \Carbon\Carbon::createFromDate(null,$m,1)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm">Year</label>
                <select name="year" class="form-select form-select-sm">
                    @foreach(range(now()->year - 2, now()->year) as $y)
                        <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label form-label-sm">Employee</label>
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected(($filters['employee_id'] ?? '') == $emp->id)>
                            {{ $emp->first_name }} {{ $emp->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm">Department</label>
                <select name="department" class="form-select form-select-sm">
                    <option value="">All Depts</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" @selected(($filters['department'] ?? '') == $dept)>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="present"    @selected(($filters['status'] ?? '') == 'present')>Present</option>
                    <option value="late"       @selected(($filters['status'] ?? '') == 'late')>Late</option>
                    <option value="absent"     @selected(($filters['status'] ?? '') == 'absent')>Absent</option>
                    <option value="underworked" @selected(($filters['status'] ?? '') == 'underworked')>Underworked</option>
                    <option value="holiday"    @selected(($filters['status'] ?? '') == 'holiday')>Holiday</option>
                    <option value="weekly_off" @selected(($filters['status'] ?? '') == 'weekly_off')>Weekly Off</option>
                    <option value="leave"      @selected(($filters['status'] ?? '') == 'leave')>Leave</option>
                </select>
            </div>
            <div class="col-sm-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">Go</button>
            </div>
        </form>
    </div>

    {{-- Totals row --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f4fd;">
                    <i class="bi bi-people" style="color:#1a8fe3;"></i>
                </div>
                <div>
                    <div class="label">Employees</div>
                    <div class="value">{{ $totals['total_employees'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eaf7ee;">
                    <i class="bi bi-check-circle" style="color:#28a745;"></i>
                </div>
                <div>
                    <div class="label">Total Present</div>
                    <div class="value">{{ $totals['total_present'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fdecea;">
                    <i class="bi bi-x-circle" style="color:#dc3545;"></i>
                </div>
                <div>
                    <div class="label">Total Absent</div>
                    <div class="value">{{ $totals['total_absent'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff3e0;">
                    <i class="bi bi-person-dash" style="color:#fd7e14;"></i>
                </div>
                <div>
                    <div class="label">On Leave</div>
                    <div class="value">{{ $totals['total_leave'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e1;">
                    <i class="bi bi-sun" style="color:#ffc107;"></i>
                </div>
                <div>
                    <div class="label">Holidays</div>
                    <div class="value">{{ $totals['total_holiday'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff;">
                    <i class="bi bi-calendar2-week" style="color:#6c757d;"></i>
                </div>
                <div>
                    <div class="label">Weekly Off</div>
                    <div class="value">{{ $totals['total_weekly_off'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Per-employee summary --}}
    <div class="hrms-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Employee Summary</span>
            <span class="text-muted" style="font-size:.8rem;font-weight:400;">
                Avg Attendance Rate: <strong>{{ number_format($totals['avg_attendance'], 1) }}%</strong>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Dept</th>
                        <th class="text-center" title="Expected working days (excl. holidays & weekly offs)">Work Days</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Late</th>
                        <th class="text-center">Underworked</th>
                        <th class="text-center">Absent</th>
                        <th class="text-center">Holiday</th>
                        <th class="text-center">Weekly Off</th>
                        <th class="text-center">Leave</th>
                        <th class="text-center">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $row)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $row['employee']->full_name }}</div>
                            <div class="text-muted" style="font-size:.76rem;">{{ $row['employee']->employee_code }}</div>
                        </td>
                        <td class="text-muted" style="font-size:.82rem;">{{ $row['employee']->department ?? '—' }}</td>
                        <td class="text-center">{{ $row['working_days'] }}</td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success">{{ $row['present_days'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning-subtle text-warning">{{ $row['late_days'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info-subtle text-info">{{ $row['underworked_days'] }}</span>
                        </td>
                        <td class="text-center">
                            @if($row['absent_days'] > 0)
                                <span class="badge bg-danger-subtle text-danger">{{ $row['absent_days'] }}</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($row['holiday_days'] > 0)
                                <span class="badge bg-warning-subtle text-dark">{{ $row['holiday_days'] }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($row['weekly_off_days'] > 0)
                                <span class="badge bg-secondary-subtle text-secondary">{{ $row['weekly_off_days'] }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($row['leave_days'] > 0)
                                <span class="badge bg-primary-subtle text-primary">{{ $row['leave_days'] }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php $rate = $row['attendance_rate']; @endphp
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress flex-grow-1" style="height:5px;min-width:40px;">
                                    <div class="progress-bar {{ $rate >= 90 ? 'bg-success' : ($rate >= 70 ? 'bg-warning' : 'bg-danger') }}"
                                         style="width:{{ $rate }}%"></div>
                                </div>
                                <span style="font-size:.76rem;min-width:34px;">{{ $rate }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center py-4 text-muted">No data for selected period.</td></tr>
                    @endforelse
                </tbody>
                @if($summary->count())
                <tfoot class="table-light fw-semibold" style="font-size:.85rem;">
                    <tr>
                        <td colspan="2">Totals</td>
                        <td class="text-center">—</td>
                        <td class="text-center">{{ $totals['total_present'] }}</td>
                        <td class="text-center">{{ $totals['total_late'] }}</td>
                        <td class="text-center">{{ $totals['total_underworked'] }}</td>
                        <td class="text-center">{{ $totals['total_absent'] }}</td>
                        <td class="text-center">{{ $totals['total_holiday'] }}</td>
                        <td class="text-center">{{ $totals['total_weekly_off'] }}</td>
                        <td class="text-center">{{ $totals['total_leave'] }}</td>
                        <td class="text-center">{{ number_format($totals['avg_attendance'], 1) }}%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Daily records --}}
    @if($records->count())
    <div class="hrms-card">
        <div class="card-header">
            Daily Records
            <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $records->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem;">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $rec)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($rec->date)->format('d M Y') }}</td>
                        <td>{{ $rec->employee->full_name }}</td>
                        <td>{{ $rec->check_in  ? \Carbon\Carbon::parse($rec->check_in)->format('H:i')  : '—' }}</td>
                        <td>{{ $rec->check_out ? \Carbon\Carbon::parse($rec->check_out)->format('H:i') : '—' }}</td>
                        <td>{{ $rec->working_hours ?? '—' }}</td>
                        <td>
                            @php
                                $statusMap = [
                                    'present'    => ['bg-success-subtle text-success',   'Present'],
                                    'late'       => ['bg-warning-subtle text-warning',   'Late'],
                                    'underworked'=> ['bg-info-subtle text-info',         'Underworked'],
                                    'absent'     => ['bg-danger-subtle text-danger',     'Absent'],
                                    'holiday'    => ['bg-warning-subtle text-dark',      'Holiday'],
                                    'weekly_off' => ['bg-secondary-subtle text-secondary','Weekly Off'],
                                    'leave'      => ['bg-primary-subtle text-primary',   'Leave'],
                                ];
                                [$cls, $label] = $statusMap[$rec->status] ?? ['bg-secondary-subtle text-secondary', ucfirst($rec->status)];
                            @endphp
                            <span class="badge {{ $cls }}">{{ $label }}</span>
                            @if(($rec->source ?? null) === 'time_doctor')
                                <span class="badge bg-primary-subtle text-primary ms-1">Time Doctor</span>
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:.82rem;">{{ $rec->note ?? $rec->notes ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</x-app-layout>
