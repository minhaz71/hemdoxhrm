<x-app-layout>
    <x-slot name="title">{{ $isSelfService ? 'My Leave' : 'Leave Management' }}</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">{{ $isSelfService ? 'My Leave' : 'Leave Applications' }}</h5>
            <small class="text-muted">
                {{ $leaves->total() }} {{ $isSelfService ? 'application(s)' : 'total applications' }}
            </small>
        </div>
        <a href="{{ route('leaves.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Apply Leave
        </a>
    </div>

    {{-- Filters --}}
    <div class="hrms-card p-3 mb-4">
        <form method="GET" action="{{ route('leaves.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="pending"  {{ ($filters['status'] ?? '') === 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            @unless($isSelfService)
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Employee</label>
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">All Employees</option>
                    @foreach ($employees as $emp)
                    <option value="{{ $emp->id }}"
                        {{ ($filters['employee_id'] ?? '') == $emp->id ? 'selected' : '' }}>
                        {{ $emp->first_name }} {{ $emp->last_name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endunless
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-primary">Filter</button>
                <a href="{{ route('leaves.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="hrms-card">
        <div class="card-header"><i class="bi bi-calendar-check me-2 text-success"></i>Applications</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @unless($isSelfService)
                        <th>Employee</th>
                        @endunless
                        <th>Leave Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Applied</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaves as $leave)
                    <tr>
                        @unless($isSelfService)
                        <td>
                            <div class="fw-semibold">{{ $leave->employee->full_name }}</div>
                            <small class="text-muted">{{ $leave->employee->department }}</small>
                        </td>
                        @endunless
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">
                                {{ $leave->effective_type_name }}
                            </span>
                            @if ($leave->is_unpaid_override)
                                <span class="badge bg-danger-subtle text-danger ms-1">Unpaid</span>
                            @endif
                        </td>
                        <td>{{ $leave->start_date->format('M j, Y') }}</td>
                        <td>{{ $leave->end_date->format('M j, Y') }}</td>
                        <td><span class="fw-semibold">{{ $leave->total_days }}</span></td>
                        <td>
                            @if ($leave->status === 'pending')
                                <span class="badge bg-warning-subtle text-warning">Pending</span>
                            @elseif ($leave->status === 'approved')
                                <span class="badge bg-success-subtle text-success">Approved</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Rejected</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $leave->created_at->format('M j') }}</td>
                        <td>
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('leaves.show', $leave) }}"
                                   class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if (auth()->user()->isAdmin())
                                <a href="{{ route('leaves.show', $leave) }}#admin-correction"
                                   class="btn btn-sm btn-outline-primary" title="Admin correction">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                @endif
                                @hasrole('admin', 'hr', 'manager')
                                    @if ($leave->status === 'pending')
                                    <form method="POST" action="{{ route('leaves.action', $leave) }}"
                                          onsubmit="return confirm('Approve this leave?')">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="action" value="approved">
                                        <button class="btn btn-sm btn-outline-success" title="Approve">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rejectModal{{ $leave->id }}"
                                            title="Reject">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    @endif
                                @endhasrole
                                @if ($leave->status === 'pending')
                                <form method="POST" action="{{ route('leaves.destroy', $leave) }}"
                                      onsubmit="return confirm('Cancel this leave application?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-secondary" title="Cancel">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isSelfService ? 7 : 8 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                            No leave applications found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($leaves->hasPages())
        <div class="p-3 border-top">{{ $leaves->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>

    @hasrole('admin', 'hr', 'manager')
    @foreach ($leaves as $leave)
        @if ($leave->status === 'pending')
        <div class="modal fade" id="rejectModal{{ $leave->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('leaves.action', $leave) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="action" value="rejected">
                        <div class="modal-header border-0 pb-0">
                            <h6 class="modal-title fw-bold">Reject Leave Application</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                            <textarea name="rejection_note" class="form-control" rows="3"
                                      placeholder="Provide a reason..." required></textarea>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Reject Leave</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @endforeach
    @endhasrole
</x-app-layout>
