<x-app-layout>
    <x-slot name="title">{{ $employee->full_name }}</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">{{ $employee->full_name }}</h5>
            <small class="text-muted">
                System code: {{ $employee->employee_code }}
                @if($employee->organization_employee_code) · Employee code: {{ $employee->organization_employee_code }}@endif
                @if($employee->designation) · {{ $employee->designation }}@endif
                @if($employee->department) · {{ $employee->department }}@endif
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('employees.education.index', $employee) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-mortarboard me-1"></i> Education
            </a>
            <a href="{{ route('attendance.history', $employee) }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-clock-history me-1"></i> Attendance
            </a>
            @if(auth()->user()->employee?->id === $employee->id)
            <a href="{{ route('payroll.my') }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-cash-stack me-1"></i> Salary
            </a>
            @endif
            @if(auth()->user()->isAdmin() || auth()->user()->isHR())
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            @endif
        </div>
    </div>

    <div class="row g-4">

        {{-- ── Left: main content ────────────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Profile header --}}
            <div class="hrms-card p-4 mb-4">
                <div class="d-flex align-items-center gap-4">
                    @if($employee->photo_url)
                    <img src="{{ $employee->photo_url }}" alt="{{ $employee->full_name }}"
                         style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid #e9ecef;flex-shrink:0;">
                    @else
                    <div style="width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,#dbeafe,#bfdbfe);display:flex;align-items:center;justify-content:center;border:3px solid #e9ecef;flex-shrink:0;">
                        <span style="font-size:2rem;font-weight:700;color:#1d4ed8;">
                            {{ strtoupper(substr($employee->first_name,0,1).substr($employee->last_name,0,1)) }}
                        </span>
                    </div>
                    @endif
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-1">{{ $employee->full_name }}</h5>
                        <div class="text-muted" style="font-size:.875rem;">{{ $employee->designation ?? '' }}</div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @if($employee->status === 'active')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            @elseif($employee->status === 'terminated')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Terminated</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Inactive</span>
                            @endif
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                {{ str_replace('_', ' ', ucfirst($employee->employment_type)) }}
                            </span>
                            @if($employee->login_id)
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                <i class="bi bi-at" style="font-size:.7rem;"></i>{{ $employee->login_id }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Personal Information --}}
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <i class="bi bi-person me-1"></i>Personal Information
                </h6>
                <div class="row g-3" style="font-size:.875rem;">
                    <div class="col-md-6">
                        <small class="text-muted d-block">System Code</small>
                        <span class="fw-semibold"><code>{{ $employee->employee_code }}</code></span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Employee Code</small>
                        <span class="fw-semibold">{{ $employee->organization_employee_code ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Email</small>
                        <span class="fw-semibold">{{ $employee->user?->email ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Phone</small>
                        <span class="fw-semibold">{{ $employee->phone ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Gender</small>
                        <span class="fw-semibold">{{ ucfirst($employee->gender ?? '—') }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Date of Birth</small>
                        <span class="fw-semibold">{{ $employee->date_of_birth?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Address</small>
                        <span class="fw-semibold">{{ $employee->address ?? '—' }}</span>
                    </div>
                    @if($employee->nid)
                    <div class="col-md-6">
                        <small class="text-muted d-block">NID Number</small>
                        <span class="fw-semibold">{{ $employee->nid }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Employment Details --}}
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <i class="bi bi-briefcase me-1"></i>Employment Details
                </h6>
                <div class="row g-3" style="font-size:.875rem;">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Designation</small>
                        <span class="fw-semibold">{{ $employee->designation ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Department</small>
                        <span class="fw-semibold">{{ $employee->department ?? '—' }}</span>
                    </div>
                    @if($employee->branch)
                    <div class="col-md-6">
                        <small class="text-muted d-block">Branch</small>
                        <span class="fw-semibold">{{ $employee->branch->name }}</span>
                    </div>
                    @endif
                    @if($employee->shift)
                    <div class="col-md-6">
                        <small class="text-muted d-block">Shift</small>
                        <span class="fw-semibold">{{ $employee->shift->name }}</span>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <small class="text-muted d-block">Team Leader</small>
                        <span class="fw-semibold">{{ $employee->teamLeader?->full_name ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Join Date</small>
                        <span class="fw-semibold">{{ $employee->join_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Employment Type</small>
                        <span class="fw-semibold">{{ str_replace('_', ' ', ucfirst($employee->employment_type)) }}</span>
                    </div>
                </div>
            </div>

            {{-- Documents --}}
            @if($employee->nid_document || $employee->certificate)
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <i class="bi bi-file-earmark-text me-1"></i>Documents
                </h6>
                <div class="d-flex flex-wrap gap-3">
                    @if($employee->nid_document)
                    <a href="{{ asset('storage/'.$employee->nid_document) }}" target="_blank"
                       class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i> NID Document
                    </a>
                    @endif
                    @if($employee->certificate)
                    <a href="{{ asset('storage/'.$employee->certificate) }}" target="_blank"
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-file-earmark-text me-1"></i> Education Certificate
                    </a>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- ── Right sidebar ─────────────────────────────────────────── --}}
        <div class="col-lg-4">

            {{-- Salary --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isHR() || auth()->user()->employee?->id === $employee->id)
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    Salary
                </h6>
                <small class="text-muted" style="font-size:.75rem;">Current Base Salary</small>
                <div class="fs-3 fw-bold text-success mt-1">{{ currency($employee->base_salary) }}</div>
                <small class="text-muted" style="font-size:.73rem;">per month</small>

                @php $histories = $employee->salaryHistories()->limit(5)->get(); @endphp
                @if($histories->count() > 1)
                <hr class="my-3">
                <small class="text-muted d-block mb-2" style="font-size:.73rem;">Salary History</small>
                @foreach($histories as $hist)
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.8rem;">
                    <span class="text-muted">
                        {{ $hist->effective_from->format('M Y') }}
                        @if($hist->effective_to) – {{ $hist->effective_to->format('M Y') }} @else – present @endif
                    </span>
                    <span class="fw-semibold text-success">{{ currency($hist->base_salary) }}</span>
                </div>
                @endforeach
                @endif
            </div>
            @endif

            {{-- Login Account (admin/hr only) --}}
            @if($employee->user && (auth()->user()->isAdmin() || auth()->user()->isHR()))
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    Login Account
                </h6>
                <small class="text-muted" style="font-size:.75rem;">Email</small>
                <div class="fw-semibold mb-2" style="font-size:.875rem;">{{ $employee->user->email }}</div>
                @if($employee->login_id)
                <small class="text-muted" style="font-size:.75rem;">User ID</small>
                <div class="fw-semibold mb-2" style="font-size:.875rem;font-family:monospace;">{{ $employee->login_id }}</div>
                @endif
                <small class="text-muted" style="font-size:.75rem;">Roles</small><br>
                <div class="mt-1">
                    @foreach($employee->user->roles as $role)
                        <span class="badge bg-primary-subtle text-primary me-1">{{ $role->display_name }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="hrms-card p-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    Quick Actions
                </h6>
                @if(auth()->user()->isAdmin() || auth()->user()->isHR())
                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary w-100 mb-2 btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit Profile
                </a>
                @endif
                <a href="{{ route('employees.education.index', $employee) }}" class="btn btn-outline-primary w-100 mb-2 btn-sm">
                    <i class="bi bi-mortarboard me-1"></i> Education Records
                </a>
                <a href="{{ route('attendance.history', $employee) }}" class="btn btn-outline-info w-100 mb-2 btn-sm">
                    <i class="bi bi-clock-history me-1"></i> Attendance History
                </a>
                @if(auth()->user()->employee?->id === $employee->id)
                <a href="{{ route('payroll.my') }}" class="btn btn-outline-success w-100 mb-2 btn-sm">
                    <i class="bi bi-cash-stack me-1"></i> Salary History
                </a>
                <a href="{{ route('payslips.my') }}" class="btn btn-outline-danger w-100 mb-2 btn-sm">
                    <i class="bi bi-receipt me-1"></i> My Payslips
                </a>
                @endif

                @if((auth()->user()->isAdmin() || auth()->user()->isHR()) && $employee->status !== 'terminated')
                <hr class="my-3">
                <form method="POST" action="{{ route('employees.terminate', $employee) }}"
                      onsubmit="return confirm('Terminate {{ $employee->full_name }}? This will disable their login immediately.')">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                        <i class="bi bi-person-x me-1"></i> Terminate Employee
                    </button>
                </form>
                @endif
            </div>

        </div>
    </div>

</x-app-layout>
