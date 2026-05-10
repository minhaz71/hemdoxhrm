<x-app-layout title="Employees">

@push('styles')
<style>
.avatar-sm {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: #e8f4fd;
    color: #1a8fe3;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700;
    font-size: .8rem;
    flex-shrink: 0;
}
.filter-bar { background: #fff; border: 1px solid #e9ecef; border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; }
</style>
@endpush

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0">Employee List</h5>
        <small class="text-muted">{{ $employees->total() }} total employee{{ $employees->total() !== 1 ? 's' : '' }}</small>
    </div>
    <a href="{{ route('employees.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> Add Employee
    </a>
</div>

{{-- ── Filter Bar ────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('employees.index') }}" class="filter-bar">
    <div class="row g-2 align-items-end">
        {{-- Search --}}
        <div class="col-md-4">
            <label class="form-label small fw-semibold text-muted mb-1">Search</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control"
                       placeholder="Name or employee code…" value="{{ $filters['search'] ?? '' }}">
            </div>
        </div>

        {{-- Branch filter (only when branches enabled) --}}
        @if(branches_enabled() && $branches->isNotEmpty())
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Branch</label>
            <select name="branch_id" class="form-select form-select-sm">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ ($filters['branch_id'] ?? '') == $b->id ? 'selected' : '' }}>
                    {{ $b->name }}{{ $b->is_headquarters ? ' (HQ)' : '' }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Status filter --}}
        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Status</option>
                <option value="active"     {{ ($filters['status'] ?? '') === 'active'     ? 'selected' : '' }}>Active</option>
                <option value="inactive"   {{ ($filters['status'] ?? '') === 'inactive'   ? 'selected' : '' }}>Inactive</option>
                <option value="terminated" {{ ($filters['status'] ?? '') === 'terminated' ? 'selected' : '' }}>Terminated</option>
            </select>
        </div>

        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            @if(array_filter($filters))
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i> Clear
            </a>
            @endif
        </div>
    </div>
</form>

{{-- ── Active filter chips ──────────────────────────────────────── --}}
@if(array_filter($filters))
<div class="d-flex flex-wrap gap-2 mb-3">
    @if(!empty($filters['search']))
    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
        <i class="bi bi-search me-1"></i> "{{ $filters['search'] }}"
    </span>
    @endif
    @if(!empty($filters['branch_id']) && branches_enabled())
    @php $bf = $branches->firstWhere('id', $filters['branch_id']); @endphp
    <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2">
        <i class="bi bi-geo-alt me-1"></i> {{ $bf?->name ?? 'Branch #'.$filters['branch_id'] }}
    </span>
    @endif
    @if(!empty($filters['status']))
    <span class="badge bg-secondary-subtle text-secondary border px-3 py-2">
        <i class="bi bi-circle-fill me-1" style="font-size:.45rem;vertical-align:middle;"></i> {{ ucfirst($filters['status']) }}
    </span>
    @endif
</div>
@endif

{{-- ── Table ────────────────────────────────────────────────────── --}}
<div class="hrms-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>System Code</th>
                    <th>Employee</th>
                    @if(branches_enabled())
                    <th>Branch</th>
                    @endif
                    <th>Department / Designation</th>
                    <th>Type</th>
                    @if(auth()->user()->isAdmin() || auth()->user()->isHR())
                    <th>Base Salary</th>
                    @endif
                    <th>Status</th>
                    <th>Joined</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                <tr>
                    <td>
                        <span class="badge bg-light text-dark border" style="font-family:monospace;font-size:.75rem;">
                            {{ $employee->employee_code }}
                        </span>
                        @if($employee->organization_employee_code)
                            <div class="text-muted mt-1" style="font-size:.72rem;">
                                Org: {{ $employee->organization_employee_code }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</div>
                            <div>
                                <div class="fw-semibold" style="font-size:.875rem;">{{ $employee->full_name }}</div>
                                <small class="text-muted">{{ $employee->user?->email ?? '—' }}</small>
                            </div>
                        </div>
                    </td>
                    @if(branches_enabled())
                    <td>
                        @if($employee->branch)
                            <span class="d-flex align-items-center gap-1" style="font-size:.82rem;">
                                <i class="bi bi-geo-alt text-muted" style="font-size:.75rem;"></i>
                                {{ $employee->branch->name }}
                                @if($employee->branch->is_headquarters)
                                <span class="badge bg-warning-subtle text-warning" style="font-size:.6rem;">HQ</span>
                                @endif
                            </span>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    @endif
                    <td style="font-size:.82rem;">
                        <div class="fw-semibold">{{ $employee->designation }}</div>
                        <div class="text-muted">
                            {{ $employee->department?->name ?? $employee->department ?? '—' }}
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-info-subtle text-info" style="font-size:.72rem;">
                            {{ str_replace('_', ' ', ucfirst($employee->employment_type)) }}
                        </span>
                    </td>
                    @if(auth()->user()->isAdmin() || auth()->user()->isHR())
                    <td style="font-size:.875rem;">{{ currency($employee->base_salary) }}</td>
                    @endif
                    <td>
                        @php
                            $sc = match($employee->status) {
                                'active'     => ['bg-success-subtle text-success', 'Active'],
                                'terminated' => ['bg-danger-subtle text-danger',   'Terminated'],
                                default      => ['bg-warning-subtle text-warning', 'Inactive'],
                            };
                        @endphp
                        <span class="badge {{ $sc[0] }}" style="font-size:.72rem;">{{ $sc[1] }}</span>
                    </td>
                    <td style="font-size:.82rem;">{{ $employee->join_date->format('d M Y') }}</td>
                    <td>
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('employees.show', $employee) }}"
                               class="btn btn-sm btn-outline-secondary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('employees.edit', $employee) }}"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if ($employee->status !== 'terminated')
                            <form method="POST"
                                  action="{{ route('employees.terminate', $employee) }}"
                                  onsubmit="return confirm('Terminate {{ addslashes($employee->full_name) }}? This will disable their login.')">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Terminate">
                                    <i class="bi bi-person-x"></i>
                                </button>
                            </form>
                            @endif
                            <form method="POST"
                                  action="{{ route('employees.destroy', $employee) }}"
                                  onsubmit="return confirm('Permanently delete {{ addslashes($employee->full_name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ branches_enabled() ? (auth()->user()->isAdmin() || auth()->user()->isHR() ? 9 : 8) : (auth()->user()->isAdmin() || auth()->user()->isHR() ? 8 : 7) }}" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                        @if(array_filter($filters))
                            No employees match your filters.
                            <a href="{{ route('employees.index') }}">Clear filters</a>
                        @else
                            No employees yet. <a href="{{ route('employees.create') }}">Add the first one.</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($employees->hasPages())
    <div class="p-3 border-top">
        {{ $employees->appends($filters)->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

</x-app-layout>
