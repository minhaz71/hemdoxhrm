<x-app-layout title="Salary History">
    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-0">Salary History</h5>
            <small class="text-muted">Browse salary changes across employees</small>
        </div>
    </div>

    <div class="hrms-card p-3 mb-4">
        <form method="GET" action="{{ route('salary-history.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Employee</label>
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(($filters['employee_id'] ?? '') == $employee->id)>
                            {{ $employee->full_name }} ({{ $employee->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="hrms-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Previous</th>
                        <th>New Salary</th>
                        <th>Effective</th>
                        <th>Status</th>
                        <th>Changed By</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $record->employee?->full_name ?? '—' }}</div>
                                <small class="text-muted">{{ $record->employee?->employee_code ?? '' }} · {{ $record->employee?->user?->email ?? 'No email' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-{{ $record->getTypeBadge() }}-subtle text-{{ $record->getTypeBadge() }}">
                                    {{ $record->getTypeLabel() }}
                                </span>
                            </td>
                            <td>{{ $record->previous_salary !== null ? currency($record->previous_salary) : '—' }}</td>
                            <td class="fw-semibold">{{ currency($record->base_salary) }}</td>
                            <td>
                                {{ $record->effective_from?->format('M j, Y') }}
                                @if($record->effective_to)
                                    <small class="text-muted d-block">to {{ $record->effective_to->format('M j, Y') }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $record->getStatusBadge() }}-subtle text-{{ $record->getStatusBadge() }}">
                                    {{ ucfirst($record->status) }}
                                </span>
                            </td>
                            <td>{{ $record->changedBy?->name ?? '—' }}</td>
                            <td class="text-end">
                                @if($record->employee)
                                    <a href="{{ route('employees.salary-history.index', $record->employee) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-clock-history me-1"></i> Open
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No salary history records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-3 border-top">
                {{ $records->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</x-app-layout>
