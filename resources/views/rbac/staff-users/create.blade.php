<x-app-layout>
    <x-slot name="title">Add Staff User</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Add Staff User</h5>
            <small class="text-muted">Create an HR, Manager, or Admin account with full employee profile</small>
        </div>
        <a href="{{ route('rbac.staff-users.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('rbac.staff-users.store') }}" enctype="multipart/form-data" id="staffForm">
        @csrf
        <div class="row g-4">

            {{-- ── LEFT COLUMN ────────────────────────────────────── --}}
            <div class="col-lg-8">

                {{-- Section 1: Account Info --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="bi bi-person-circle me-1"></i> Account Information
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">
                                First Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="first_name"
                                   class="form-control @error('first_name') is-invalid @enderror"
                                   value="{{ old('first_name') }}" required placeholder="John">
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">
                                Last Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="last_name"
                                   class="form-control @error('last_name') is-invalid @enderror"
                                   value="{{ old('last_name') }}" required placeholder="Doe">
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required placeholder="john@company.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">
                                User ID (login_id)
                                <span class="text-muted fw-normal small">optional</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-at"></i></span>
                                <input type="text" name="login_id" id="loginIdInput"
                                       class="form-control @error('login_id') is-invalid @enderror"
                                       value="{{ old('login_id') }}" placeholder="johndoe"
                                       autocomplete="off">
                                <span class="input-group-text" id="loginIdStatus" style="min-width:36px;"></span>
                                @error('login_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div id="loginIdMsg" class="form-text"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">
                                Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" name="password" id="pwd"
                                       class="form-control @error('password') is-invalid @enderror"
                                       required placeholder="Min 8, upper + lower + number">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('pwd',this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">
                                Confirm Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" name="password_confirmation" id="pwdConfirm"
                                       class="form-control" required placeholder="Repeat password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('pwdConfirm',this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="force_password_reset"
                                       id="forceReset" value="1" {{ old('force_password_reset') ? 'checked' : '' }}>
                                <label class="form-check-label small" for="forceReset">
                                    <i class="bi bi-shield-exclamation me-1 text-warning"></i>
                                    Require password change on first login
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Personal Details --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="bi bi-person me-1"></i> Personal Details
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Phone</label>
                            <input type="text" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}" placeholder="+880 17xx-xxxxxx">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Date of Birth</label>
                            <input type="date" name="date_of_birth"
                                   class="form-control @error('date_of_birth') is-invalid @enderror"
                                   value="{{ old('date_of_birth') }}">
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Gender</label>
                            <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                <option value="">Select…</option>
                                <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other"  {{ old('gender') === 'other'  ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">NID Number</label>
                            <input type="text" name="nid"
                                   class="form-control @error('nid') is-invalid @enderror"
                                   value="{{ old('nid') }}" placeholder="National ID">
                            @error('nid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Address</label>
                            <textarea name="address" rows="2"
                                      class="form-control @error('address') is-invalid @enderror"
                                      placeholder="Full address…">{{ old('address') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Section 3: Employment Details --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="bi bi-briefcase me-1"></i> Employment Details
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Designation</label>
                            <select name="designation_id" class="form-select @error('designation_id') is-invalid @enderror">
                                <option value="">Select designation…</option>
                                @foreach($designations as $d)
                                <option value="{{ $d->id }}" {{ old('designation_id') == $d->id ? 'selected' : '' }}>
                                    {{ $d->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Department</label>
                            <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">Select department…</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if($branchesEnabled)
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Branch</label>
                            <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">Select branch…</option>
                                @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}{{ $b->is_headquarters ? ' (HQ)' : '' }}
                                </option>
                                @endforeach
                            </select>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Shift</label>
                            <select name="shift_id" class="form-select @error('shift_id') is-invalid @enderror">
                                <option value="">Select shift…</option>
                                @foreach($shifts as $s)
                                <option value="{{ $s->id }}" {{ old('shift_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('shift_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Join Date</label>
                            <input type="date" name="join_date"
                                   class="form-control @error('join_date') is-invalid @enderror"
                                   value="{{ old('join_date', date('Y-m-d')) }}">
                            @error('join_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Employment Type</label>
                            <select name="employment_type" class="form-select @error('employment_type') is-invalid @enderror">
                                <option value="full_time"  {{ old('employment_type','full_time') === 'full_time'  ? 'selected' : '' }}>Full Time</option>
                                <option value="part_time"  {{ old('employment_type') === 'part_time'  ? 'selected' : '' }}>Part Time</option>
                                <option value="contract"   {{ old('employment_type') === 'contract'   ? 'selected' : '' }}>Contract</option>
                                <option value="internship" {{ old('employment_type') === 'internship' ? 'selected' : '' }}>Internship</option>
                            </select>
                            @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── RIGHT COLUMN ───────────────────────────────────── --}}
            <div class="col-lg-4">

                {{-- Photo --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="bi bi-image me-1"></i> Profile Photo
                    </h6>
                    <div class="text-center mb-3">
                        <div id="photoPreview"
                             style="width:100px;height:100px;border-radius:50%;margin:0 auto;
                                    background:#e9ecef;display:flex;align-items:center;justify-content:center;
                                    overflow:hidden;border:3px solid #dee2e6;cursor:pointer;"
                             onclick="document.getElementById('photoInput').click()">
                            <i class="bi bi-person-circle text-muted" style="font-size:2.8rem;"></i>
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="document.getElementById('photoInput').click()">
                                <i class="bi bi-upload me-1"></i> Upload Photo
                            </button>
                        </div>
                    </div>
                    <input type="file" id="photoInput" name="photo" accept="image/jpg,image/jpeg,image/png"
                           class="d-none @error('photo') is-invalid @enderror"
                           onchange="previewPhoto(this)">
                    @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <small class="text-muted d-block text-center">JPG or PNG, max 2MB</small>
                </div>

                {{-- Role Assignment --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="bi bi-shield-check me-1"></i> Role Assignment <span class="text-danger">*</span>
                    </h6>
                    @error('role_ids')
                    <div class="alert alert-danger py-2 px-3 small mb-3">{{ $message }}</div>
                    @enderror
                    @foreach($roles as $r)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox"
                               name="role_ids[]" value="{{ $r->id }}"
                               id="role_{{ $r->id }}"
                               {{ (is_array(old('role_ids')) && in_array($r->id, old('role_ids')))
                                  || ($r->name === 'hr' && !old('role_ids'))
                                  ? 'checked' : '' }}>
                        <label class="form-check-label" for="role_{{ $r->id }}">
                            <span class="badge me-1
                                {{ $r->name === 'admin'   ? 'bg-danger-subtle text-danger' :
                                  ($r->name === 'hr'      ? 'bg-primary-subtle text-primary' :
                                                             'bg-success-subtle text-success') }}">
                                {{ $r->display_name }}
                            </span>
                        </label>
                    </div>
                    @endforeach
                    <small class="text-muted">Select at least one role. "HR" is selected by default.</small>
                </div>

                {{-- Salary --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="bi bi-cash-stack me-1"></i> Salary
                    </h6>
                    <label class="form-label fw-semibold" style="font-size:.875rem;">Base Salary (per month)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">{{ config('app.currency_symbol', '৳') }}</span>
                        <input type="number" name="base_salary" step="0.01" min="0"
                               class="form-control @error('base_salary') is-invalid @enderror"
                               value="{{ old('base_salary', 0) }}" placeholder="0.00">
                        @error('base_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i> Create Staff User
                    </button>
                    <a href="{{ route('rbac.staff-users.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>

            </div>
        </div>
    </form>

    @push('scripts')
    <script>
    // Photo preview
    function previewPhoto(input) {
        if (!input.files || !input.files[0]) return;
        const reader = new FileReader();
        reader.onload = e => {
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }

    // Password toggle
    function togglePwd(id, btn) {
        const input = document.getElementById(id);
        const icon  = btn.querySelector('i');
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
    }

    // Login ID availability check
    const loginIdInput  = document.getElementById('loginIdInput');
    const loginIdStatus = document.getElementById('loginIdStatus');
    const loginIdMsg    = document.getElementById('loginIdMsg');
    let loginIdTimer;

    loginIdInput?.addEventListener('input', function () {
        clearTimeout(loginIdTimer);
        const val = this.value.trim();
        loginIdStatus.innerHTML = '';
        loginIdMsg.textContent  = '';
        if (!val) return;

        loginIdStatus.innerHTML = '<span class="spinner-border spinner-border-sm text-muted"></span>';

        loginIdTimer = setTimeout(() => {
            fetch(`/check-login-id?login_id=${encodeURIComponent(val)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.available) {
                    loginIdStatus.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                    loginIdMsg.innerHTML    = '<span class="text-success small">Available</span>';
                    loginIdInput.classList.remove('is-invalid');
                } else {
                    loginIdStatus.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
                    loginIdMsg.innerHTML    = '<span class="text-danger small">Already taken</span>';
                    loginIdInput.classList.add('is-invalid');
                }
            })
            .catch(() => { loginIdStatus.innerHTML = ''; });
        }, 500);
    });
    </script>
    @endpush
</x-app-layout>
