<x-app-layout>
    <x-slot name="title">Time Doctor Import</x-slot>

    <x-alert />

    @php
        $formatMinutes = fn (?int $minutes) => sprintf('%dh %02dm', intdiv((int) $minutes, 60), ((int) $minutes) % 60);
        $statusClass = fn (string $status) => match ($status) {
            'present' => 'bg-success-subtle text-success',
            'late' => 'bg-warning-subtle text-warning',
            'underworked' => 'bg-info-subtle text-info',
            default => 'bg-danger-subtle text-danger',
        };
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">{{ $import->original_filename }}</h5>
            <small class="text-muted">
                Imported {{ $import->imported_at?->format('M j, Y g:i A') }} by {{ $import->importer?->name ?? 'System' }}
            </small>
        </div>
        <a href="{{ route('time-doctor.imports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f4fd;">
                    <i class="bi bi-list-check" style="color:#1a8fe3;"></i>
                </div>
                <div>
                    <div class="label">Processed</div>
                    <div class="value">{{ $import->processed_rows }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eaf7ee;">
                    <i class="bi bi-plus-circle" style="color:#28a745;"></i>
                </div>
                <div>
                    <div class="label">Created</div>
                    <div class="value">{{ $import->created_records }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e6;">
                    <i class="bi bi-pencil-square" style="color:#ffc107;"></i>
                </div>
                <div>
                    <div class="label">Updated</div>
                    <div class="value">{{ $import->updated_records }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fdecea;">
                    <i class="bi bi-exclamation-triangle" style="color:#dc3545;"></i>
                </div>
                <div>
                    <div class="label">Leave Alerts</div>
                    <div class="value">{{ $leaveConflictCount }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($leaveConflictCount > 0)
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>
                <strong>{{ $leaveConflictCount }} worked-on-leave warning{{ $leaveConflictCount === 1 ? '' : 's' }} found.</strong>
                Employees are notified once per Time Doctor daily record. HR should review before applying a penalty or leave adjustment.
            </div>
        </div>
    @endif

    @if (! empty($import->errors))
        <div class="hrms-card mb-4">
            <div class="card-header">
                <i class="bi bi-exclamation-circle me-2 text-warning"></i>Skipped Rows
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Line</th>
                            <th>Email</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($import->errors as $error)
                            <tr>
                                <td>{{ $error['line'] ?? '—' }}</td>
                                <td>{{ $error['email'] ?? '—' }}</td>
                                <td>{{ $error['message'] ?? 'Unable to process row.' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="hrms-card">
        <div class="card-header">
            <i class="bi bi-speedometer2 me-2 text-primary"></i>Processed Records
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Tracked</th>
                        <th>Idle</th>
                        <th>Active</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th class="text-end">Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $record->employee->full_name }}</div>
                                <small class="text-muted">{{ $record->email }}</small>
                            </td>
                            <td>{{ $record->work_date->format('M j, Y') }}</td>
                            <td>{{ $formatMinutes($record->time_tracked_minutes) }}</td>
                            <td>
                                {{ $formatMinutes($record->idle_minutes) }}
                                <small class="text-muted">({{ number_format($record->idle_minutes_percent, 2) }}%)</small>
                            </td>
                            <td>
                                {{ $formatMinutes($record->productive_minutes ?? $record->active_minutes) }}
                                <small class="text-muted">({{ number_format($record->productivity_percentage ?? $record->activity_percent, 2) }}%)</small>
                            </td>
                            <td>{{ $record->start_time ? substr($record->start_time, 0, 5) : '—' }}</td>
                            <td>{{ $record->end_time ? substr($record->end_time, 0, 5) : '—' }}</td>
                            <td>
                                <span class="badge {{ $statusClass($record->attendance_status) }}">
                                    {{ ucfirst($record->attendance_status) }}
                                </span>
                                @if ($record->worked_on_leave)
                                    <span class="badge bg-warning-subtle text-warning ms-1" title="Time Doctor shows work during approved leave">
                                        Leave alert
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('attendance.history', $record->employee) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-clock-history"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                No records were processed for this import.
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
