<x-app-layout>
<x-slot name="title">Salary Increments</x-slot>
<x-alert />

@push('styles')
<style>
.filter-bar { background: #fff; border: 1px solid #e9ecef; border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; }
</style>
@endpush

{{-- ── Page Header ─────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb" style="font-size:.82rem;">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item active">Salary Increments</li>
            </ol>
        </nav>
        <h5 class="fw-bold mb-0">
            Salary Increments
            @if($pendingCount > 0)
            <a href="{{ route('salary-increments.approval') }}" class="badge bg-warning text-dark ms-2" style="font-size:.72rem;">
                {{ $pendingCount }} pending
            </a>
            @endif
        </h5>
        <small class="text-muted">{{ $records->total() }} record{{ $records->total() !== 1 ? 's' : '' }}</small>
    </div>
    @if(Gate::check('employees.salary.manage'))
    <a href="{{ route('salary-increments.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New Increment
    </a>
    @endif
</div>

{{-- ── Filter Bar ────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('salary-increments.index') }}" class="filter-bar">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Employee</label>
            <select name="employee_id" class="form-select form-select-sm">
                <option value="">All Employees</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ ($filters['employee_id'] ?? '') == $emp->id ? 'selected' : '' }}>
                    {{ $emp->full_name }} ({{ $emp->employee_code }})
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Status</option>
                <option value="pending"  {{ ($filters['status'] ?? '') === 'pending'  ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Type</label>
            <select name="salary_type" class="form-select form-select-sm">
                <option value="">All Types</option>
                <option value="increment"  {{ ($filters['salary_type'] ?? '') === 'increment'  ? 'selected' : '' }}>Increment</option>
                <option value="decrement"  {{ ($filters['salary_type'] ?? '') === 'decrement'  ? 'selected' : '' }}>Decrement</option>
                <option value="adjustment" {{ ($filters['salary_type'] ?? '') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
            </select>
        </div>
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            @if(array_filter($filters))
            <a href="{{ route('salary-increments.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i> Clear
            </a>
            @endif
        </div>
    </div>
</form>

{{-- ── Table ────────────────────────────────────────────────────── --}}
<div class="hrms-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Previous Salary</th>
                    <th>New Salary</th>
                    <th>Amount</th>
                    <th>%</th>
                    <th>Effective Month</th>
                    <th>Status</th>
                    <th>Submitted By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                @php
                    $typeBadge   = $record->getTypeBadge();
                    $statusBadge = $record->getStatusBadge();
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold" style="font-size:.875rem;">{{ $record->employee?->full_name }}</div>
                        <small class="text-muted">{{ $record->employee?->employee_code }}</small>
                    </td>
                    <td>
                        <span class="badge bg-{{ $typeBadge }}-subtle text-{{ $typeBadge }}" style="font-size:.72rem;">
                            {{ $record->getTypeLabel() }}
                        </span>
                    </td>
                    <td style="font-size:.875rem;">{{ $record->previous_salary !== null ? currency($record->previous_salary) : '—' }}</td>
                    <td style="font-size:.875rem;" class="fw-semibold">{{ currency($record->base_salary) }}</td>
                    <td style="font-size:.875rem;">
                        @if($record->increment_amount !== null)
                            <span class="{{ $record->increment_amount >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $record->increment_amount >= 0 ? '+' : '' }}{{ currency($record->increment_amount) }}
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td style="font-size:.875rem;">
                        @if($record->increment_percentage !== null)
                            <span class="{{ $record->increment_percentage >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $record->increment_percentage >= 0 ? '+' : '' }}{{ number_format($record->increment_percentage, 2) }}%
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td style="font-size:.875rem;">{{ $record->effective_from->format('M Y') }}</td>
                    <td>
                        <span class="badge bg-{{ $statusBadge }}-subtle text-{{ $statusBadge }}" style="font-size:.72rem;">
                            {{ ucfirst($record->status) }}
                        </span>
                    </td>
                    <td style="font-size:.82rem;">{{ $record->changedBy?->name ?? '—' }}</td>
                    <td>
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('salary-increments.show', $record) }}"
                               class="btn btn-sm btn-outline-secondary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($record->isPending() && Gate::check('employees.salary.manage'))
                            <a href="{{ route('salary-increments.edit', $record) }}"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if(auth()->user()->isAdmin())
                            <form method="POST" action="{{ route('salary-increments.approve', $record) }}"
                                  onsubmit="return confirm('Approve this increment?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Approve">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-danger" title="Reject"
                                    data-bs-toggle="modal" data-bs-target="#rejectModal{{ $record->id }}">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                        @if(array_filter($filters))
                            No records match your filters. <a href="{{ route('salary-increments.index') }}">Clear filters</a>
                        @else
                            No salary increments yet.
                            @if(Gate::check('employees.salary.manage'))
                            <a href="{{ route('salary-increments.create') }}">Create the first one.</a>
                            @endif
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
    <div class="p-3 border-top">
        {{ $records->appends($filters)->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

{{-- ── Reject modals ─────────────────────────────────────────────── --}}
@foreach($records as $record)
@if($record->isPending() && auth()->user()->isAdmin())
<div class="modal fade" id="rejectModal{{ $record->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('salary-increments.reject', $record) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Reject Increment — {{ $record->employee?->full_name }}</h6>
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
@endif
@endforeach

</x-app-layout>
