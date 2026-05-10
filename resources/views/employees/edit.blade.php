<x-app-layout>
    <x-slot name="title">Edit Employee</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Edit Employee</h5>
            <small class="text-muted">{{ $employee->employee_code }} — {{ $employee->full_name }}</small>
        </div>
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('employees.update', $employee) }}"
          enctype="multipart/form-data">
        @csrf @method('PATCH')

        <div class="row g-4">

            <div class="col-lg-8">
                {{-- Personal Info --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Personal Information
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                                   value="{{ old('first_name', $employee->first_name) }}" required>
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                                   value="{{ old('last_name', $employee->last_name) }}" required>
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $employee->phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $employee->user?->email) }}" placeholder="employee@company.com">
                            <div class="form-text">Used for login, notifications, and Time Doctor matching.</div>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                                   value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}">
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                <option value="">— Select —</option>
                                <option value="male"   {{ old('gender', $employee->gender) === 'male'   ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $employee->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other"  {{ old('gender', $employee->gender) === 'other'  ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
                                   value="{{ old('address', $employee->address) }}">
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Photo & Documents --}}
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        <i class="bi bi-file-earmark-person me-1"></i>Photo &amp; Documents
                    </h6>
                    <div class="row g-4">

                        {{-- Profile Photo --}}
                        <div class="col-md-4 d-flex flex-column align-items-center">
                            <label class="form-label w-100 text-center mb-2">Profile Photo</label>
                            @if($employee->photo_url)
                            <img id="photoPreview" src="{{ $employee->photo_url }}" alt="Photo"
                                 style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #e9ecef;cursor:pointer;"
                                 onclick="document.getElementById('photoInput').click()">
                            @else
                            <div id="photoPreviewPlaceholder"
                                 style="width:100px;height:100px;border-radius:50%;background:#e9ecef;border:3px dashed #ced4da;display:flex;align-items:center;justify-content:center;cursor:pointer;"
                                 onclick="document.getElementById('photoInput').click()">
                                <i class="bi bi-camera fs-3 text-secondary"></i>
                            </div>
                            @endif
                            <input type="file" name="photo" id="photoInput" class="d-none"
                                   accept="image/jpeg,image/png,image/jpg"
                                   onchange="previewPhoto(this)">
                            <small class="text-muted mt-2" style="font-size:.72rem;">JPG/PNG, max 2MB<br>Click to change</small>
                            @error('photo')<div class="text-danger mt-1" style="font-size:.78rem;">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-8">
                            {{-- NID Number --}}
                            <div class="mb-3">
                                <label class="form-label">NID Number</label>
                                <input type="text" name="nid"
                                       class="form-control form-control-sm @error('nid') is-invalid @enderror"
                                       value="{{ old('nid', $employee->nid) }}" maxlength="50"
                                       placeholder="National ID number">
                                @error('nid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- NID Document --}}
                            <div class="mb-3">
                                <label class="form-label">NID Document <small class="text-muted">(PDF, max 5MB)</small></label>
                                @if($employee->nid_document)
                                <div class="mb-1">
                                    <a href="{{ asset('storage/'.$employee->nid_document) }}" target="_blank"
                                       class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-file-earmark-pdf me-1"></i>View Current
                                    </a>
                                </div>
                                @endif
                                <input type="file" name="nid_document"
                                       class="form-control form-control-sm @error('nid_document') is-invalid @enderror"
                                       accept="application/pdf">
                                <small class="text-muted" style="font-size:.73rem;">PDF only. Leave blank to keep existing.</small>
                                @error('nid_document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Certificate --}}
                            <div class="mb-0">
                                <label class="form-label">Education Certificate <small class="text-muted">(PDF, max 5MB — Optional)</small></label>
                                @if($employee->certificate)
                                <div class="mb-1">
                                    <a href="{{ asset('storage/'.$employee->certificate) }}" target="_blank"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark-text me-1"></i>View Current
                                    </a>
                                </div>
                                @endif
                                <input type="file" name="certificate"
                                       class="form-control form-control-sm @error('certificate') is-invalid @enderror"
                                       accept="application/pdf">
                                <small class="text-muted" style="font-size:.73rem;">PDF only. Leave blank to keep existing.</small>
                                @error('certificate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Employment Info --}}
                <div class="hrms-card p-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Employment Details
                    </h6>
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Employee Code <small class="text-muted">(organization)</small></label>
                            <input type="text" name="organization_employee_code"
                                   class="form-control @error('organization_employee_code') is-invalid @enderror"
                                   value="{{ old('organization_employee_code', $employee->organization_employee_code) }}"
                                   placeholder="Optional company-provided code">
                            <div class="form-text">HRMS system code remains {{ $employee->employee_code }}.</div>
                            @error('organization_employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Branch (only when multi-branch is enabled) --}}
                        @if($branchesEnabled)
                        <div class="col-12">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" id="branchSelect"
                                    class="form-select @error('branch_id') is-invalid @enderror"
                                    onchange="loadDepartments(this.value)">
                                <option value="">— Select Branch —</option>
                                @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ old('branch_id', $employee->branch_id) == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}{{ $b->is_headquarters ? ' (HQ)' : '' }}
                                </option>
                                @endforeach
                            </select>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @endif

                        {{-- Department (cascades when branches enabled, plain list otherwise) --}}
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department_id" id="departmentSelect"
                                    class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">— Select Department —</option>
                                @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ old('department_id', $employee->department_id) == $d->id ? 'selected' : '' }}>
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
                                <option value="{{ $leader->id }}" {{ old('team_leader_id', $employee->team_leader_id) == $leader->id ? 'selected' : '' }}>
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
                                        <option value="{{ $d->id }}"
                                            {{ old('designation_id', $employee->designation_id) == $d->id ? 'selected' : '' }}>
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
                                <option value="{{ $s->id }}" {{ old('shift_id', $employee->shift_id) == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }} ({{ substr($s->start_time,0,5) }}–{{ substr($s->end_time,0,5) }})
                                </option>
                                @endforeach
                            </select>
                            @error('shift_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Weekly Off Days</label>
                            @php $weeklyOffDays = old('weekly_off_days', $employee->weeklyOffs()->active()->pluck('day_of_week')->all()); @endphp
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(['sunday','monday','tuesday','wednesday','thursday','friday','saturday'] as $day)
                                    <label class="border rounded px-3 py-2">
                                        <input type="checkbox" name="weekly_off_days[]" value="{{ $day }}" @checked(in_array($day, $weeklyOffDays, true))>
                                        {{ ucfirst($day) }}
                                    </label>
                                @endforeach
                            </div>
                            @error('weekly_off_days')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Join Date <span class="text-danger">*</span></label>
                            <input type="date" name="join_date" class="form-control @error('join_date') is-invalid @enderror"
                                   value="{{ old('join_date', $employee->join_date->format('Y-m-d')) }}" required>
                            @error('join_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employment Type <span class="text-danger">*</span></label>
                            <select name="employment_type" class="form-select @error('employment_type') is-invalid @enderror" required>
                                <option value="full_time" {{ old('employment_type', $employee->employment_type) === 'full_time' ? 'selected' : '' }}>Full Time</option>
                                <option value="part_time" {{ old('employment_type', $employee->employment_type) === 'part_time' ? 'selected' : '' }}>Part Time</option>
                                <option value="contract"  {{ old('employment_type', $employee->employment_type) === 'contract'  ? 'selected' : '' }}>Contract</option>
                            </select>
                            @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Salary
                    </h6>
                    <label class="form-label">Base Salary (monthly) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">{{ currency_symbol() }}</span>
                        <input type="number" name="base_salary" step="0.01" min="0"
                               id="baseSalaryInput"
                               class="form-control @error('base_salary') is-invalid @enderror"
                               value="{{ old('base_salary', $employee->base_salary) }}" required>
                        @error('base_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Effective-from shown only when salary is changed --}}
                    <div id="salaryEffectiveWrap" class="mt-2" style="display:none;">
                        <label class="form-label form-label-sm text-muted">
                            <i class="bi bi-calendar-event me-1"></i>Apply new salary from month
                        </label>
                        <input type="month" name="salary_effective_from"
                               class="form-control form-control-sm @error('salary_effective_from') is-invalid @enderror"
                               value="{{ old('salary_effective_from', now()->format('Y-m')) }}"
                               max="{{ now()->format('Y-m') }}">
                        <div class="form-text">Payrolls from this month onward will use the new salary. Earlier payrolls must be regenerated manually.</div>
                        @error('salary_effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <script>
                    (function () {
                        const input = document.getElementById('baseSalaryInput');
                        const wrap  = document.getElementById('salaryEffectiveWrap');
                        const orig  = input.value;
                        input.addEventListener('input', () => {
                            wrap.style.display = input.value !== orig ? '' : 'none';
                        });
                    })();
                    </script>
                </div>

                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Status
                    </h6>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="active"     {{ old('status', $employee->status) === 'active'     ? 'selected' : '' }}>Active</option>
                        <option value="inactive"   {{ old('status', $employee->status) === 'inactive'   ? 'selected' : '' }}>Inactive</option>
                        <option value="terminated" {{ old('status', $employee->status) === 'terminated' ? 'selected' : '' }}>Terminated</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="hrms-card p-4">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-check2 me-1"></i> Save Changes
                    </button>
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary w-100">
                        Cancel
                    </a>
                </div>
            </div>

        </div>
    </form>

    {{-- ── Education Records ── --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="hrms-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-mortarboard me-2 text-primary"></i>Education Records</span>
                    @can('update', $employee)
                    <button class="btn btn-sm btn-primary" id="btnAddEdu">
                        <i class="bi bi-plus-lg me-1"></i> Add Education
                    </button>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:.875rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width:36px">#</th>
                                <th>Degree Type</th>
                                <th>Degree / Qualification</th>
                                <th>Institute</th>
                                <th class="text-center">Year</th>
                                <th class="text-center">Result</th>
                                <th>Board / University</th>
                                @can('update', $employee)
                                <th class="text-end">Actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody id="eduTbody">
                            @forelse($educations as $i => $edu)
                            <tr id="edu-row-{{ $edu->id }}">
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td><span class="badge bg-primary-subtle text-primary">{{ $edu->degreeType->name }}</span></td>
                                <td class="fw-semibold">{{ $edu->degreeName->name }}</td>
                                <td>{{ $edu->institute_name }}</td>
                                <td class="text-center">{{ $edu->passing_year }}</td>
                                <td class="text-center">
                                    @if($edu->result)
                                        <span class="badge bg-success-subtle text-success">{{ $edu->result }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $edu->board_university ?? '—' }}</td>
                                @can('update', $employee)
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-edu" data-id="{{ $edu->id }}" title="Edit"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-outline-danger btn-del-edu" data-id="{{ $edu->id }}" title="Delete"><i class="bi bi-trash"></i></button>
                                </td>
                                @endcan
                            </tr>
                            @empty
                            <tr id="eduEmptyRow">
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-mortarboard fs-3 d-block mb-2 opacity-25"></i>
                                    No education records yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @can('update', $employee)
    {{-- Add / Edit Modal --}}
    <div class="modal fade" id="eduModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eduModalTitle">Add Education Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="eduFormErr" class="alert alert-danger d-none"></div>
                    <input type="hidden" id="eduEditId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Degree Type <span class="text-danger">*</span></label>
                            <select id="fldDegreeType" class="form-select">
                                <option value="">— Select Degree Type —</option>
                                @foreach($degreeTypes as $dt)
                                <option value="{{ $dt->id }}">{{ $dt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Degree / Qualification <span class="text-danger">*</span></label>
                            <select id="fldDegreeName" class="form-select" disabled>
                                <option value="">— Select Degree Type first —</option>
                            </select>
                            <div id="fldDegreeNameSpinner" class="text-muted small mt-1 d-none">
                                <span class="spinner-border spinner-border-sm me-1"></span> Loading…
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Institute Name <span class="text-danger">*</span></label>
                            <input type="text" id="fldInstitute" class="form-control" placeholder="e.g. University of Dhaka" maxlength="200">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Passing Year <span class="text-danger">*</span></label>
                            <input type="number" id="fldYear" class="form-control" min="1950" max="{{ date('Y') + 1 }}" placeholder="{{ date('Y') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Result / GPA <span class="text-muted small">(optional)</span></label>
                            <input type="text" id="fldResult" class="form-control" placeholder="e.g. 3.85 / A+" maxlength="50">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Board / University <span class="text-muted small">(optional)</span></label>
                            <input type="text" id="fldBoard" class="form-control" placeholder="e.g. Dhaka Board" maxlength="200">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSaveEdu">
                        <span id="btnSaveEduTxt">Save Record</span>
                        <span id="btnSaveEduSpin" class="spinner-border spinner-border-sm d-none ms-1"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirm --}}
    <div class="modal fade" id="delEduModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Remove this education record? <small class="text-muted d-block">This cannot be undone.</small>
                    <div id="delEduErr" class="alert alert-danger mt-2 d-none"></div>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger btn-sm" id="btnConfirmDelEdu">Delete</button>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- Toast --}}
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100">
        <div id="eduToast" class="toast align-items-center text-white border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="eduToastMsg"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    // ── Branch → Department cascade ─────────────────────────────────
    const _branchesEnabled = @json($branchesEnabled);

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
    // Trigger on load if branch already selected
    const initBranch = document.getElementById('branchSelect')?.value;
    if (initBranch && _branchesEnabled) loadDepartments(initBranch);

    const EDU_CSRF      = document.querySelector('meta[name="csrf-token"]').content;
    const EDU_BASE      = '/employees/{{ $employee->id }}/education';
    const EDU_NAMES_URL = '/degree-names/by-type';

    const eduModal   = document.getElementById('eduModal')    ? new bootstrap.Modal(document.getElementById('eduModal'))    : null;
    const delEduModal = document.getElementById('delEduModal') ? new bootstrap.Modal(document.getElementById('delEduModal')) : null;
    const eduToastInst = document.getElementById('eduToast')  ? new bootstrap.Toast(document.getElementById('eduToast'), { delay: 3000 }) : null;

    let delEduId = null;

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showEduToast(msg, ok = true) {
        const el = document.getElementById('eduToast');
        el.className = `toast align-items-center text-white border-0 ${ok ? 'bg-success' : 'bg-danger'}`;
        document.getElementById('eduToastMsg').textContent = msg;
        eduToastInst.show();
    }

    function buildEduRow(e, idx) {
        const res = e.result
            ? `<span class="badge bg-success-subtle text-success">${escHtml(e.result)}</span>`
            : '<span class="text-muted">—</span>';
        return `<tr id="edu-row-${e.id}">
            <td class="text-muted">${idx}</td>
            <td><span class="badge bg-primary-subtle text-primary">${escHtml(e.degree_type_name)}</span></td>
            <td class="fw-semibold">${escHtml(e.degree_name_name)}</td>
            <td>${escHtml(e.institute_name)}</td>
            <td class="text-center">${e.passing_year}</td>
            <td class="text-center">${res}</td>
            <td class="text-muted small">${escHtml(e.board_university ?? '—')}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary btn-edit-edu" data-id="${e.id}" title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-del-edu" data-id="${e.id}" title="Delete"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`;
    }

    function loadDegreeNames(typeId, selectAfter = null) {
        const sel  = document.getElementById('fldDegreeName');
        const spin = document.getElementById('fldDegreeNameSpinner');
        if (!typeId) {
            sel.innerHTML = '<option value="">— Select Degree Type first —</option>';
            sel.disabled = true;
            return;
        }
        sel.disabled = true;
        spin.classList.remove('d-none');
        sel.innerHTML = '<option value="">Loading…</option>';
        fetch(`${EDU_NAMES_URL}/${typeId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                sel.innerHTML = '<option value="">— Select Qualification —</option>';
                data.names.forEach(n => sel.innerHTML += `<option value="${n.id}">${escHtml(n.name)}</option>`);
                sel.disabled = false;
                if (selectAfter) sel.value = selectAfter;
            })
            .catch(() => { sel.innerHTML = '<option value="">Failed to load</option>'; sel.disabled = false; })
            .finally(() => spin.classList.add('d-none'));
    }

    function resetEduForm() {
        document.getElementById('eduEditId').value       = '';
        document.getElementById('fldDegreeType').value   = '';
        document.getElementById('fldDegreeName').innerHTML = '<option value="">— Select Degree Type first —</option>';
        document.getElementById('fldDegreeName').disabled  = true;
        document.getElementById('fldInstitute').value    = '';
        document.getElementById('fldYear').value         = '';
        document.getElementById('fldResult').value       = '';
        document.getElementById('fldBoard').value        = '';
        document.getElementById('eduFormErr').classList.add('d-none');
    }

    document.getElementById('fldDegreeType')?.addEventListener('change', function () {
        loadDegreeNames(this.value);
    });

    document.getElementById('btnAddEdu')?.addEventListener('click', () => {
        resetEduForm();
        document.getElementById('eduModalTitle').textContent = 'Add Education Record';
        eduModal.show();
    });

    document.addEventListener('click', e => {
        const editBtn = e.target.closest('.btn-edit-edu');
        if (editBtn && eduModal) {
            fetch(`${EDU_BASE}/${editBtn.dataset.id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(({ education: ed }) => {
                    resetEduForm();
                    document.getElementById('eduEditId').value     = ed.id;
                    document.getElementById('fldDegreeType').value = ed.degree_type_id;
                    document.getElementById('fldInstitute').value  = ed.institute_name;
                    document.getElementById('fldYear').value       = ed.passing_year;
                    document.getElementById('fldResult').value     = ed.result ?? '';
                    document.getElementById('fldBoard').value      = ed.board_university ?? '';
                    loadDegreeNames(ed.degree_type_id, ed.degree_name_id);
                    document.getElementById('eduModalTitle').textContent = 'Edit Education Record';
                    eduModal.show();
                });
        }
        const delBtn = e.target.closest('.btn-del-edu');
        if (delBtn && delEduModal) {
            delEduId = delBtn.dataset.id;
            document.getElementById('delEduErr').classList.add('d-none');
            delEduModal.show();
        }
    });

    document.getElementById('btnSaveEdu')?.addEventListener('click', () => {
        const id     = document.getElementById('eduEditId').value;
        const typeId = document.getElementById('fldDegreeType').value;
        const nameId = document.getElementById('fldDegreeName').value;
        const inst   = document.getElementById('fldInstitute').value.trim();
        const year   = document.getElementById('fldYear').value;
        const result = document.getElementById('fldResult').value.trim() || null;
        const board  = document.getElementById('fldBoard').value.trim() || null;
        const errEl  = document.getElementById('eduFormErr');

        if (!typeId || !nameId || !inst || !year) {
            errEl.textContent = 'Degree Type, Degree Name, Institute, and Year are required.';
            errEl.classList.remove('d-none');
            return;
        }
        const isEdit = !!id;
        document.getElementById('btnSaveEduSpin').classList.remove('d-none');
        document.getElementById('btnSaveEduTxt').textContent = 'Saving…';
        errEl.classList.add('d-none');

        fetch(isEdit ? `${EDU_BASE}/${id}` : EDU_BASE, {
            method: isEdit ? 'PATCH' : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': EDU_CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                degree_type_id:  typeId,
                degree_name_id:  nameId,
                institute_name:  inst,
                passing_year:    parseInt(year),
                result,
                board_university: board,
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message ?? 'Save failed.');
            eduModal.hide();
            showEduToast(data.message);
            const ed       = data.education;
            const existing = document.getElementById(`edu-row-${ed.id}`);
            document.getElementById('eduEmptyRow')?.remove();
            const rows = document.querySelectorAll('#eduTbody tr[id^="edu-row-"]');
            if (existing) {
                existing.outerHTML = buildEduRow(ed, existing.querySelector('td').textContent.trim());
            } else {
                document.getElementById('eduTbody').insertAdjacentHTML('beforeend', buildEduRow(ed, rows.length + 1));
            }
        })
        .catch(err => { errEl.textContent = err.message; errEl.classList.remove('d-none'); })
        .finally(() => {
            document.getElementById('btnSaveEduSpin').classList.add('d-none');
            document.getElementById('btnSaveEduTxt').textContent = 'Save Record';
        });
    });

    document.getElementById('btnConfirmDelEdu')?.addEventListener('click', () => {
        fetch(`${EDU_BASE}/${delEduId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': EDU_CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            delEduModal.hide();
            document.getElementById(`edu-row-${delEduId}`)?.remove();
            showEduToast(data.message);
            if (!document.querySelectorAll('#eduTbody tr[id^="edu-row-"]').length) {
                document.getElementById('eduTbody').innerHTML =
                    `<tr id="eduEmptyRow"><td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-mortarboard fs-3 d-block mb-2 opacity-25"></i>No education records yet.
                    </td></tr>`;
            }
        })
        .catch(err => {
            document.getElementById('delEduErr').textContent = err.message;
            document.getElementById('delEduErr').classList.remove('d-none');
        });
    });
    </script>
    <script>
    // ── Photo preview on edit page ─────────────────────────────────
    function previewPhoto(input) {
        if (!input.files[0]) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            // Replace existing img or placeholder
            const existing = document.getElementById('photoPreview');
            const placeholder = document.getElementById('photoPreviewPlaceholder');
            if (existing) {
                existing.src = e.target.result;
            } else if (placeholder) {
                const img = document.createElement('img');
                img.id = 'photoPreview';
                img.src = e.target.result;
                img.alt = 'Photo';
                img.style.cssText = 'width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #e9ecef;cursor:pointer;';
                img.onclick = () => document.getElementById('photoInput').click();
                placeholder.replaceWith(img);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
    </script>
    @endpush

</x-app-layout>
