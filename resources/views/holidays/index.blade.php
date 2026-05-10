<x-app-layout>
    <x-slot name="title">Holidays</x-slot>
    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Holiday Management</h5>
            <small class="text-muted">Company, branch, department, and employee-specific holidays</small>
        </div>
        <a href="{{ route('holidays.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Holiday
        </a>
    </div>

    <div class="hrms-card p-3 mb-4">
        <form class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Holiday Year</label>
                <input type="number" name="year" class="form-control" value="{{ request('year', now()->year) }}" min="2000" max="2100">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100">Filter</button>
            </div>
        </form>
    </div>

    <div class="hrms-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Holiday</th>
                        <th>Dates</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Email</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($holidays as $holiday)
                    <tr>
                        <td><div class="fw-semibold">{{ $holiday->title }}</div><small class="text-muted">{{ $holiday->reason }}</small></td>
                        <td>{{ $holiday->start_date->format('M j, Y') }} - {{ $holiday->end_date->format('M j, Y') }}</td>
                        <td><span class="badge bg-primary-subtle text-primary">{{ str_replace('_', ' ', ucfirst($holiday->type)) }}</span></td>
                        <td><span class="badge {{ $holiday->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($holiday->status) }}</span></td>
                        <td>{{ $holiday->email_sent_at?->format('M j, Y g:i A') ?? 'Not sent' }}</td>
                        <td class="text-end">
                            <a href="{{ route('holidays.show', $holiday) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('holidays.edit', $holiday) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('holidays.toggle', $holiday) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-warning"><i class="bi bi-power"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">No holidays found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($holidays->hasPages())
            <div class="p-3 border-top">{{ $holidays->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</x-app-layout>
