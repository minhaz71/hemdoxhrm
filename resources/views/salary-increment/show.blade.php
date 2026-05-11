<x-app-layout>
<x-slot name="title">Salary Increment #{{ $salaryIncrement->id }}</x-slot>
<x-alert />

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb" style="font-size:.82rem;">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('salary-increments.index') }}">Salary Increments</a></li>
                <li class="breadcrumb-item active">#{{ $salaryIncrement->id }}</li>
            </ol>
        </nav>
        <h5 class="fw-bold mb-0">Salary Increment #{{ $salaryIncrement->id }}</h5>
        <small class="text-muted">
            <span class="badge bg-{{ $salaryIncrement->getTypeBadge() }}-subtle text-{{ $salaryIncrement->getTypeBadge() }}">
                {{ $salaryIncrement->getTypeLabel() }}
            </span>
            &middot;
            <span class="badge bg-{{ $salaryIncrement->getStatusBadge() }}-subtle text-{{ $salaryIncrement->getStatusBadge() }}">
                {{ ucfirst($salaryIncrement->status) }}
            </span>
        </small>
    </div>
    <div class="d-flex gap-2">
        @if($salaryIncrement->isPending() && Gate::check('employees.salary.manage'))
        <a href="{{ route('salary-increments.edit', $salaryIncrement) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @if(auth()->user()->isAdmin())
        <form method="POST" action="{{ route('salary-increments.approve', $salaryIncrement) }}"
              onsubmit="return confirm('Approve this increment?')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-check-circle me-1"></i> Approve
            </button>
        </form>
        <button type="button" class="btn btn-outline-danger btn-sm"
                data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="bi bi-x-circle me-1"></i> Reject
        </button>
        @endif
        @endif
        <a href="{{ route('salary-increments.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-3">

    {{-- Main detail card --}}
    <div class="col-lg-7">
        <div class="hrms-card p-4 h-100">
            <h6 class="fw-bold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">Increment Details</h6>

            {{-- Employee --}}
            <div class="mb-3 d-flex gap-3 align-items-center">
                <div>
                    <div class="fw-bold">{{ $salaryIncrement->employee?->full_name }}</div>
                    <small class="text-muted">{{ $salaryIncrement->employee?->employee_code }}
                        &middot; {{ $salaryIncrement->employee?->designation }}</small>
                </div>
            </div>
            <hr>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <div class="text-muted small">Previous Salary</div>
                    <div class="fw-bold">{{ $salaryIncrement->previous_salary !== null ? currency($salaryIncrement->previous_salary) : '—' }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">New Salary</div>
                    <div class="fw-bold text-success fs-5">{{ currency($salaryIncrement->base_salary) }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Increment Amount</div>
                    <div class="fw-bold {{ ($salaryIncrement->increment_amount ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        @if($salaryIncrement->increment_amount !== null)
                            {{ $salaryIncrement->increment_amount >= 0 ? '+' : '' }}{{ currency($salaryIncrement->increment_amount) }}
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Increment %</div>
                    <div class="fw-bold {{ ($salaryIncrement->increment_percentage ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        @if($salaryIncrement->increment_percentage !== null)
                            {{ $salaryIncrement->increment_percentage >= 0 ? '+' : '' }}{{ number_format($salaryIncrement->increment_percentage, 2) }}%
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Effective Month</div>
                    <div class="fw-bold">{{ $salaryIncrement->effective_from->format('F Y') }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Status</div>
                    <span class="badge bg-{{ $salaryIncrement->getStatusBadge() }}-subtle text-{{ $salaryIncrement->getStatusBadge() }} px-2 py-1">
                        {{ ucfirst($salaryIncrement->status) }}
                    </span>
                </div>
            </div>

            @if($salaryIncrement->reason)
            <div class="mb-2">
                <div class="text-muted small">Reason</div>
                <div>{{ $salaryIncrement->reason }}</div>
            </div>
            @endif

            @if($salaryIncrement->note)
            <div class="mb-2">
                <div class="text-muted small">Note</div>
                <div class="text-muted">{{ $salaryIncrement->note }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Audit trail --}}
    <div class="col-lg-5">
        <div class="hrms-card p-4 h-100">
            <h6 class="fw-bold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">Audit Trail</h6>

            <ul class="list-unstyled mb-0">
                <li class="d-flex gap-3 mb-3">
                    <div class="text-center" style="width:32px;">
                        <span class="badge bg-primary-subtle text-primary rounded-circle p-2">
                            <i class="bi bi-plus-lg"></i>
                        </span>
                    </div>
                    <div>
                        <div class="fw-semibold small">Submitted</div>
                        <div class="text-muted small">{{ $salaryIncrement->changedBy?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $salaryIncrement->created_at->format('d M Y H:i') }}</div>
                    </div>
                </li>

                @if($salaryIncrement->status === 'approved')
                <li class="d-flex gap-3 mb-3">
                    <div class="text-center" style="width:32px;">
                        <span class="badge bg-success-subtle text-success rounded-circle p-2">
                            <i class="bi bi-check-lg"></i>
                        </span>
                    </div>
                    <div>
                        <div class="fw-semibold small">Approved</div>
                        <div class="text-muted small">{{ $salaryIncrement->approvedBy?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $salaryIncrement->updated_at->format('d M Y H:i') }}</div>
                    </div>
                </li>
                @elseif($salaryIncrement->status === 'rejected')
                <li class="d-flex gap-3 mb-3">
                    <div class="text-center" style="width:32px;">
                        <span class="badge bg-danger-subtle text-danger rounded-circle p-2">
                            <i class="bi bi-x-lg"></i>
                        </span>
                    </div>
                    <div>
                        <div class="fw-semibold small">Rejected</div>
                        <div class="text-muted small">{{ $salaryIncrement->approvedBy?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $salaryIncrement->updated_at->format('d M Y H:i') }}</div>
                    </div>
                </li>
                @endif
            </ul>
        </div>
    </div>

    {{-- Last 5 salary history --}}
    @if($recentHistory->isNotEmpty())
    <div class="col-12">
        <div class="hrms-card">
            <div class="p-3 border-bottom">
                <h6 class="fw-bold mb-0">Recent Salary History — {{ $salaryIncrement->employee?->full_name }}</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>New Salary</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentHistory as $hist)
                        <tr {{ $hist->id === $salaryIncrement->id ? 'class=table-info' : '' }}>
                            <td>
                                <span class="badge bg-{{ $hist->getTypeBadge() }}-subtle text-{{ $hist->getTypeBadge() }}" style="font-size:.7rem;">
                                    {{ $hist->getTypeLabel() }}
                                </span>
                            </td>
                            <td>{{ currency($hist->base_salary) }}</td>
                            <td>{{ $hist->effective_from->format('M Y') }}</td>
                            <td>{{ $hist->effective_to ? $hist->effective_to->format('d M Y') : 'Present' }}</td>
                            <td>
                                <span class="badge bg-{{ $hist->getStatusBadge() }}-subtle text-{{ $hist->getStatusBadge() }}" style="font-size:.7rem;">
                                    {{ ucfirst($hist->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>

{{-- Reject Modal --}}
@if($salaryIncrement->isPending() && auth()->user()->isAdmin())
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('salary-increments.reject', $salaryIncrement) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Reject Increment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Rejection Note <span class="text-muted">(optional)</span></label>
                    <textarea name="note" class="form-control" rows="3" maxlength="500"
                              placeholder="Reason for rejection…">{{ $salaryIncrement->note }}</textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

</x-app-layout>
