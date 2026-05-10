<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Employee Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Inter', sans-serif; }
        .reg-header { background: #1a1f2e; color: #fff; padding: 18px 0; }
        .reg-header h4 { margin: 0; font-weight: 700; font-size: 1.1rem; }
        .section-title { font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #6c757d; margin-bottom: 1rem; }
        .card { border: 1px solid #e9ecef; border-radius: 10px; }
        .form-label { font-size: .875rem; font-weight: 500; }
        .required-star { color: #dc3545; }

        /* Step indicator */
        .step-indicator { display: flex; align-items: center; justify-content: center; gap: 0; margin-bottom: 2rem; }
        .step { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
        .step-circle { width: 36px; height: 36px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 700; border: 2px solid #dee2e6; z-index: 1; }
        .step.active .step-circle { background: #0d6efd; color: #fff; border-color: #0d6efd; }
        .step.done .step-circle { background: #198754; color: #fff; border-color: #198754; }
        .step-label { font-size: .72rem; color: #6c757d; margin-top: 6px; text-align: center; }
        .step.active .step-label { color: #0d6efd; font-weight: 600; }
        .step-line { flex: 1; height: 2px; background: #dee2e6; margin-top: -24px; }

        /* Photo preview */
        .photo-preview-wrap { display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .photo-circle { width: 110px; height: 110px; border-radius: 50%; background: #e9ecef; border: 3px dashed #ced4da; display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; }
        .photo-circle img { width: 100%; height: 100%; object-fit: cover; }
        .photo-circle i { font-size: 2.5rem; color: #adb5bd; }

        /* File upload drop area */
        .file-drop { border: 2px dashed #ced4da; border-radius: 8px; padding: 18px; text-align: center; cursor: pointer; transition: border-color .2s; background: #fafafa; }
        .file-drop:hover { border-color: #0d6efd; }
        .file-drop input[type=file] { display: none; }
        .file-drop .file-name { font-size: .8rem; color: #0d6efd; margin-top: 6px; word-break: break-all; }

        /* Locked section */
        .locked-field { position: relative; }
        .locked-overlay { position: absolute; inset: 0; background: rgba(248,249,250,.85); border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: .8rem; color: #6c757d; pointer-events: none; z-index: 2; }
        .locked-overlay i { font-size: 1rem; color: #adb5bd; }

        /* Login ID availability badge */
        .login-id-status { font-size: .78rem; margin-top: 4px; min-height: 18px; }
    </style>
</head>
<body>

{{-- ── Header ──────────────────────────────────────────────────────── --}}
<div class="reg-header">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h4><i class="bi bi-person-badge me-2" style="color:#4e9af1;"></i>Employee Registration</h4>
            <span class="badge bg-warning text-dark" style="font-size:.72rem;">
                <i class="bi bi-clock me-1"></i>Expires: {{ $invitation->expires_at->format('d M Y H:i') }}
            </span>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            {{-- Step indicator --}}
            <div class="step-indicator mb-4">
                <div class="step active">
                    <div class="step-circle">1</div>
                    <div class="step-label">Personal Info</div>
                </div>
                <div class="step-line"></div>
                <div class="step active">
                    <div class="step-circle">2</div>
                    <div class="step-label">Documents</div>
                </div>
                <div class="step-line"></div>
                <div class="step active">
                    <div class="step-circle">3</div>
                    <div class="step-label">Account Setup</div>
                </div>
            </div>

            @if($errors->any())
            <div class="alert alert-danger mb-4">
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ $invitation->submission_url }}"
                  enctype="multipart/form-data">
                @csrf

                {{-- ── Card 1: Personal Information ───────────────────── --}}
                <div class="card p-4 mb-4">
                    <p class="section-title"><i class="bi bi-person me-1"></i>Personal Information</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="required-star">*</span></label>
                            <input type="text" name="first_name"
                                   class="form-control @error('first_name') is-invalid @enderror"
                                   value="{{ old('first_name') }}" required>
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="required-star">*</span></label>
                            <input type="text" name="last_name"
                                   class="form-control @error('last_name') is-invalid @enderror"
                                   value="{{ old('last_name') }}" required>
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="required-star">*</span></label>
                            <input type="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $invitation->email) }}" required>
                            <div class="form-text">Used for login, notifications, and Time Doctor matching.</div>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employee Code <small class="text-muted">(organization)</small></label>
                            <input type="text" name="organization_employee_code"
                                   class="form-control @error('organization_employee_code') is-invalid @enderror"
                                   value="{{ old('organization_employee_code') }}"
                                   placeholder="Optional company-provided code">
                            @error('organization_employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth"
                                   class="form-control @error('date_of_birth') is-invalid @enderror"
                                   value="{{ old('date_of_birth') }}">
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                <option value="">— Select —</option>
                                <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other"  {{ old('gender') === 'other'  ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address"
                                   class="form-control @error('address') is-invalid @enderror"
                                   value="{{ old('address') }}">
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- ── Card 2: Identity & Documents ────────────────────── --}}
                <div class="card p-4 mb-4">
                    <p class="section-title"><i class="bi bi-file-earmark-person me-1"></i>Identity &amp; Documents</p>
                    <div class="row g-4">

                        {{-- Profile Photo --}}
                        <div class="col-md-4 d-flex flex-column align-items-center">
                            <label class="form-label w-100 text-center mb-2">Profile Photo</label>
                            <div class="photo-preview-wrap">
                                <div class="photo-circle" id="photoCircle" onclick="document.getElementById('photoInput').click()">
                                    @if(old('photo'))
                                    <i class="bi bi-person-fill"></i>
                                    @else
                                    <i class="bi bi-person-fill"></i>
                                    @endif
                                </div>
                                <small class="text-muted" style="font-size:.73rem;">JPG/PNG, max 2MB</small>
                                <input type="file" name="photo" id="photoInput"
                                       accept="image/jpeg,image/png,image/jpg"
                                       class="@error('photo') is-invalid @enderror">
                                @error('photo')<div class="text-danger" style="font-size:.8rem;">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-8">
                            {{-- NID Number --}}
                            <div class="mb-3">
                                <label class="form-label">National ID (NID) Number</label>
                                <input type="text" name="nid"
                                       class="form-control @error('nid') is-invalid @enderror"
                                       value="{{ old('nid') }}" maxlength="50"
                                       placeholder="Enter your NID number">
                                @error('nid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- NID Document --}}
                            <div class="mb-3">
                                <label class="form-label">NID Document <small class="text-muted">(PDF, max 5MB)</small></label>
                                <div class="file-drop @error('nid_document') border-danger @enderror"
                                     onclick="document.getElementById('nidDocInput').click()">
                                    <i class="bi bi-file-earmark-pdf text-danger fs-4"></i>
                                    <div class="text-muted" style="font-size:.82rem;">Click to upload NID document</div>
                                    <div class="file-name" id="nidDocName"></div>
                                    <input type="file" name="nid_document" id="nidDocInput" accept="application/pdf">
                                </div>
                                @error('nid_document')<div class="text-danger mt-1" style="font-size:.8rem;">{{ $message }}</div>@enderror
                            </div>

                            {{-- Education Certificate --}}
                            <div class="mb-0">
                                <label class="form-label">Education Certificate <small class="text-muted">(PDF, max 5MB)</small></label>
                                <div class="file-drop @error('certificate') border-danger @enderror"
                                     onclick="document.getElementById('certInput').click()">
                                    <i class="bi bi-file-earmark-text text-primary fs-4"></i>
                                    <div class="text-muted" style="font-size:.82rem;">Click to upload certificate</div>
                                    <div class="file-name" id="certName"></div>
                                    <input type="file" name="certificate" id="certInput" accept="application/pdf">
                                </div>
                                @error('certificate')<div class="text-danger mt-1" style="font-size:.8rem;">{{ $message }}</div>@enderror
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ── Card 3: Employment Preference ───────────────────── --}}
                <div class="card p-4 mb-4">
                    <p class="section-title"><i class="bi bi-briefcase me-1"></i>Employment Preference</p>
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Designation <span class="required-star">*</span></label>
                            <select name="designation_id"
                                    class="form-select @error('designation_id') is-invalid @enderror" required>
                                <option value="">— Select Designation —</option>
                                @foreach($designations as $d)
                                <option value="{{ $d->id }}" {{ old('designation_id') == $d->id ? 'selected' : '' }}>
                                    {{ $d->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department_id"
                                    class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">— Select Department —</option>
                                @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>
                                    {{ $d->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if($branchesEnabled && $branches->isNotEmpty())
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select name="branch_id"
                                    class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">— Select Branch —</option>
                                @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label">Shift</label>
                            <select name="shift_id"
                                    class="form-select @error('shift_id') is-invalid @enderror">
                                <option value="">— Select Shift —</option>
                                @foreach($shifts as $s)
                                <option value="{{ $s->id }}" {{ old('shift_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('shift_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- ── Weekly Off Preference (shown only when admin setting is enabled) ── --}}
                        @if(setting('registration_weekly_off_enabled', true) !== false)
                        <div class="col-12">
                            <div class="border rounded p-3" style="background:#f8f9fa;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-calendar2-week text-primary"></i>
                                    <span class="fw-semibold" style="font-size:.9rem;">Weekly Off Preference</span>
                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size:.72rem;">Optional</span>
                                </div>
                                <p class="text-muted mb-3" style="font-size:.82rem;">
                                    Select the day(s) you prefer as your weekly off. HR will review and confirm during approval.
                                </p>

                                {{-- Day checkboxes --}}
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    @foreach(['sunday','monday','tuesday','wednesday','thursday','friday','saturday'] as $day)
                                        <label class="border rounded px-3 py-2 d-flex align-items-center gap-2"
                                               style="cursor:pointer;font-size:.875rem;
                                                      background:{{ in_array($day, old('weekly_off_days', $defaultWeeklyOffDays ?? []), true) ? '#e8f4fd' : '#fff' }};">
                                            <input type="checkbox"
                                                   name="weekly_off_days[]"
                                                   value="{{ $day }}"
                                                   @checked(in_array($day, old('weekly_off_days', $defaultWeeklyOffDays ?? []), true))>
                                            {{ ucfirst($day) }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('weekly_off_days')
                                    <div class="text-danger small mb-2">{{ $message }}</div>
                                @enderror

                                {{-- Reason / note --}}
                                <label class="form-label mb-1" style="font-size:.85rem;">
                                    Reason <span class="text-muted">(optional)</span>
                                </label>
                                <textarea name="weekly_off_note"
                                          rows="2"
                                          class="form-control form-control-sm @error('weekly_off_note') is-invalid @enderror"
                                          placeholder="e.g. Friday is my religious observance day, family commitment on Saturday…"
                                          maxlength="500">{{ old('weekly_off_note') }}</textarea>
                                @error('weekly_off_note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        @endif {{-- registration_weekly_off_enabled --}}

                    </div>
                </div>

                {{-- ── Card 4: Salary & Employment Details (BLOCKED) ────── --}}
                <div class="card p-4 mb-4">
                    <p class="section-title"><i class="bi bi-currency-dollar me-1"></i>Salary &amp; Employment Details</p>

                    <div class="alert alert-info d-flex align-items-start gap-2 mb-3" style="font-size:.875rem;">
                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                        <span>Salary and employment terms will be filled by the HR department after review. These fields are not editable by the applicant.</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted">Employment Type</label>
                            <div class="locked-field">
                                <input type="text" class="form-control bg-light" value="To be set by HR" readonly disabled>
                                <div class="locked-overlay"><i class="bi bi-lock-fill"></i> To be filled by HR</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Join Date</label>
                            <div class="locked-field">
                                <input type="text" class="form-control bg-light" value="To be set by HR" readonly disabled>
                                <div class="locked-overlay"><i class="bi bi-lock-fill"></i> To be filled by HR</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted">Base Salary</label>
                            <div class="locked-field">
                                <input type="text" class="form-control bg-light" value="To be set by HR" readonly disabled>
                                <div class="locked-overlay"><i class="bi bi-lock-fill"></i> To be filled by HR</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Card 5: Account Credentials ────────────────────── --}}
                <div class="card p-4 mb-4">
                    <p class="section-title"><i class="bi bi-shield-lock me-1"></i>Account Credentials</p>
                    <p class="text-muted mb-3" style="font-size:.875rem;">
                        These credentials will be used to log in after your application is approved.
                    </p>
                    <div class="row g-3">

                        {{-- User ID (login_id) --}}
                        <div class="col-md-12">
                            <label class="form-label">User ID <span class="required-star">*</span>
                                <small class="text-muted fw-normal">(letters, numbers, underscore only)</small>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-at"></i></span>
                                <input type="text" name="login_id" id="loginIdInput"
                                       class="form-control @error('login_id') is-invalid @enderror"
                                       value="{{ old('login_id') }}"
                                       minlength="4" maxlength="50"
                                       pattern="[a-zA-Z0-9_]+"
                                       required
                                       autocomplete="username"
                                       placeholder="e.g. john_doe">
                                @error('login_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="login-id-status" id="loginIdStatus"></div>
                        </div>

                        {{-- Password --}}
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="required-star">*</span>
                                <small class="text-muted fw-normal">(min 13 characters)</small>
                            </label>
                            <div class="input-group">
                                <input type="password" name="password" id="passwordInput"
                                       class="form-control @error('password') is-invalid @enderror"
                                       minlength="13" required autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                </button>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password <span class="required-star">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password_confirmation" id="passwordConfirmInput"
                                       class="form-control" minlength="13" required autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" id="togglePasswordConfirm">
                                    <i class="bi bi-eye" id="togglePasswordConfirmIcon"></i>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex gap-3 justify-content-end mb-5">
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="bi bi-send me-1"></i> Submit Registration
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Photo preview ──────────────────────────────────────────────────
document.getElementById('photoInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        const circle = document.getElementById('photoCircle');
        circle.innerHTML = '<img src="' + e.target.result + '" alt="Photo preview">';
    };
    reader.readAsDataURL(file);
});

// ── PDF file name display ──────────────────────────────────────────
document.getElementById('nidDocInput').addEventListener('change', function () {
    document.getElementById('nidDocName').textContent = this.files[0] ? this.files[0].name : '';
});
document.getElementById('certInput').addEventListener('change', function () {
    document.getElementById('certName').textContent = this.files[0] ? this.files[0].name : '';
});

// ── Password show/hide ─────────────────────────────────────────────
document.getElementById('togglePassword').addEventListener('click', function () {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('togglePasswordIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
document.getElementById('togglePasswordConfirm').addEventListener('click', function () {
    const input = document.getElementById('passwordConfirmInput');
    const icon  = document.getElementById('togglePasswordConfirmIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
});

// ── Login ID availability check (debounced 500ms) ─────────────────
(function () {
    let debounceTimer = null;
    const input  = document.getElementById('loginIdInput');
    const status = document.getElementById('loginIdStatus');
    const checkUrl = '{{ route('register.check-login-id') }}';

    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const val = this.value.trim();
        status.innerHTML = '';

        if (val.length < 4) {
            if (val.length > 0) {
                status.innerHTML = '<span class="text-muted"><i class="bi bi-dash-circle me-1"></i>At least 4 characters required</span>';
            }
            return;
        }

        status.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Checking…</span>';

        debounceTimer = setTimeout(function () {
            fetch(checkUrl + '?login_id=' + encodeURIComponent(val), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.available) {
                    status.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>' + data.message + '</span>';
                } else {
                    status.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>' + data.message + '</span>';
                }
            })
            .catch(() => {
                status.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Could not verify</span>';
            });
        }, 500);
    });
})();
</script>
</body>
</html>
