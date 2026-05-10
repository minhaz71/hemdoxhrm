<x-app-layout>
    <x-slot name="title">Staff Users</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Staff Users</h5>
            <small class="text-muted">Manage HR, Manager, and Admin accounts</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('rbac.users.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-shield-lock me-1"></i> User Access
            </a>
            <a href="{{ route('rbac.staff-users.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i> Add Staff User
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="hrms-card p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-muted mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Name or email…" value="{{ $search ?? '' }}">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Role</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="">All Staff Roles</option>
                    @foreach($roles as $r)
                    <option value="{{ $r->name }}" {{ ($role ?? '') === $r->name ? 'selected' : '' }}>
                        {{ $r->display_name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if($search || $role)
                <a href="{{ route('rbac.staff-users.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="hrms-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.875rem;">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Roles</th>
                        <th>Designation / Dept.</th>
                        <th>Joined</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    @php
                        $emp = $u->employee;
                        $photo = $emp?->photo ? asset('storage/'.$emp->photo) : null;
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                {{-- Avatar --}}
                                @if($photo)
                                    <img src="{{ $photo }}" alt="{{ $u->name }}"
                                         style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #e9ecef;">
                                @else
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0"
                                         style="width:38px;height:38px;font-size:.75rem;font-weight:700;background:linear-gradient(135deg,#4e9af1,#1d4ed8);">
                                        {{ strtoupper(substr($u->name,0,1)) }}{{ strtoupper(substr(strrchr($u->name,' ') ?: $u->name,1,1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $u->name }}</div>
                                    <div class="text-muted" style="font-size:.78rem;">{{ $u->email }}</div>
                                    @if($u->login_id)
                                    <div style="font-size:.72rem;color:#6c757d;"><i class="bi bi-at"></i>{{ $u->login_id }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @foreach($u->roles as $r)
                                <span class="badge me-1
                                    {{ $r->name === 'admin'   ? 'bg-danger-subtle text-danger' :
                                      ($r->name === 'hr'      ? 'bg-primary-subtle text-primary' :
                                                                 'bg-success-subtle text-success') }}">
                                    {{ $r->display_name }}
                                </span>
                            @endforeach
                        </td>
                        <td>
                            @if($emp)
                                <div style="font-size:.82rem;">{{ $emp->designation ?? '—' }}</div>
                                <div class="text-muted" style="font-size:.75rem;">{{ $emp->department?->name ?? $emp->department ?? '—' }}</div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td style="font-size:.82rem;white-space:nowrap;">
                            {{ $emp?->join_date?->format('d M Y') ?? '—' }}
                        </td>
                        <td class="text-center">
                            @if($u->force_password_reset)
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:.7rem;">
                                    <i class="bi bi-key me-1"></i>Reset Required
                                </span>
                            @elseif(isset($u->status) && $u->status === 'terminated')
                                <span class="badge bg-danger-subtle text-danger" style="font-size:.7rem;">Terminated</span>
                            @else
                                <span class="badge bg-success-subtle text-success" style="font-size:.7rem;">Active</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('rbac.staff-users.show', $u) }}"
                               class="btn btn-sm btn-outline-secondary" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('rbac.staff-users.edit', $u) }}"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-people fs-2 d-block mb-2 opacity-25"></i>
                            @if($search || $role)
                                No staff users match your filters.
                                <a href="{{ route('rbac.staff-users.index') }}">Clear filters</a>
                            @else
                                No staff users yet.
                                <a href="{{ route('rbac.staff-users.create') }}">Create the first one</a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="p-3 border-top">{{ $users->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</x-app-layout>
