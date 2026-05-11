<x-app-layout>
<x-slot name="title">Pending Salary Approvals</x-slot>
<x-alert />

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb" style="font-size:.82rem;">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('salary-increments.index') }}">Salary Increments</a></li>
                <li class="breadcrumb-item active">Pending Approvals</li>
            </ol>
        </nav>
        <h5 class="fw-bold mb-0">
            Pending Approvals
            @if($pending->isNotEmpty())
            <span class="badge bg-warning text-dark ms-2">{{ $pending->count() }}</span>
            @endif
        </h5>
        <small class="text-muted">Salary changes awaiting your approval</small>
    </div>
    <a href="{{ route('salary-increments.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-list me-1"></i> All Records
    </a>
</div>

@if($pending->isEmpty())
<div class="hrms-card p-5 text-center text-muted">
    <i class="bi bi-check-circle fs-1 d-block mb-3 text-success opacity-75"></i>
    <h6 class="fw-semibold">All caught up!</h6>
    <p class="mb-0">No salary increments are awaiting approval.</p>
</div>
@else
<div class="row g-3">
    @foreach($pending as $record)
    <div class="col-lg-6">
        <div class="hrms-card p-4 h-100 border-start border-warning border-3">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="fw-bold">{{ $record->employee?->full_name }}</div>
                    <small class="text-muted">{{ $record->employee?->employee_code }}
                        &middot; {{ $record->employee?->designation }}</small>
                </div>
                <span class="badge bg-{{ $record->getTypeBadge() }}-subtle text-{{ $record->getTypeBadge() }} px-2 py-1">
                    {{ $record->getTypeLabel() }}
                </span>
            </div>

            {{-- Salary details --}}
            <div class="row g-2 mb-3">
                <div class="col-4 text-center">
                    <div class="text-muted" style="font-size:.72rem;">Previous</div>
                    <div class="fw-semibold small">{{ $record->previous_salary !== null ? currency($record->previous_salary) : '—' }}</div>
                </div>
                <div class="col-4 text-center">
                    <div class="text-muted" style="font-size:.72rem;">Change</div>
                    <div class="fw-semibold small {{ ($record->increment_amount ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        @if($record->increment_amount !== null)
                            {{ $record->increment_amount >= 0 ? '+' : '' }}{{ currency($record->increment_amount) }}
                            <span class="d-block" style="font-size:.7rem;">
                                ({{ $record->increment_percentage >= 0 ? '+' : '' }}{{ number_format($record->increment_percentage ?? 0, 1) }}%)
                            </span>
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div class="text-muted" style="font-size:.72rem;">New Salary</div>
                    <div class="fw-bold text-success">{{ currency($record->base_salary) }}</div>
                </div>
            </div>

            {{-- Meta --}}
            <div class="mb-3 p-2 bg-light rounded" style="font-size:.78rem;">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Effective</span>
                    <span class="fw-semibold">{{ $record->effective_from->format('F Y') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Submitted by</span>
                    <span>{{ $record->changedBy?->name ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Submitted at</span>
                    <span>{{ $record->created_at->format('d M Y H:i') }}</span>
                </div>
            </div>

            @if($record->reason)
            <div class="mb-3">
                <span class="text-muted small">Reason:</span>
                <span class="small">{{ $record->reason }}</span>
            </div>
            @endif

            {{-- Actions --}}
            <div class="d-flex gap-2">
                <a href="{{ route('salary-increments.show', $record) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-eye me-1"></i> View
                </a>
                <form method="POST" action="{{ route('salary-increments.approve', $record) }}"
                      onsubmit="return confirm('Approve salary increment for {{ addslashes($record->employee?->full_name) }}?')"
                      class="flex-fill">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success w-100">
                        <i class="bi bi-check-circle me-1"></i> Approve
                    </button>
                </form>
                <button type="button" class="btn btn-sm btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#rejectModal{{ $record->id }}">
                    <i class="bi bi-x-circle me-1"></i> Reject
                </button>
            </div>

        </div>
    </div>

    {{-- Reject modal --}}
    <div class="modal fade" id="rejectModal{{ $record->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('salary-increments.reject', $record) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title">Reject — {{ $record->employee?->full_name }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label fw-semibold">Rejection Note <span class="text-muted">(optional)</span></label>
                        <textarea name="note" class="form-control" rows="3" maxlength="500"
                                  placeholder="Reason for rejection…"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endif

</x-app-layout>
