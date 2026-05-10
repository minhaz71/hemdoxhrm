<x-app-layout>
    <x-slot name="title">Attendance</x-slot>

    <x-alert />

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Attendance Board</h5>
            <small class="text-muted">{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="{{ route('attendance.index') }}" class="d-flex gap-2">
                <input type="date" name="date" class="form-control form-control-sm"
                       value="{{ $date }}" max="{{ today()->toDateString() }}">
                <button class="btn btn-sm btn-outline-primary">Filter</button>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f4fd;">
                    <i class="bi bi-people" style="color:#1a8fe3;"></i>
                </div>
                <div>
                    <div class="label">Total</div>
                    <div class="value">{{ $summary['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eaf7ee;">
                    <i class="bi bi-check-circle" style="color:#28a745;"></i>
                </div>
                <div>
                    <div class="label">Present</div>
                    <div class="value">{{ $summary['present'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e6;">
                    <i class="bi bi-clock" style="color:#ffc107;"></i>
                </div>
                <div>
                    <div class="label">Late</div>
                    <div class="value">{{ $summary['late'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f4fd;">
                    <i class="bi bi-hourglass-split" style="color:#1a8fe3;"></i>
                </div>
                <div>
                    <div class="label">Underworked</div>
                    <div class="value">{{ $summary['underworked'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff;">
                    <i class="bi bi-calendar2-week" style="color:#6c757d;"></i>
                </div>
                <div>
                    <div class="label">Weekly Off</div>
                    <div class="value">{{ $summary['onWeeklyOff'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff3e0;">
                    <i class="bi bi-person-dash" style="color:#fd7e14;"></i>
                </div>
                <div>
                    <div class="label">On Leave</div>
                    <div class="value">{{ $summary['onLeave'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fdecea;">
                    <i class="bi bi-x-circle" style="color:#dc3545;"></i>
                </div>
                <div>
                    <div class="label">Absent</div>
                    <div class="value">{{ $summary['absent'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- AJAX Mark Panel (today only) --}}
        @if ($date === today()->toDateString())
        <div class="col-lg-4">
            <div class="hrms-card h-100">
                <div class="card-header">
                    <i class="bi bi-person-check me-2 text-primary"></i>Mark Attendance
                    <span class="badge bg-primary-subtle text-primary ms-1" id="live-clock"></span>
                </div>
                <div class="p-3">
                    <div class="mb-3">
                        <input type="text" id="employee-search" class="form-control"
                               placeholder="Search employee...">
                    </div>

                    <div id="employee-list" style="max-height:420px;overflow-y:auto;">
                        @foreach ($employees as $emp)
                        @php $marked = $emp->attendances->isNotEmpty(); @endphp
                        <div class="employee-row d-flex align-items-center justify-content-between p-2 rounded mb-1 border"
                             data-name="{{ strtolower($emp->full_name) }}"
                             id="emp-row-{{ $emp->id }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-sm-att">{{ strtoupper(substr($emp->first_name,0,1)) }}</div>
                                <div>
                                    <div class="fw-semibold" style="font-size:.85rem;">{{ $emp->full_name }}</div>
                                    <small class="text-muted">{{ $emp->designation }}</small>
                                </div>
                            </div>
                            <div id="emp-action-{{ $emp->id }}">
                                @if ($marked)
                                    @php $att = $emp->attendances->first(); @endphp
                                    <span class="badge {{ $att->status === 'late' ? 'bg-warning text-dark' : 'bg-success' }}"
                                          id="emp-badge-{{ $emp->id }}">
                                        {{ ucfirst($att->status) }} {{ $att->check_in }}
                                    </span>
                                    @if (! $att->check_out)
                                    <button class="btn btn-xs btn-outline-secondary ms-1"
                                            onclick="checkOut({{ $att->id }}, {{ $emp->id }})">
                                        Out
                                    </button>
                                    @else
                                    <small class="text-muted ms-1">Out {{ $att->check_out }}</small>
                                    @endif
                                @else
                                    <button class="btn btn-sm btn-primary"
                                            onclick="markAttendance({{ $emp->id }})">
                                        <i class="bi bi-check2"></i> Mark
                                    </button>
                                @endif
                            </div>
                        </div>
                        @endforeach

                        @if ($employees->isEmpty())
                        <div class="text-center text-muted py-4" style="font-size:.875rem;">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                            No active employees found.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Attendance Records Table --}}
        <div class="col-lg-{{ $date === today()->toDateString() ? '8' : '12' }}">
            <div class="hrms-card">
                <div class="card-header">
                    <i class="bi bi-table me-2 text-success"></i>
                    Records — {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Hours</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-table-body">
                            @forelse ($records as $record)
                            <tr id="record-row-{{ $record->id }}">
                                <td>
                                    <div class="fw-semibold">{{ $record->employee->full_name }}</div>
                                    <small class="text-muted">{{ $record->employee->designation }}</small>
                                </td>
                                <td>{{ $record->check_in }}</td>
                                <td>{{ $record->check_out ?? '—' }}</td>
                                <td>{{ $record->working_hours ?? '—' }}</td>
                                <td>
                                    @if ($record->status === 'present')
                                        <span class="badge bg-success-subtle text-success">Present</span>
                                    @elseif ($record->status === 'late')
                                        <span class="badge bg-warning-subtle text-warning">Late</span>
                                    @elseif ($record->status === 'underworked')
                                        <span class="badge bg-info-subtle text-info">Underworked</span>
                                    @elseif ($record->status === 'holiday')
                                        <span class="badge bg-purple-subtle text-purple" style="background:#f3e8ff;color:#7c3aed;">
                                            <i class="bi bi-flag me-1"></i>Holiday
                                        </span>
                                    @elseif ($record->status === 'weekly_off')
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <i class="bi bi-calendar2-week me-1"></i>Weekly Off
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Absent</span>
                                    @endif
                                    @if ($record->source === 'time_doctor')
                                        <span class="badge bg-primary-subtle text-primary ms-1">Time Doctor</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="{{ route('attendance.edit', $record) }}"
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('attendance.destroy', $record) }}"
                                              onsubmit="return confirm('Delete this record?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                    No attendance records for this date.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($records->hasPages())
                <div class="p-3 border-top">
                    {{ $records->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>

<style>
.avatar-sm-att {
    width: 32px; height: 32px; border-radius: 8px;
    background: #e8f4fd; color: #1a8fe3;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .8rem; flex-shrink: 0;
}
.btn-xs { padding: 2px 6px; font-size: .75rem; }
.employee-row { transition: background .15s; }
.employee-row:hover { background: #f8f9fa; }
</style>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// Live clock
function updateClock() {
    const el = document.getElementById('live-clock');
    if (el) el.textContent = new Date().toLocaleTimeString();
}
setInterval(updateClock, 1000);
updateClock();

// Employee search filter
document.getElementById('employee-search')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.employee-row').forEach(row => {
        row.style.display = row.dataset.name.includes(q) ? '' : 'none';
    });
});

// Mark attendance via AJAX
function markAttendance(employeeId) {
    fetch('{{ route('attendance.store') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ employee_id: employeeId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const badgeColor = data.status === 'late' ? 'bg-warning text-dark' : 'bg-success';
            const att = data.attendance;

            // Update mark button → badge + checkout button
            document.getElementById(`emp-action-${employeeId}`).innerHTML =
                `<span class="badge ${badgeColor}" id="emp-badge-${employeeId}">
                    ${capitalize(data.status)} ${att.check_in}
                 </span>
                 <button class="btn btn-xs btn-outline-secondary ms-1"
                         onclick="checkOut(${att.id}, ${employeeId})">Out</button>`;

            // Prepend new row to table
            const tbody = document.getElementById('attendance-table-body');
            const emptyRow = tbody.querySelector('td[colspan]');
            if (emptyRow) emptyRow.closest('tr').remove();

            const statusBadge = data.status === 'late'
                ? '<span class="badge bg-warning-subtle text-warning">Late</span>'
                : '<span class="badge bg-success-subtle text-success">Present</span>';

            tbody.insertAdjacentHTML('afterbegin', `
                <tr id="record-row-${att.id}">
                    <td><div class="fw-semibold">${data.attendance.employee_id}</div></td>
                    <td>${att.check_in}</td>
                    <td id="checkout-cell-${att.id}">—</td>
                    <td id="hours-cell-${att.id}">—</td>
                    <td>${statusBadge}</td>
                    <td></td>
                </tr>`);

            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Error marking attendance.', 'danger');
        }
    })
    .catch(() => showToast('Network error. Please try again.', 'danger'));
}

// Check out via AJAX
function checkOut(attendanceId, employeeId) {
    fetch(`/attendance/${attendanceId}/checkout`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Update sidebar badge
            const badge = document.getElementById(`emp-badge-${employeeId}`);
            if (badge) badge.insertAdjacentHTML('afterend',
                `<small class="text-muted ms-1">Out ${data.check_out}</small>`);
            const btn = badge?.nextElementSibling;
            if (btn?.tagName === 'BUTTON') btn.remove();

            // Update table row
            const checkoutCell = document.getElementById(`checkout-cell-${attendanceId}`);
            const hoursCell    = document.getElementById(`hours-cell-${attendanceId}`);
            if (checkoutCell) checkoutCell.textContent = data.check_out;
            if (hoursCell)    hoursCell.textContent    = data.working_hours ?? '—';

            showToast(data.message, 'success');
        }
    });
}

function capitalize(str) { return str.charAt(0).toUpperCase() + str.slice(1); }

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible position-fixed shadow`;
    toast.style.cssText = 'bottom:24px;right:24px;z-index:9999;min-width:280px;';
    toast.innerHTML = `${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}
</script>
@endpush
