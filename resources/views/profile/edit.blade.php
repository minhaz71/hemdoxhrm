<x-app-layout title="My Profile">

@push('styles')
<style>
.avatar-lg {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: var(--hrms-primary);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem;
    font-weight: 700;
    flex-shrink: 0;
}
.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .75rem;
    font-weight: 600;
    text-transform: capitalize;
    letter-spacing: .03em;
}
.role-admin    { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
.role-hr       { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
.role-manager  { background: #ede9fe; color: #5b21b6; border: 1px solid #c4b5fd; }
.role-employee { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.role-default  { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
.info-row {
    display: flex;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
    gap: 12px;
}
.info-row:last-child { border-bottom: none; }
.info-label {
    width: 140px;
    flex-shrink: 0;
    font-size: .8rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: .05em;
    padding-top: 2px;
}
.info-value {
    flex: 1;
    font-size: .9rem;
    color: #1a1f2e;
    word-break: break-word;
}
.profile-nav .nav-link {
    border-radius: 6px;
    color: #6c757d;
    font-size: .875rem;
    padding: 8px 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.profile-nav .nav-link.active {
    background: color-mix(in srgb, var(--hrms-primary) 12%, transparent);
    color: var(--hrms-primary);
    font-weight: 600;
}
.section-heading {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #adb5bd;
    padding: 16px 0 6px;
}
</style>
@endpush

<div class="page-body">

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php
    $employee  = $user->employee;
    $roles     = $user->roles;
    $initials  = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $user->name), 0, 2))));
    $roleNames = $roles->pluck('name');

    $roleColorMap = ['admin'=>'role-admin','hr'=>'role-hr','manager'=>'role-manager','employee'=>'role-employee'];
@endphp

<div class="row g-4">

    {{-- ── Left column: Identity card ─────────────────────────────── --}}
    <div class="col-lg-3">
        <div class="hrms-card p-4 text-center mb-3">
            {{-- Profile photo or initials avatar --}}
            @if($employee?->photo_url)
            <img src="{{ $employee->photo_url }}" alt="{{ $user->name }}"
                 style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e9ecef;margin:0 auto 12px;display:block;">
            @else
            <div class="avatar-lg mx-auto mb-3">{{ $initials }}</div>
            @endif
            <h5 class="fw-bold mb-1" style="font-size:1rem;">{{ $user->name }}</h5>
            <div class="text-muted small mb-3">{{ $user->email }}</div>

            {{-- Role badges --}}
            <div class="d-flex flex-wrap gap-1 justify-content-center mb-3">
                @forelse($roles as $role)
                <span class="role-badge {{ $roleColorMap[$role->name] ?? 'role-default' }}">
                    <i class="bi bi-shield-check" style="font-size:.7rem;"></i>
                    {{ $role->name }}
                </span>
                @empty
                <span class="role-badge role-default">No role</span>
                @endforelse
            </div>

            {{-- Account status --}}
            @php $statusColor = match($user->status ?? 'active') { 'active'=>'success','inactive'=>'warning','terminated'=>'danger', default=>'secondary' }; @endphp
            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} border border-{{ $statusColor }}-subtle">
                <i class="bi bi-circle-fill me-1" style="font-size:.45rem;vertical-align:middle;"></i>
                {{ ucfirst($user->status ?? 'active') }}
            </span>
        </div>

        {{-- Quick nav --}}
        <div class="hrms-card p-2">
            <div class="profile-nav nav flex-column">
                <div class="section-heading px-2">Account</div>
                <a class="nav-link active" id="nav-info-tab" data-bs-toggle="pill" href="#tab-info">
                    <i class="bi bi-person"></i> Personal Info
                </a>
                <a class="nav-link" id="nav-password-tab" data-bs-toggle="pill" href="#tab-password">
                    <i class="bi bi-lock"></i> Change Password
                </a>
                @if($employee)
                <div class="section-heading px-2">Employment</div>
                <a class="nav-link" id="nav-emp-tab" data-bs-toggle="pill" href="#tab-emp">
                    <i class="bi bi-briefcase"></i> Employment Details
                </a>
                @endif
                @if($user->isAdmin())
                <div class="section-heading px-2">Administration</div>
                <a class="nav-link" href="{{ route('settings.index') }}">
                    <i class="bi bi-gear"></i> System Settings
                </a>
                <a class="nav-link" href="{{ route('company.index') }}">
                    <i class="bi bi-building"></i> Company Profile
                </a>
                <a class="nav-link" href="{{ route('rbac.roles.index') }}">
                    <i class="bi bi-shield-lock"></i> Roles & Permissions
                </a>
                <a class="nav-link" href="{{ route('menus.index') }}">
                    <i class="bi bi-list-nested"></i> Menu Manager
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Right column: Tab content ───────────────────────────────── --}}
    <div class="col-lg-9">
        <div class="tab-content">

            {{-- ── Personal Info ──────────────────────────────────── --}}
            <div class="tab-pane fade show active" id="tab-info">
                <div class="hrms-card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle text-primary"></i>
                        Personal Information
                    </div>
                    <div class="p-4">
                        <form method="POST" action="{{ route('profile.update') }}">
                            @csrf @method('PATCH')
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $user->name) }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
	                                <div class="col-md-6">
	                                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
	                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
	                                           value="{{ old('email', $user->email) }}" required>
                                        <div class="form-text">Primary email is used for login and Time Doctor matching.</div>
	                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
	                                </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Alternate Email</label>
                                        <input type="email" name="alternate_email" class="form-control @error('alternate_email') is-invalid @enderror"
                                               value="{{ old('alternate_email', $user->alternate_email) }}" placeholder="personal@example.com">
                                        <div class="form-text">Notification-only. Workplace emails are sent to both addresses when provided.</div>
                                        @error('alternate_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                {{-- Account metadata (read-only) --}}
                                <div class="col-12 mt-2">
                                    <hr class="my-2">
                                    <p class="text-muted small mb-3">Account information (read-only)</p>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <div class="bg-light rounded p-3">
                                                <div class="text-muted" style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Roles</div>
                                                <div class="mt-1 d-flex flex-wrap gap-1">
                                                    @foreach($roles as $r)
                                                    <span class="role-badge {{ $roleColorMap[$r->name] ?? 'role-default' }}">{{ $r->name }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="bg-light rounded p-3">
                                                <div class="text-muted" style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Member Since</div>
                                                <div class="mt-1 fw-semibold" style="font-size:.875rem;">{{ $user->created_at->format('d M Y') }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="bg-light rounded p-3">
                                                <div class="text-muted" style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Account Status</div>
                                                <div class="mt-1">
                                                    <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ ucfirst($user->status ?? 'active') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mt-2">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-floppy me-1"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ── Change Password ─────────────────────────────────── --}}
            <div class="tab-pane fade" id="tab-password">
                <div class="hrms-card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-lock text-primary"></i>
                        Change Password
                    </div>
                    <div class="p-4">
                        <form method="POST" action="{{ route('profile.password') }}">
                            @csrf @method('PATCH')
                            <div class="row g-3" style="max-width:480px;">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                                    <input type="password" name="current_password"
                                           class="form-control @error('current_password') is-invalid @enderror"
                                           autocomplete="current-password" required>
                                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           autocomplete="new-password" required>
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">Minimum 8 characters.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password_confirmation"
                                           class="form-control" autocomplete="new-password" required>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-warning px-4">
                                        <i class="bi bi-lock me-1"></i> Change Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ── Employment Details ──────────────────────────────── --}}
            @if($employee)
            <div class="tab-pane fade" id="tab-emp">
                <div class="hrms-card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-briefcase text-primary"></i>
                            Employment Details
                        </div>
                        @php
                            $empStatus = match($employee->status) {
                                'active'     => ['bg-success', 'Active'],
                                'inactive'   => ['bg-warning', 'Inactive'],
                                'terminated' => ['bg-danger',  'Terminated'],
                                default      => ['bg-secondary','Unknown'],
                            };
                        @endphp
                        <span class="badge {{ $empStatus[0] }}">{{ $empStatus[1] }}</span>
                    </div>
                    <div class="p-4">

                        {{-- Identity --}}
                        <p class="section-heading" style="margin-top:0;">Employee Identity</p>
                        <div class="info-row">
                            <div class="info-label">System Code</div>
                            <div class="info-value"><code>{{ $employee->employee_code }}</code></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Employee Code</div>
                            <div class="info-value">{{ $employee->organization_employee_code ?: '—' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Full Name</div>
                            <div class="info-value fw-semibold">{{ $employee->full_name }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone</div>
                            <div class="info-value">{{ $employee->phone ?: '—' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value">{{ $employee->date_of_birth ? $employee->date_of_birth->format('d M Y') : '—' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Gender</div>
                            <div class="info-value">{{ $employee->gender ? ucfirst($employee->gender) : '—' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Address</div>
                            <div class="info-value">{{ $employee->address ?: '—' }}</div>
                        </div>

                        {{-- Employment --}}
                        <p class="section-heading">Employment</p>
                        <div class="info-row">
                            <div class="info-label">Designation</div>
                            <div class="info-value">
                                {{ $employee->designationModel?->name ?? $employee->designation ?? '—' }}
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Department</div>
                            <div class="info-value">
                                {{ $employee->department?->name ?? $employee->department ?? '—' }}
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Branch</div>
                            <div class="info-value">
                                @if($employee->branch)
                                    {{ $employee->branch->name }}
                                    @if($employee->branch->is_headquarters)
                                    <span class="badge bg-warning-subtle text-warning ms-1" style="font-size:.65rem;">HQ</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Shift</div>
                            <div class="info-value">
                                @if($employee->shift)
                                <span class="d-inline-flex align-items-center gap-1">
                                    <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:{{ $employee->shift->color }}"></span>
                                    {{ $employee->shift->name }}
                                    <span class="text-muted small">({{ substr($employee->shift->start_time,0,5) }}–{{ substr($employee->shift->end_time,0,5) }})</span>
                                </span>
                                @else
                                —
                                @endif
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Join Date</div>
                            <div class="info-value">{{ $employee->join_date->format('d M Y') }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Employment Type</div>
                            <div class="info-value">
                                @php
                                    $typeMap = ['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract'];
                                @endphp
                                {{ $typeMap[$employee->employment_type] ?? $employee->employment_type }}
                            </div>
                        </div>
                        @if($user->isAdmin() || $user->isHR())
                        <div class="info-row">
                            <div class="info-label">Base Salary</div>
                            <div class="info-value fw-semibold">
                                {{ currency_symbol() }} {{ number_format($employee->base_salary, 2) }}
                            </div>
                        </div>
                        @endif

                        {{-- Identity Documents --}}
                        @if($employee->nid || $employee->nid_document || $employee->certificate || $employee->login_id)
                        <p class="section-heading">Identity &amp; Documents</p>
                        @if($employee->login_id)
                        <div class="info-row">
                            <div class="info-label">User ID</div>
                            <div class="info-value"><code>{{ $employee->login_id }}</code></div>
                        </div>
                        @endif
                        @if($employee->nid)
                        <div class="info-row">
                            <div class="info-label">NID Number</div>
                            <div class="info-value">{{ $employee->nid }}</div>
                        </div>
                        @endif
                        @if($employee->nid_document || $employee->certificate)
                        <div class="info-row">
                            <div class="info-label">Documents</div>
                            <div class="info-value d-flex flex-wrap gap-2">
                                @if($employee->nid_document)
                                <a href="{{ asset('storage/'.$employee->nid_document) }}" target="_blank"
                                   class="btn btn-outline-danger btn-sm" style="font-size:.78rem;">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>NID Document
                                </a>
                                @endif
                                @if($employee->certificate)
                                <a href="{{ asset('storage/'.$employee->certificate) }}" target="_blank"
                                   class="btn btn-outline-primary btn-sm" style="font-size:.78rem;">
                                    <i class="bi bi-file-earmark-text me-1"></i>Certificate
                                </a>
                                @endif
                            </div>
                        </div>
                        @endif
                        @endif

                    </div>
                </div>
            </div>
            @endif

        </div>{{-- /tab-content --}}
    </div>{{-- /col --}}

</div>{{-- /row --}}
</div>{{-- /page-body --}}

@push('scripts')
<script>
// Activate tab from anchor or session
(function () {
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`[href="${hash}"]`);
        if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
    }
    // Keep URL in sync
    document.querySelectorAll('.profile-nav [data-bs-toggle="pill"]').forEach(el => {
        el.addEventListener('shown.bs.tab', e => {
            history.replaceState(null, '', e.target.getAttribute('href'));
        });
    });

    // If there were password errors, open that tab
    @if($errors->hasAny(['current_password','password']))
    const pwTab = document.getElementById('nav-password-tab');
    if (pwTab) bootstrap.Tab.getOrCreateInstance(pwTab).show();
    @endif

    // If there were name/email errors, stay on info tab (already active)
})();
</script>
@endpush

</x-app-layout>
