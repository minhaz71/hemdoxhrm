<x-app-layout>
    <x-slot name="title">Attendance History</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Attendance History</h5>
            <small class="text-muted">{{ $employee->full_name }} — {{ $employee->employee_code }}</small>
        </div>
        <a href="{{ auth()->user()->isAdmin() || auth()->user()->isHR() || auth()->user()->isManager() ? route('attendance.index') : route('dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    {{-- Monthly summary bar --}}
    @php
        $present  = $records->where('status', 'present')->count();
        $late     = $records->where('status', 'late')->count();
        $underworked = $records->where('status', 'underworked')->count();
        $absent   = $records->where('status', 'absent')->count();
        $total    = $records->total();
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eaf7ee;"><i class="bi bi-check-circle" style="color:#28a745;"></i></div>
                <div><div class="label">Present</div><div class="value">{{ $present }}</div></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e6;"><i class="bi bi-clock" style="color:#ffc107;"></i></div>
                <div><div class="label">Late</div><div class="value">{{ $late }}</div></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f4fd;"><i class="bi bi-hourglass-split" style="color:#1a8fe3;"></i></div>
                <div><div class="label">Underworked</div><div class="value">{{ $underworked }}</div></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fdecea;"><i class="bi bi-x-circle" style="color:#dc3545;"></i></div>
                <div><div class="label">Absent</div><div class="value">{{ $absent }}</div></div>
            </div>
        </div>
    </div>

    <div class="hrms-card">
        <div class="card-header">
            <i class="bi bi-clock-history me-2 text-primary"></i>All Records
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                    <tr>
                        <td class="fw-semibold">{{ $record->date->format('M j, Y') }}</td>
                        <td class="text-muted">{{ $record->date->format('D') }}</td>
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
                            @else
                                <span class="badge bg-danger-subtle text-danger">Absent</span>
                            @endif
                            @if ($record->source === 'time_doctor')
                                <span class="badge bg-primary-subtle text-primary ms-1">Time Doctor</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $record->note ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                            No attendance records found.
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
</x-app-layout>
