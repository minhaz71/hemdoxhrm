<x-app-layout>
    <x-slot name="title">Add Employee</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Add New Employee</h5>
            <small class="text-muted">Fill in the details below</small>
        </div>
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('employees.store') }}">
        @csrf

        <div class="row g-4">

            {{-- Personal Info --}}
            <div class="col-lg-8">
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Personal Information
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                                   value="{{ old('first_name') }}" required>
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                                   value="{{ old('last_name') }}" required>
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="employee@company.com">
                            <div class="form-text">Used for login, notifications, and Time Doctor matching.</div>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alternate Email</label>
                            <input type="email" name="alternate_email" class="form-control @error('alternate_email') is-invalid @enderror"
                                   value="{{ old('alternate_email') }}" placeholder="personal@example.com">
                            <div class="form-text">Notification-only. Time Doctor matching always uses primary email.</div>
                            @error('alternate_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
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
                            <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
                                   value="{{ old('address') }}">
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Employment Info --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Employment Details
                    </h6>
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Employee Code <small class="text-muted">(organization)</small></label>
                            <input type="text" name="organization_employee_code"
                                   class="form-control @error('organization_employee_code') is-invalid @enderror"
                                   value="{{ old('organization_employee_code') }}"
                                   placeholder="Optional company-provided code">
                            <div class="form-text">Manual code from your organization. HRMS system code is generated automatically.</div>
                            @error('organization_employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Branch (only when multi-branch is enabled) --}}
                        @if($branchesEnabled)
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label mb-0">Branch</label>
                                @can('permission:settings.edit')
                                <a href="#" class="small text-primary" onclick="openQuickBranchModal(); return false;">
                                    <i class="bi bi-plus-circle me-1"></i> Add Branch
                                </a>
                                @endcan
                            </div>

                            @if($branches->isEmpty())
                            <div class="alert alert-warning d-flex align-items-center gap-2 py-2" style="font-size:.84rem;">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>
                                    No branches set up yet.
                                    <a href="{{ route('company.index') }}#tab-branches" target="_blank" class="alert-link">
                                        Add branches in Company Profile
                                    </a>
                                    or
                                    <a href="#" class="alert-link" onclick="openQuickBranchModal(); return false;">add one now</a>.
                                </span>
                            </div>
                            @endif

                            <div class="d-flex gap-2">
                                <select name="branch_id" id="branchSelect"
                                        class="form-select @error('branch_id') is-invalid @enderror"
                                        onchange="loadDepartments(this.value)">
                                    <option value="">— Select Branch —</option>
                                    @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>
                                        {{ $b->name }}{{ $b->is_headquarters ? ' (HQ)' : '' }}
                                    </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary flex-shrink-0"
                                        title="Add new branch" onclick="openQuickBranchModal()">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            @error('branch_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        @endif

                        {{-- Department (cascades from branch when enabled, else plain list) --}}
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department_id" id="departmentSelect"
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

                        <div class="col-md-6">
                            <label class="form-label">Team Leader</label>
                            <select name="team_leader_id" class="form-select @error('team_leader_id') is-invalid @enderror">
                                <option value="">— No Team Leader —</option>
                                @foreach($teamLeaders as $leader)
                                <option value="{{ $leader->id }}" {{ old('team_leader_id') == $leader->id ? 'selected' : '' }}>
                                    {{ $leader->full_name }} — {{ $leader->employee_code }}
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text">Receives leave approval and payslip notifications when assigned.</div>
                            @error('team_leader_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Designation --}}
                        <div class="col-md-6">
                            <label class="form-label">Designation <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2">
                                <select name="designation_id"
                                        class="form-select @error('designation_id') is-invalid @enderror" required>
                                    <option value="">— Select Designation —</option>
                                    @foreach($designations as $d)
                                        <option value="{{ $d->id }}" {{ old('designation_id') == $d->id ? 'selected' : '' }}>
                                            {{ $d->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @can('manage-designations')
                                <a href="{{ route('designations.index') }}" target="_blank"
                                   class="btn btn-outline-secondary flex-shrink-0" title="Manage Designations">
                                    <i class="bi bi-gear"></i>
                                </a>
                                @endcan
                            </div>
                            @error('designation_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        {{-- Shift --}}
                        <div class="col-md-6">
                            <label class="form-label">Shift</label>
                            <select name="shift_id" class="form-select @error('shift_id') is-invalid @enderror">
                                <option value="">— No Shift —</option>
                                @foreach($shifts as $s)
                                <option value="{{ $s->id }}" {{ old('shift_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }} ({{ substr($s->start_time,0,5) }}–{{ substr($s->end_time,0,5) }})
                                </option>
                                @endforeach
                            </select>
                            @error('shift_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Weekly Off Days</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(['sunday','monday','tuesday','wednesday','thursday','friday','saturday'] as $day)
                                    <label class="border rounded px-3 py-2">
                                        <input type="checkbox" name="weekly_off_days[]" value="{{ $day }}" @checked(in_array($day, old('weekly_off_days', $defaultWeeklyOffDays ?? []), true))>
                                        {{ ucfirst($day) }}
                                    </label>
                                @endforeach
                            </div>
                            @error('weekly_off_days')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Join Date <span class="text-danger">*</span></label>
                            <input type="date" name="join_date" class="form-control @error('join_date') is-invalid @enderror"
                                   value="{{ old('join_date') }}" required>
                            @error('join_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employment Type <span class="text-danger">*</span></label>
                            <select name="employment_type" class="form-select @error('employment_type') is-invalid @enderror" required>
                                <option value="full_time"  {{ old('employment_type','full_time') === 'full_time' ? 'selected' : '' }}>Full Time</option>
                                <option value="part_time"  {{ old('employment_type') === 'part_time' ? 'selected' : '' }}>Part Time</option>
                                <option value="contract"   {{ old('employment_type') === 'contract'  ? 'selected' : '' }}>Contract</option>
                            </select>
                            @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Login Account --}}
                <div class="hrms-card p-4">
                    <h6 class="fw-semibold mb-1 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Login Account <span class="badge bg-secondary fw-normal ms-1">Optional</span>
                    </h6>
                    <small class="text-muted d-block mb-3">If an email is provided above, a linked account is created. Leave password blank to generate a secure password.</small>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                   minlength="13" placeholder="Leave blank to generate a secure password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control"
                                   minlength="13" placeholder="Repeat password when setting one">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar: Salary + Actions --}}
            <div class="col-lg-4">
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Salary
                    </h6>
                    <label class="form-label">Base Salary (monthly) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">{{ currency_symbol() }}</span>
                        <input type="number" name="base_salary" step="0.01" min="0"
                               class="form-control @error('base_salary') is-invalid @enderror"
                               value="{{ old('base_salary', '0.00') }}" required>
                        @error('base_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="hrms-card p-4">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-person-check me-1"></i> Create Employee
                    </button>
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary w-100">
                        Cancel
                    </a>
                    <p class="text-muted small mt-3 mb-0 text-center">
                        <i class="bi bi-mortarboard me-1"></i>Education records can be added after saving.
                    </p>
                </div>
            </div>

        </div>
    </form>
{{-- ── Quick Branch Modal (only when branches enabled) ───────────── --}}
@if($branchesEnabled)
<div class="modal fade" id="quickBranchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-geo-alt me-2 text-primary"></i>Add New Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="qbError" class="alert alert-danger d-none small"></div>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" id="qbName" class="form-control" placeholder="e.g. Dhaka Head Office">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Code</label>
                        <input type="text" id="qbCode" class="form-control" placeholder="e.g. BR-01">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">City</label>
                        <input type="text" id="qbCity" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Country</label>
                        <input type="text" id="qbCountry" class="form-control">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="qbHQ">
                            <label class="form-check-label" for="qbHQ">Mark as Headquarters</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="saveQuickBranch()">
                    <i class="bi bi-floppy me-1"></i> Save & Select
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
const _branchesEnabled = @json($branchesEnabled);
const _csrf = document.querySelector('meta[name="csrf-token"]').content;

// ── Branch → Department cascade ────────────────────────────────
async function loadDepartments(branchId) {
    if (!_branchesEnabled) return;
    const sel = document.getElementById('departmentSelect');
    if (!sel) return;
    const current = sel.value;
    sel.innerHTML = '<option value="">— Loading… —</option>';
    const url = '{{ route("departments.by-branch") }}' + (branchId ? `?branch_id=${branchId}` : '');
    try {
        const res  = await fetch(url, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        sel.innerHTML = '<option value="">— Select Department —</option>';
        data.forEach(d => {
            const opt = new Option(d.name, d.id, false, String(d.id) === String(current));
            sel.appendChild(opt);
        });
    } catch {
        sel.innerHTML = '<option value="">— Select Department —</option>';
    }
}

// Trigger on page load if branch already selected (validation error)
const initBranch = document.getElementById('branchSelect')?.value;
if (initBranch) loadDepartments(initBranch);

// ── Quick Branch ───────────────────────────────────────────────
const _qbModal = document.getElementById('quickBranchModal')
    ? new bootstrap.Modal(document.getElementById('quickBranchModal'))
    : null;

function openQuickBranchModal() {
    if (!_qbModal) return;
    document.getElementById('qbName').value    = '';
    document.getElementById('qbCode').value    = '';
    document.getElementById('qbCity').value    = '';
    document.getElementById('qbCountry').value = '';
    document.getElementById('qbHQ').checked    = false;
    document.getElementById('qbError').classList.add('d-none');
    _qbModal.show();
}

async function saveQuickBranch() {
    const name = document.getElementById('qbName').value.trim();
    const errEl = document.getElementById('qbError');
    if (!name) {
        errEl.textContent = 'Branch name is required.';
        errEl.classList.remove('d-none');
        return;
    }
    errEl.classList.add('d-none');

    try {
        const res = await fetch('{{ route("company.branches.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrf, Accept: 'application/json' },
            body: JSON.stringify({
                name:            name,
                code:            document.getElementById('qbCode').value.trim() || null,
                city:            document.getElementById('qbCity').value.trim() || null,
                country:         document.getElementById('qbCountry').value.trim() || null,
                is_headquarters: document.getElementById('qbHQ').checked ? 1 : 0,
                office_hours:    [],
            }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Failed to save branch.');

        // Add to branch select and select it
        const sel = document.getElementById('branchSelect');
        if (sel) {
            const opt = new Option(
                data.branch.name + (data.branch.is_headquarters ? ' (HQ)' : ''),
                data.branch.id,
                true,
                true
            );
            sel.appendChild(opt);
            sel.value = data.branch.id;
            loadDepartments(data.branch.id);
        }

        // Remove the "no branches" warning if it was shown
        document.querySelector('.alert-warning')?.remove();

        _qbModal.hide();
    } catch (err) {
        errEl.textContent = err.message;
        errEl.classList.remove('d-none');
    }
}
</script>
@endpush
</x-app-layout>
