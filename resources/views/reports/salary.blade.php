<x-app-layout>
    <x-slot name="title">Salary Report</x-slot>

    <div class="mb-4 d-flex align-items-center justify-content-between">
        <div>
            <h5 class="fw-bold mb-0">Salary Report</h5>
            <small class="text-muted">
                {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
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
                    <option value="draft"     @selected(($filters['status'] ?? '') == 'draft')>Draft</option>
                    <option value="processed" @selected(($filters['status'] ?? '') == 'processed')>Processed</option>
                    <option value="paid"      @selected(($filters['status'] ?? '') == 'paid')>Paid</option>
                </select>
            </div>
            <div class="col-sm-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">Go</button>
            </div>
        </form>
    </div>

    {{-- Salary totals --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f4fd;">
                    <i class="bi bi-people" style="color:#1a8fe3;"></i>
                </div>
                <div>
                    <div class="label">Records</div>
                    <div class="value">{{ $records->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eaf7ee;">
                    <i class="bi bi-cash-stack" style="color:#28a745;"></i>
                </div>
                <div>
                    <div class="label">Total Gross</div>
                    <div class="value" style="font-size:1.2rem;">{{ currency($totals['total_gross'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fdecea;">
                    <i class="bi bi-dash-circle" style="color:#dc3545;"></i>
                </div>
                <div>
                    <div class="label">Total Deductions</div>
                    <div class="value" style="font-size:1.2rem;">{{ currency($totals['total_deductions'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f3e8ff;">
                    <i class="bi bi-wallet2" style="color:#6f42c1;"></i>
                </div>
                <div>
                    <div class="label">Total Net</div>
                    <div class="value" style="font-size:1.2rem;color:#6f42c1;">{{ currency($totals['total_net'], 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Attendance overview for this period --}}
    @if($records->count())
    <div class="hrms-card p-3 mb-4">
        <div class="d-flex flex-wrap gap-3 align-items-center" style="font-size:.85rem;">
            <span class="text-muted fw-semibold">Period Attendance:</span>
            <span><i class="bi bi-check-circle text-success me-1"></i>Present <strong>{{ $totals['total_present'] }}</strong></span>
            <span><i class="bi bi-x-circle text-danger me-1"></i>Absent <strong>{{ $totals['total_absent'] }}</strong></span>
            <span><i class="bi bi-person-dash text-warning me-1"></i>Leave <strong>{{ $totals['total_leave'] }}</strong></span>
            <span><i class="bi bi-sun text-warning me-1"></i>Holiday <strong>{{ $totals['total_holiday'] }}</strong> <small class="text-muted">(no deduction)</small></span>
            <span><i class="bi bi-calendar2-week text-secondary me-1"></i>Weekly Off <strong>{{ $totals['total_weekly_off'] }}</strong> <small class="text-muted">(no deduction)</small></span>
            <span><i class="bi bi-slash-circle text-info me-1"></i>Unpaid Leave <strong>{{ $totals['total_unpaid_leave'] }}</strong></span>
        </div>
    </div>
    @endif

    @if($totals['total_bonus'] + $totals['total_incentive'] + ($overtimeEnabled ? $totals['total_overtime'] : 0) > 0)
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="hrms-card p-3 d-flex justify-content-between align-items-center">
                <span class="text-muted" style="font-size:.85rem;">Total Bonus</span>
                <strong>{{ currency($totals['total_bonus']) }}</strong>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="hrms-card p-3 d-flex justify-content-between align-items-center">
                <span class="text-muted" style="font-size:.85rem;">Total Incentive</span>
                <strong>{{ currency($totals['total_incentive']) }}</strong>
            </div>
        </div>
        @if($overtimeEnabled)
        <div class="col-sm-4">
            <div class="hrms-card p-3 d-flex justify-content-between align-items-center">
                <span class="text-muted" style="font-size:.85rem;">Total Overtime</span>
                <strong>{{ currency($totals['total_overtime']) }}</strong>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Payroll table with attendance breakdown --}}
    <div class="hrms-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Payroll Records</span>
            <div class="d-flex gap-2" style="font-size:.8rem;">
                <span class="badge bg-success-subtle text-success">{{ $totals['paid_count'] }} Paid</span>
                <span class="badge bg-secondary-subtle text-secondary">{{ $totals['draft_count'] }} Pending</span>
                @if($totals['avg_net'])
                    <span class="text-muted">Avg Net: <strong>{{ currency($totals['avg_net'], 0) }}</strong></span>
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.82rem;">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="align-middle">Employee</th>
                        <th rowspan="2" class="align-middle">Dept</th>
                        <th rowspan="2" class="text-end align-middle">Base</th>
                        <th rowspan="2" class="text-end align-middle">Gross</th>
                        <th rowspan="2" class="text-end align-middle text-danger">Deductions</th>
                        <th rowspan="2" class="text-end align-middle fw-bold">Net</th>
                        {{-- Attendance breakdown header --}}
                        <th colspan="7" class="text-center bg-light border-start" style="font-size:.75rem;color:#6c757d;">Attendance (days)</th>
                        <th rowspan="2" class="text-center align-middle">Status</th>
                    </tr>
                    <tr class="bg-light" style="font-size:.75rem;color:#6c757d;">
                        <th class="text-center border-start">Work</th>
                        <th class="text-center text-success">Present</th>
                        <th class="text-center text-danger">Absent</th>
                        <th class="text-center" style="color:#fd7e14;">Leave</th>
                        <th class="text-center text-warning">Unpaid</th>
                        <th class="text-center" style="color:#adb5bd;">Holiday</th>
                        <th class="text-center" style="color:#6c757d;">W.Off</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $rec->employee->full_name }}</div>
                            <div class="text-muted" style="font-size:.75rem;">{{ $rec->employee->employee_code }}</div>
                        </td>
                        <td class="text-muted" style="font-size:.8rem;">{{ $rec->employee->department ?? '—' }}</td>
                        <td class="text-end">{{ currency($rec->base_salary) }}</td>
                        <td class="text-end fw-semibold">{{ currency($rec->gross_salary) }}</td>
                        <td class="text-end text-danger">
                            @if($rec->total_deductions > 0)({{ currency($rec->total_deductions) }})@else —@endif
                        </td>
                        <td class="text-end fw-bold text-success">{{ currency($rec->net_salary) }}</td>
                        {{-- Attendance breakdown --}}
                        <td class="text-center border-start">{{ $rec->working_days }}</td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success">{{ $rec->present_days }}</span>
                        </td>
                        <td class="text-center">
                            @if($rec->absent_days > 0)
                                <span class="badge bg-danger-subtle text-danger">{{ $rec->absent_days }}</span>
                            @else <span class="text-muted">0</span> @endif
                        </td>
                        <td class="text-center">
                            @php $totalLeave = ($rec->leave_days ?? 0) ?: ($rec->paid_leave_days ?? 0) + ($rec->unpaid_leave_days ?? 0); @endphp
                            @if($totalLeave > 0)
                                <span class="badge bg-primary-subtle text-primary">{{ $totalLeave }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td class="text-center">
                            @if($rec->unpaid_leave_days > 0)
                                <span class="badge bg-warning-subtle text-warning">{{ $rec->unpaid_leave_days }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td class="text-center">
                            @if(($rec->holiday_days ?? 0) > 0)
                                <span class="badge bg-warning-subtle text-dark">{{ $rec->holiday_days }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td class="text-center">
                            @if(($rec->weekly_off_days ?? 0) > 0)
                                <span class="badge bg-secondary-subtle text-secondary">{{ $rec->weekly_off_days }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td class="text-center">
                            @if($rec->status === 'paid')
                                <span class="badge bg-success-subtle text-success">Paid</span>
                            @elseif($rec->status === 'processed')
                                <span class="badge bg-primary-subtle text-primary">Processed</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="14" class="text-center py-4 text-muted">No payroll records for selected period.</td></tr>
                    @endforelse
                </tbody>
                @if($records->count())
                <tfoot class="table-light fw-semibold" style="font-size:.82rem;">
                    <tr>
                        <td colspan="3" class="text-end">Totals</td>
                        <td class="text-end">{{ currency($totals['total_gross']) }}</td>
                        <td class="text-end text-danger">({{ currency($totals['total_deductions']) }})</td>
                        <td class="text-end text-success">{{ currency($totals['total_net']) }}</td>
                        <td class="text-center border-start">—</td>
                        <td class="text-center">{{ $totals['total_present'] }}</td>
                        <td class="text-center">{{ $totals['total_absent'] }}</td>
                        <td class="text-center">{{ $totals['total_leave'] }}</td>
                        <td class="text-center">{{ $totals['total_unpaid_leave'] }}</td>
                        <td class="text-center">{{ $totals['total_holiday'] }}</td>
                        <td class="text-center">{{ $totals['total_weekly_off'] }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- Deduction logic key --}}
        <div class="p-3 border-top bg-light" style="font-size:.78rem;color:#6c757d;">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Deduction rules:</strong>
            Absent days → full-day deduction.
            Unpaid leave → management-configured leave penalty.
            Paid leave, holidays, weekly offs → <em>no deduction</em>.
        </div>
    </div>
</x-app-layout>
