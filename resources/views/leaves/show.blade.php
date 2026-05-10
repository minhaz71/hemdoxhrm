<x-app-layout>
    <x-slot name="title">Leave Application</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Leave Application</h5>
            <small class="text-muted">#{{ $leave->id }} — {{ $leave->employee->full_name }}</small>
        </div>
        <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">

            {{-- Details --}}
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                    Application Details
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Employee</small>
                        <span class="fw-semibold">{{ $leave->employee->full_name }}</span>
                        <small class="text-muted ms-1">({{ $leave->employee->employee_code }})</small>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Department</small>
                        <span class="fw-semibold">{{ $leave->employee->department }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Leave Type</small>
                        <span class="fw-semibold">{{ $leave->effective_type_name }}</span>
                        @if ($leave->is_unpaid_override)
                            <span class="badge bg-danger-subtle text-danger ms-1">Unpaid Override</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Duration</small>
                        <span class="fw-semibold">
                            {{ $leave->start_date->format('M j') }} – {{ $leave->end_date->format('M j, Y') }}
                        </span>
                        <span class="badge bg-primary-subtle text-primary ms-1">{{ $leave->total_days }} day(s)</span>
                    </div>
                    @if ($leave->reason)
                    <div class="col-12">
                        <small class="text-muted d-block">Reason</small>
                        <span>{{ $leave->reason }}</span>
                    </div>
                    @endif
                    @if ($leave->rejection_note)
                    <div class="col-12">
                        <small class="text-muted d-block">Rejection Note</small>
                        <span class="text-danger">{{ $leave->rejection_note }}</span>
                    </div>
                    @endif
                    @if ($leave->approvedBy)
                    <div class="col-md-6">
                        <small class="text-muted d-block">Actioned By</small>
                        <span class="fw-semibold">{{ $leave->approvedBy->name }}</span>
                        <small class="text-muted"> on {{ $leave->actioned_at->format('M j, Y H:i') }}</small>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Approval Actions --}}
            @hasrole('admin', 'hr', 'manager')
            @if ($leave->status === 'pending')
            <div class="hrms-card p-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                    Action
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <form method="POST" action="{{ route('leaves.action', $leave) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="action" value="approved">
                            <button type="submit" class="btn btn-success w-100"
                                    onclick="return confirm('Approve this leave?')">
                                <i class="bi bi-check-circle me-1"></i> Approve Leave
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-danger w-100"
                                data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle me-1"></i> Reject Leave
                        </button>
                    </div>
                </div>
            </div>
            @endif
            @endhasrole

        </div>

        {{-- Sidebar: Status + Balance --}}
        <div class="col-lg-4">

            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                    Status
                </h6>
                @if ($leave->status === 'pending')
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6">
                        <i class="bi bi-hourglass-split me-1"></i> Pending
                    </span>
                @elseif ($leave->status === 'approved')
                    <span class="badge bg-success px-3 py-2 fs-6">
                        <i class="bi bi-check-circle me-1"></i> Approved
                    </span>
                @else
                    <span class="badge bg-danger px-3 py-2 fs-6">
                        <i class="bi bi-x-circle me-1"></i> Rejected
                    </span>
                @endif
                <div class="mt-2 text-muted small">Applied {{ $leave->created_at->diffForHumans() }}</div>
            </div>

            <div class="hrms-card p-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                    Leave Balance {{ now()->year }}
                </h6>
                @foreach ($balance as $b)
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <div>
                        <div class="fw-semibold" style="font-size:.85rem;">{{ $b['type'] }}</div>
                        <small class="text-muted">{{ $b['is_paid'] ? 'Paid' : 'Unpaid' }}</small>
                    </div>
                    <div class="text-end">
                        @if ($b['allowed'] > 0)
                            <span class="fw-bold {{ $b['remaining'] == 0 ? 'text-danger' : 'text-success' }}">
                                {{ $b['remaining'] }}
                            </span>
                            <small class="text-muted"> / {{ $b['allowed'] }}</small>
                        @else
                            <small class="text-muted">Unlimited</small>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('leaves.action', $leave) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="rejected">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">Reject Leave</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="rejection_note" class="form-control @error('rejection_note') is-invalid @enderror"
                              rows="3" placeholder="Reason for rejection..." required>{{ old('rejection_note') }}</textarea>
                    @error('rejection_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
