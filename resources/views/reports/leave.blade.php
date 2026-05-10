<x-app-layout>
    <x-slot name="title">Leave Report</x-slot>

    <div class="mb-4 d-flex align-items-center justify-content-between">
        <div>
            <h5 class="fw-bold mb-0">Leave Report</h5>
            <small class="text-muted">
                {{ $month ? \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') : $year }}
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
                <label class="form-label form-label-sm">Year</label>
                <select name="year" class="form-select form-select-sm">
                    @foreach(range(now()->year - 2, now()->year) as $y)
                        <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm">Month</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="">All Months</option>
                    @foreach(range(1,12) as $m)
                        <option value="{{ $m }}" @selected($m == $month)>
                            {{ \Carbon\Carbon::createFromDate(null,$m,1)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm">Leave Type</label>
                <select name="leave_type_id" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" @selected(($filters['leave_type_id'] ?? '') == $lt->id)>
                            {{ $lt->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending"  @selected(($filters['status'] ?? '') == 'pending')>Pending</option>
                    <option value="approved" @selected(($filters['status'] ?? '') == 'approved')>Approved</option>
                    <option value="rejected" @selected(($filters['status'] ?? '') == 'rejected')>Rejected</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm">Employee</label>
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected(($filters['employee_id'] ?? '') == $emp->id)>
                            {{ $emp->first_name }} {{ $emp->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">Go</button>
            </div>
        </form>
    </div>

    {{-- Totals --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e6;">
                    <i class="bi bi-calendar-check" style="color:#ffc107;"></i>
                </div>
                <div>
                    <div class="label">Applications</div>
                    <div class="value">{{ $totals['total_applications'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eaf7ee;">
                    <i class="bi bi-check-circle" style="color:#28a745;"></i>
                </div>
                <div>
                    <div class="label">Approved</div>
                    <div class="value text-success">{{ $totals['approved'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff3cd;">
                    <i class="bi bi-hourglass-split" style="color:#ffc107;"></i>
                </div>
                <div>
                    <div class="label">Pending</div>
                    <div class="value text-warning">{{ $totals['pending'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fdecea;">
                    <i class="bi bi-x-circle" style="color:#dc3545;"></i>
                </div>
                <div>
                    <div class="label">Rejected</div>
                    <div class="value text-danger">{{ $totals['rejected'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- By-type breakdown --}}
        @if($byType->count())
        <div class="col-md-4">
            <div class="hrms-card h-100">
                <div class="card-header">By Leave Type</div>
                <div class="p-3">
                    @foreach($byType as $bt)
                    <div class="mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold" style="font-size:.875rem;">{{ $bt['type'] ?: 'Unknown' }}</span>
                            <span class="text-muted" style="font-size:.8rem;">{{ $bt['total_days'] }} days</span>
                        </div>
                        <div class="d-flex gap-2" style="font-size:.78rem;">
                            <span class="badge bg-success-subtle text-success">✓ {{ $bt['approved'] }}</span>
                            <span class="badge bg-warning-subtle text-warning">⏳ {{ $bt['pending'] }}</span>
                            <span class="badge bg-danger-subtle text-danger">✕ {{ $bt['rejected'] }}</span>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $bt['count'] }} total</span>
                        </div>
                    </div>
                    @endforeach

                    <div class="pt-1 d-flex justify-content-between" style="font-size:.85rem;">
                        <span class="text-muted">Total Days Taken</span>
                        <strong>{{ $totals['total_days'] }}</strong>
                    </div>
                    @if($totals['unpaid_overrides'])
                    <div class="d-flex justify-content-between mt-1" style="font-size:.85rem;">
                        <span class="text-muted">Unpaid (limit exceeded)</span>
                        <strong class="text-warning">{{ $totals['unpaid_overrides'] }}</strong>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Leave applications table --}}
        <div class="{{ $byType->count() ? 'col-md-8' : 'col-12' }}">
            <div class="hrms-card">
                <div class="card-header">Leave Applications</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:.875rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th class="text-center">Days</th>
                                <th>Status</th>
                                <th>Approved By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $leave)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $leave->employee->full_name }}</div>
                                    <div class="text-muted" style="font-size:.78rem;">{{ $leave->employee->employee_code }}</div>
                                </td>
                                <td>
                                    {{ $leave->leaveType->name ?? '—' }}
                                    @if($leave->is_unpaid_override)
                                        <span class="badge bg-warning-subtle text-warning ms-1" style="font-size:.7rem;">Unpaid</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}</td>
                                <td class="text-center">{{ $leave->total_days }}</td>
                                <td>
                                    @if($leave->status === 'approved')
                                        <span class="badge bg-success-subtle text-success">Approved</span>
                                    @elseif($leave->status === 'pending')
                                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Rejected</span>
                                    @endif
                                </td>
                                <td class="text-muted">
                                    {{ $leave->approvedBy ? $leave->approvedBy->name : '—' }}
                                    @if($leave->approved_at)
                                        <div style="font-size:.72rem;">
                                            {{ \Carbon\Carbon::parse($leave->approved_at)->format('d M Y') }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No leave applications found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
