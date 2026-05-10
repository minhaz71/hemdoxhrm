<x-app-layout>
    <x-slot name="title">{{ $staffUser->name }}</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">{{ $staffUser->name }}</h5>
            <small class="text-muted">
                @foreach($staffUser->roles as $r)
                    <span class="badge me-1
                        {{ $r->name === 'admin' ? 'bg-danger-subtle text-danger' :
                          ($r->name === 'hr'    ? 'bg-primary-subtle text-primary' :
                                                   'bg-success-subtle text-success') }}">
                        {{ $r->display_name }}
                    </span>
                @endforeach
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('rbac.staff-users.edit', $staffUser) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="{{ route('rbac.users.permissions', $staffUser) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-shield-lock me-1"></i> Permissions
            </a>
            <a href="{{ route('rbac.staff-users.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    @php $emp = $staffUser->employee; @endphp

    <div class="row g-4">
        {{-- ── Left ─────────────────────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Profile header --}}
            <div class="hrms-card p-4 mb-4">
                <div class="d-flex align-items-center gap-4">
                    @if($emp?->photo)
                        <img src="{{ asset('storage/'.$emp->photo) }}" alt="{{ $staffUser->name }}"
                             style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #e9ecef;flex-shrink:0;">
                    @else
                        <div style="width:90px;height:90px;border-radius:50%;display:flex;align-items:center;
                                    justify-content:center;background:linear-gradient(135deg,#dbeafe,#bfdbfe);
                                    border:3px solid #e9ecef;flex-shrink:0;">
                            <span style="font-size:2rem;font-weight:700;color:#1d4ed8;">
                                {{ strtoupper(substr($staffUser->name,0,1)) }}
                            </span>
                        </div>
                    @endif
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-1">{{ $staffUser->name }}</h5>
                        <div class="text-muted mb-2" style="font-size:.875rem;">{{ $emp?->designation ?? 'No designation' }}</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($staffUser->roles as $r)
                            <span class="badge
                                {{ $r->name === 'admin' ? 'bg-danger-subtle text-danger border border-danger-subtle' :
                                  ($r->name === 'hr'    ? 'bg-primary-subtle text-primary border border-primary-subtle' :
                                                           'bg-success-subtle text-success border border-success-subtle') }}">
                                <i class="bi bi-shield me-1"></i>{{ $r->display_name }}
                            </span>
                            @endforeach
                            @if($staffUser->force_password_reset)
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                <i class="bi bi-key me-1"></i>Reset Required
                            </span>
                            @endif
                            @if($staffUser->login_id)
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                <i class="bi bi-at" style="font-size:.7rem;"></i>{{ $staffUser->login_id }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Account Info --}}
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <i class="bi bi-person-circle me-1"></i>Account Information
                </h6>
                <div class="row g-3" style="font-size:.875rem;">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Email</small>
                        <span class="fw-semibold">{{ $staffUser->email }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">User ID</small>
                        <span class="fw-semibold font-monospace">{{ $staffUser->login_id ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Account Status</small>
                        @if($staffUser->force_password_reset)
                            <span class="badge bg-warning-subtle text-warning">Force Reset Pending</span>
                        @else
                            <span class="badge bg-success-subtle text-success">Active</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Member Since</small>
                        <span class="fw-semibold">{{ $staffUser->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            @if($emp)
            {{-- Personal Info --}}
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <i class="bi bi-person me-1"></i>Personal Information
                </h6>
                <div class="row g-3" style="font-size:.875rem;">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Phone</small>
                        <span class="fw-semibold">{{ $emp->phone ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Date of Birth</small>
                        <span class="fw-semibold">{{ $emp->date_of_birth?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Gender</small>
                        <span class="fw-semibold">{{ ucfirst($emp->gender ?? '—') }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">NID Number</small>
                        <span class="fw-semibold">{{ $emp->nid ?? '—' }}</span>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Address</small>
                        <span class="fw-semibold">{{ $emp->address ?? '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Employment Details --}}
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <i class="bi bi-briefcase me-1"></i>Employment Details
                </h6>
                <div class="row g-3" style="font-size:.875rem;">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Employee Code</small>
                        <span class="fw-semibold font-monospace">{{ $emp->employee_code }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Designation</small>
                        <span class="fw-semibold">{{ $emp->designation ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Department</small>
                        <span class="fw-semibold">{{ $emp->department?->name ?? $emp->department ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Branch</small>
                        <span class="fw-semibold">{{ $emp->branch?->name ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Shift</small>
                        <span class="fw-semibold">{{ $emp->shift?->name ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Join Date</small>
                        <span class="fw-semibold">{{ $emp->join_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Employment Type</small>
                        <span class="fw-semibold">{{ str_replace('_', ' ', ucfirst($emp->employment_type)) }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge {{ $emp->status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                            {{ ucfirst($emp->status) }}
                        </span>
                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- ── Right Sidebar ───────────────────────────────────── --}}
        <div class="col-lg-4">

            @if($emp)
            {{-- Salary --}}
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-2 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    Salary
                </h6>
                <small class="text-muted" style="font-size:.75rem;">Base Salary</small>
                <div class="fs-3 fw-bold text-success mt-1">{{ currency($emp->base_salary) }}</div>
                <small class="text-muted" style="font-size:.73rem;">per month</small>
            </div>
            @endif

            {{-- Quick Actions --}}
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    Quick Actions
                </h6>
                <a href="{{ route('rbac.staff-users.edit', $staffUser) }}"
                   class="btn btn-primary w-100 btn-sm mb-2">
                    <i class="bi bi-pencil me-1"></i> Edit Profile
                </a>
                <a href="{{ route('rbac.users.permissions', $staffUser) }}"
                   class="btn btn-outline-primary w-100 btn-sm mb-2">
                    <i class="bi bi-shield-lock me-1"></i> Manage Permissions
                </a>
                <hr class="my-3">
                {{-- Password Reset --}}
                <p class="small fw-semibold text-muted mb-2">Password Actions</p>
                <form method="POST" action="{{ route('rbac.users.send-reset-link', $staffUser) }}" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-outline-info w-100 btn-sm"
                            onclick="return confirm('Send a password reset link to {{ $staffUser->email }}?')">
                        <i class="bi bi-envelope-check me-1"></i> Send Reset Link
                    </button>
                </form>
                @if($staffUser->force_password_reset)
                <form method="POST" action="{{ route('rbac.users.clear-force-reset', $staffUser) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-success w-100 btn-sm">
                        <i class="bi bi-shield-check me-1"></i> Clear Force Reset Flag
                    </button>
                </form>
                @else
                <form method="POST" action="{{ route('rbac.users.force-reset', $staffUser) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning w-100 btn-sm"
                            onclick="return confirm('Force {{ $staffUser->name }} to change their password on next login?')">
                        <i class="bi bi-shield-exclamation me-1"></i> Force Password Reset
                    </button>
                </form>
                @endif
            </div>

            {{-- Password reset audit --}}
            <div class="hrms-card p-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <i class="bi bi-clock-history me-1"></i>Recent Reset Actions
                </h6>
                @php $logs = $staffUser->passwordResetLogs()->with('admin')->take(5)->get(); @endphp
                @forelse($logs as $log)
                <div class="d-flex gap-2 mb-2 pb-2 border-bottom">
                    <i class="bi {{ $log->action_icon }} text-{{ $log->action_color }} mt-1" style="font-size:.85rem;flex-shrink:0;"></i>
                    <div style="font-size:.78rem;">
                        <div class="fw-semibold">{{ $log->action_label }}</div>
                        <div class="text-muted">
                            By {{ $log->admin?->name ?? 'System' }} ·
                            {{ $log->created_at?->diffForHumans() }}
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-muted small mb-0">No reset actions recorded.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
