<x-app-layout>
    <x-slot name="title">Weekly Offs</x-slot>
    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Weekly Off Management</h5>
            <small class="text-muted">Assign employee-specific weekly off days.</small>
        </div>
        <a href="{{ route('weekly-offs.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Assign Weekly Off</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="hrms-card p-3">
                <form class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select">
                            <option value="">All employees</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Day</label>
                        <select name="day_of_week" class="form-select">
                            <option value="">All days</option>
                            @foreach($days as $day)
                                <option value="{{ $day }}" @selected(request('day_of_week') === $day)>{{ ucfirst($day) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="hrms-card p-3">
                <form method="POST" action="{{ route('weekly-offs.defaults') }}">
                    @csrf
                    <label class="form-label">Default Company Weekly Off</label>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach($days as $day)
                            <label class="border rounded px-2 py-1 small">
                                <input type="checkbox" name="default_weekly_off_days[]" value="{{ $day }}" @checked(in_array($day, $defaultDays, true))>
                                {{ ucfirst($day) }}
                            </label>
                        @endforeach
                    </div>
                    <button class="btn btn-sm btn-primary w-100">Save Default</button>
                </form>
            </div>
        </div>
    </div>

    <div class="hrms-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Day</th>
                        <th>Effective Range</th>
                        <th>Status</th>
                        <th>Assigned By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($weeklyOffs as $weeklyOff)
                        <tr>
                            <td><div class="fw-semibold">{{ $weeklyOff->employee->full_name }}</div><small class="text-muted">{{ $weeklyOff->employee->employee_code }}</small></td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ ucfirst($weeklyOff->day_of_week) }}</span></td>
                            <td>{{ $weeklyOff->effective_from->format('M j, Y') }} - {{ $weeklyOff->effective_to?->format('M j, Y') ?? 'Open' }}</td>
                            <td><span class="badge {{ $weeklyOff->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($weeklyOff->status) }}</span></td>
                            <td>
                                {{ $weeklyOff->assignedBy?->name ?? '—' }}
                                @if($weeklyOff->note)
                                    <i class="bi bi-chat-left-text text-muted ms-1"
                                       title="{{ $weeklyOff->note }}"
                                       data-bs-toggle="tooltip"
                                       style="cursor:default;"></i>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('weekly-offs.edit', $weeklyOff) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('weekly-offs.destroy', $weeklyOff) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-warning" onclick="return confirm('Deactivate this weekly off?')"><i class="bi bi-power"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted">No weekly off assignments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($weeklyOffs->hasPages())
            <div class="p-3 border-top">{{ $weeklyOffs->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</x-app-layout>
