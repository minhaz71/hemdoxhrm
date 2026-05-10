<x-app-layout>
    <x-slot name="title">Edit — {{ $staffUser->name }}</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Edit Staff User</h5>
            <small class="text-muted">{{ $staffUser->name }} · {{ $staffUser->email }}</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('rbac.staff-users.show', $staffUser) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    @php $emp = $staffUser->employee; @endphp

    <form method="POST" action="{{ route('rbac.staff-users.update', $staffUser) }}"
          enctype="multipart/form-data" id="staffEditForm">
        @csrf
        @method('PATCH')

        <div class="row g-4">

            {{-- ── LEFT COLUMN ─────────────────────────────────── --}}
            <div class="col-lg-8">

                {{-- Account Info --}}
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
                                   value="{{ old('first_name', $emp?->first_name ?? explode(' ', $staffUser->name)[0]) }}" required>
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">
                                Last Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="last_name"
                                   class="form-control @error('last_name') is-invalid @enderror"
                                   value="{{ old('last_name', $emp?->last_name ?? (explode(' ', $staffUser->name)[1] ?? '')) }}" required>
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $staffUser->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">
                                User ID (login_id)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-at"></i></span>
                                <input type="text" name="login_id" id="loginIdInput"
                                       class="form-control @error('login_id') is-invalid @enderror"
                                       value="{{ old('login_id', $staffUser->login_id) }}"
                                       autocomplete="off">
                                <span class="input-group-text" id="loginIdStatus"></span>
                                @error('login_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div id="loginIdMsg" class="form-text"></div>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-light border py-2 px-3 mb-0" style="font-size:.83rem;">
                                <i class="bi bi-info-circle me-1 text-primary"></i>
                                Leave password fields empty to keep the current password.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">New Password</label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="pwd"
                                       class="form-control @error('new_password') is-invalid @enderror"
                                       placeholder="Leave blank to keep current">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('pwd',this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" name="new_password_confirmation" id="pwdConfirm"
                                       class="form-control" placeholder="Repeat new password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('pwdConfirm',this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="force_password_reset"
                                       id="forceReset" value="1"
                                       {{ old('force_password_reset', $staffUser->force_password_reset) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="forceReset">
                                    <i class="bi bi-shield-exclamation me-1 text-warning"></i>
                                    Require password change on next login
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Personal Details --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="bi bi-person me-1"></i> Personal Details
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Phone</label>
                            <input type="text" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $emp?->phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Date of Birth</label>
                            <input type="date" name="date_of_birth"
                                   class="form-control @error('date_of_birth') is-invalid @enderror"
                                   value="{{ old('date_of_birth', $emp?->date_of_birth?->format('Y-m-d')) }}">
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Gender</label>
                            <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                <option value="">Select…</option>
                                @foreach(['male'=>'Male','female'=>'Female','other'=>'Other'] as $val => $label)
                                <option value="{{ $val }}" {{ old('gender', $emp?->gender) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">NID Number</label>
                            <input type="text" name="nid"
                                   class="form-control @error('nid') is-invalid @enderror"
                                   value="{{ old('nid', $emp?->nid) }}">
                            @error('nid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Address</label>
                            <textarea name="address" rows="2"
                                      class="form-control @error('address') is-invalid @enderror">{{ old('address', $emp?->address) }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Employment --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="bi bi-briefcase me-1"></i> Employment Details
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Designation</label>
                            <select name="designation_id" class="form-select @error('designation_id') is-invalid @enderror">
                                <option value="">Select…</option>
                                @foreach($designations as $d)
                                <option value="{{ $d->id }}" {{ old('designation_id', $emp?->designation_id) == $d->id ? 'selected' : '' }}>
                                    {{ $d->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Department</label>
                            <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">Select…</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $emp?->department_id) == $dept->id ? 'selected' : '' }}>
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
                                <option value="">Select…</option>
                                @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ old('branch_id', $emp?->branch_id) == $b->id ? 'selected' : '' }}>
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
                                <option value="">Select…</option>
                                @foreach($shifts as $s)
                                <option value="{{ $s->id }}" {{ old('shift_id', $emp?->shift_id) == $s->id ? 'selected' : '' }}>
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
                                   value="{{ old('join_date', $emp?->join_date?->format('Y-m-d')) }}">
                            @error('join_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.875rem;">Employment Type</label>
                            <select name="employment_type" class="form-select @error('employment_type') is-invalid @enderror">
                                @foreach(['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','internship'=>'Internship'] as $val => $label)
                                <option value="{{ $val }}" {{ old('employment_type', $emp?->employment_type ?? 'full_time') === $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                            @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── RIGHT COLUMN ─────────────────────────────────── --}}
            <div class="col-lg-4">

                {{-- Photo --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="bi bi-image me-1"></i> Profile Photo
                    </h6>
                    <div class="text-center mb-3">
                        <div id="photoPreview"
                             style="width:100px;height:100px;border-radius:50%;margin:0 auto;
                                    overflow:hidden;border:3px solid #dee2e6;cursor:pointer;
                                    background:#e9ecef;display:flex;align-items:center;justify-content:center;"
                             onclick="document.getElementById('photoInput').click()">
                            @if($emp?->photo)
                                <img src="{{ asset('storage/'.$emp->photo) }}" alt="Photo"
                                     style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <i class="bi bi-person-circle text-muted" style="font-size:2.8rem;"></i>
                            @endif
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2"
                                onclick="document.getElementById('photoInput').click()">
                            <i class="bi bi-upload me-1"></i>
                            {{ $emp?->photo ? 'Change Photo' : 'Upload Photo' }}
                        </button>
                    </div>
                    <input type="file" id="photoInput" name="photo" accept="image/jpg,image/jpeg,image/png"
                           class="d-none @error('photo') is-invalid @enderror"
                           onchange="previewPhoto(this)">
                    @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <small class="text-muted d-block text-center">JPG or PNG, max 2MB</small>
                </div>

                {{-- Roles --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="bi bi-shield-check me-1"></i> Role Assignment <span class="text-danger">*</span>
                    </h6>
                    @error('role_ids')
                    <div class="alert alert-danger py-2 px-3 small mb-3">{{ $message }}</div>
                    @enderror
                    @php $currentRoleIds = old('role_ids', $staffUser->roles->pluck('id')->toArray()); @endphp
                    @foreach($roles as $r)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox"
                               name="role_ids[]" value="{{ $r->id }}"
                               id="role_{{ $r->id }}"
                               {{ in_array($r->id, array_map('intval', (array)$currentRoleIds)) ? 'checked' : '' }}>
                        <label class="form-check-label" for="role_{{ $r->id }}">
                            <span class="badge
                                {{ $r->name === 'admin' ? 'bg-danger-subtle text-danger' :
                                  ($r->name === 'hr'    ? 'bg-primary-subtle text-primary' :
                                                           'bg-success-subtle text-success') }}">
                                {{ $r->display_name }}
                            </span>
                        </label>
                    </div>
                    @endforeach
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
                               value="{{ old('base_salary', $emp?->base_salary ?? 0) }}">
                        @error('base_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i> Save Changes
                    </button>
                    <a href="{{ route('rbac.staff-users.show', $staffUser) }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>

            </div>
        </div>
    </form>

    @push('scripts')
    <script>
    function previewPhoto(input) {
        if (!input.files || !input.files[0]) return;
        const reader = new FileReader();
        reader.onload = e => {
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }

    function togglePwd(id, btn) {
        const input = document.getElementById(id);
        const icon  = btn.querySelector('i');
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
    }

    // Login ID availability check (skip current user's own login_id)
    const loginIdInput  = document.getElementById('loginIdInput');
    const loginIdStatus = document.getElementById('loginIdStatus');
    const loginIdMsg    = document.getElementById('loginIdMsg');
    const currentLoginId = '{{ $staffUser->login_id }}';
    let loginIdTimer;

    loginIdInput?.addEventListener('input', function () {
        clearTimeout(loginIdTimer);
        const val = this.value.trim();
        loginIdStatus.innerHTML = '';
        loginIdMsg.textContent  = '';
        if (!val || val === currentLoginId) return;

        loginIdStatus.innerHTML = '<span class="spinner-border spinner-border-sm text-muted"></span>';

        loginIdTimer = setTimeout(() => {
            fetch(`/check-login-id?login_id=${encodeURIComponent(val)}&exclude={{ $staffUser->id }}`, {
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
