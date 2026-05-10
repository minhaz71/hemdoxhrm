<x-app-layout title="Company Profile">

@push('styles')
<style>
.tab-pane-section { padding: 0; }
.office-hour-row { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
.office-hour-row:last-child { border-bottom: none; }
.day-label { width: 90px; font-weight: 500; font-size: .85rem; }
.shift-swatch { display: inline-block; width: 14px; height: 14px; border-radius: 3px; vertical-align: middle; margin-right: 4px; }
.dept-indent { padding-left: 24px; }
.badge-hq { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; font-size: .7rem; }
</style>
@endpush

<div class="page-body">

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Company Profile</h4>
        <p class="text-muted mb-0 small">Manage company information, branches, departments and shifts.</p>
    </div>
</div>

{{-- Tab Nav --}}
<div class="hrms-card">
    <div class="card-header p-0">
        <ul class="nav nav-tabs border-0 px-3 pt-2" id="companyTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-company">
                    <i class="bi bi-building me-1"></i> Company Info
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-branches">
                    <i class="bi bi-geo-alt me-1"></i> Branches
                    <span class="badge bg-secondary ms-1">{{ $branches->count() }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-departments">
                    <i class="bi bi-diagram-3 me-1"></i> Departments
                    <span class="badge bg-secondary ms-1">{{ $departments->count() }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-shifts">
                    <i class="bi bi-clock me-1"></i> Shifts
                    <span class="badge bg-secondary ms-1">{{ $shifts->count() }}</span>
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content p-4">

        {{-- ── Company Info Tab ────────────────────────────────── --}}
        <div class="tab-pane fade show active" id="tab-company">
            <form id="companyForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="{{ $company?->name }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Legal Name</label>
                        <input type="text" name="legal_name" class="form-control" value="{{ $company?->legal_name }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Registration Number</label>
                        <input type="text" name="registration_number" class="form-control" value="{{ $company?->registration_number }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tax / VAT Number</label>
                        <input type="text" name="tax_number" class="form-control" value="{{ $company?->tax_number }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ $company?->email }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ $company?->phone }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Website</label>
                        <input type="url" name="website" class="form-control" placeholder="https://" value="{{ $company?->website }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Industry</label>
                        <input type="text" name="industry" class="form-control" value="{{ $company?->industry }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ $company?->address }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">City</label>
                        <input type="text" name="city" class="form-control" value="{{ $company?->city }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">State / Province</label>
                        <input type="text" name="state" class="form-control" value="{{ $company?->state }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Country</label>
                        <input type="text" name="country" class="form-control" value="{{ $company?->country }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Postal Code</label>
                        <input type="text" name="postal_code" class="form-control" value="{{ $company?->postal_code }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $company?->description }}</textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-floppy me-1"></i> Save Company Profile
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ── Branches Tab ─────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-branches">
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary btn-sm" onclick="openBranchModal()">
                    <i class="bi bi-plus-lg me-1"></i> Add Branch
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="branchesTable">
                    <thead class="table-light">
                        <tr>
                            <th>Branch</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Office Hours</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                        <tr id="branch-row-{{ $branch->id }}">
                            <td>
                                <div class="fw-semibold">{{ $branch->name }}</div>
                                @if($branch->code) <div class="text-muted small">{{ $branch->code }}</div> @endif
                                @if($branch->is_headquarters)
                                    <span class="badge badge-hq">HQ</span>
                                @endif
                            </td>
                            <td class="small">
                                {{ collect([$branch->city, $branch->state, $branch->country])->filter()->implode(', ') ?: '—' }}
                            </td>
                            <td class="small">
                                {{ $branch->email ?: $branch->phone ?: '—' }}
                            </td>
                            <td>
                                <span class="badge {{ $branch->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="small">
                                @php
                                    $workingDays = $branch->officeHours->where('is_working', true);
                                    $dayNames = $workingDays->map(fn($oh) => \App\Models\OfficeHour::shortDayName($oh->day_of_week));
                                @endphp
                                {{ $dayNames->implode(', ') ?: '—' }}
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="editBranch({{ $branch->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-{{ $branch->is_active ? 'warning' : 'success' }}"
                                        onclick="toggleBranch({{ $branch->id }}, this)">
                                    <i class="bi bi-{{ $branch->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="deleteBranch({{ $branch->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr id="branch-empty">
                            <td colspan="6" class="text-center text-muted py-4">No branches yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Departments Tab ──────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-departments">
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary btn-sm" onclick="openDeptModal()">
                    <i class="bi bi-plus-lg me-1"></i> Add Department
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="deptsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Department</th>
                            <th>Branch</th>
                            <th>Parent</th>
                            <th>Manager</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $dept)
                        <tr id="dept-row-{{ $dept->id }}">
                            <td>
                                <div class="fw-semibold {{ $dept->parent_id ? 'dept-indent' : '' }}">
                                    @if($dept->parent_id)<i class="bi bi-arrow-return-right text-muted me-1"></i>@endif
                                    {{ $dept->name }}
                                </div>
                                @if($dept->code) <div class="text-muted small">{{ $dept->code }}</div> @endif
                            </td>
                            <td class="small">{{ $dept->branch?->name ?? '<span class="text-muted">All Branches</span>' }}</td>
                            <td class="small">{{ $dept->parent?->name ?? '—' }}</td>
                            <td class="small">{{ $dept->manager_name ?: '—' }}</td>
                            <td>
                                <span class="badge {{ $dept->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $dept->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="editDept({{ $dept->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="deleteDept({{ $dept->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr id="dept-empty">
                            <td colspan="6" class="text-center text-muted py-4">No departments yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Shifts Tab ───────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-shifts">
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary btn-sm" onclick="openShiftModal()">
                    <i class="bi bi-plus-lg me-1"></i> Add Shift
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="shiftsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Shift</th>
                            <th>Timing</th>
                            <th>Break</th>
                            <th>Working Days</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shifts as $shift)
                        <tr id="shift-row-{{ $shift->id }}">
                            <td>
                                <span class="shift-swatch" style="background:{{ $shift->color }}"></span>
                                <span class="fw-semibold">{{ $shift->name }}</span>
                                @if($shift->is_overnight)
                                    <span class="badge bg-info-subtle text-info ms-1 small">Overnight</span>
                                @endif
                            </td>
                            <td class="small">
                                {{ substr($shift->start_time, 0, 5) }} – {{ substr($shift->end_time, 0, 5) }}
                            </td>
                            <td class="small">{{ $shift->break_minutes ? $shift->break_minutes . ' min' : '—' }}</td>
                            <td class="small">{{ $shift->working_day_names }}</td>
                            <td>
                                <span class="badge {{ $shift->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $shift->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="editShift({{ $shift->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-{{ $shift->is_active ? 'warning' : 'success' }}"
                                        onclick="toggleShift({{ $shift->id }}, this)">
                                    <i class="bi bi-{{ $shift->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="deleteShift({{ $shift->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr id="shift-empty">
                            <td colspan="6" class="text-center text-muted py-4">No shifts yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- /tab-content --}}
</div>{{-- /hrms-card --}}

</div>{{-- /page-body --}}

{{-- ══ Branch Modal ══════════════════════════════════════════════════ --}}
<div class="modal fade" id="branchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="branchModalTitle">Add Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="branchId">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" id="branchName" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Branch Code</label>
                        <input type="text" id="branchCode" class="form-control" placeholder="e.g. BR-01">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" id="branchEmail" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" id="branchPhone" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea id="branchAddress" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">City</label>
                        <input type="text" id="branchCity" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">State</label>
                        <input type="text" id="branchState" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Country</label>
                        <input type="text" id="branchCountry" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Postal Code</label>
                        <input type="text" id="branchPostal" class="form-control">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="branchHQ">
                            <label class="form-check-label" for="branchHQ">Mark as Headquarters</label>
                        </div>
                    </div>

                    {{-- Office Hours --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Office Hours</label>
                        <div id="officeHoursEditor">
                            @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $dayIndex => $dayName)
                            <div class="office-hour-row" data-day="{{ $dayIndex }}">
                                <div class="day-label">{{ $dayName }}</div>
                                <div class="form-check form-switch me-2">
                                    <input class="form-check-input oh-working" type="checkbox" id="oh_day_{{ $dayIndex }}"
                                           {{ in_array($dayIndex, [1,2,3,4,5]) ? 'checked' : '' }}
                                           onchange="toggleOHRow(this, {{ $dayIndex }})">
                                </div>
                                <div class="oh-times d-flex gap-2 align-items-center" id="oh-times-{{ $dayIndex }}"
                                     style="{{ in_array($dayIndex, [1,2,3,4,5]) ? '' : 'opacity:.3;pointer-events:none;' }}">
                                    <input type="time" class="form-control form-control-sm oh-open"  style="width:120px"
                                           value="{{ in_array($dayIndex, [1,2,3,4,5]) ? '09:00' : '' }}">
                                    <span class="text-muted">–</span>
                                    <input type="time" class="form-control form-control-sm oh-close" style="width:120px"
                                           value="{{ in_array($dayIndex, [1,2,3,4,5]) ? '17:00' : '' }}">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="saveBranch()">
                    <i class="bi bi-floppy me-1"></i> Save Branch
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══ Department Modal ══════════════════════════════════════════════ --}}
<div class="modal fade" id="deptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deptModalTitle">Add Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="deptId">
                <div class="row g-3">
                    <div class="col-8">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" id="deptName" class="form-control" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Code</label>
                        <input type="text" id="deptCode" class="form-control" placeholder="e.g. ENG">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Branch</label>
                        <select id="deptBranch" class="form-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Parent Department</label>
                        <select id="deptParent" class="form-select">
                            <option value="">— None —</option>
                            @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Department Manager</label>
                        <input type="text" id="deptManager" class="form-control" placeholder="Manager name">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" id="deptEmail" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea id="deptDesc" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12 d-none" id="deptActiveWrap">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="deptActive" checked>
                            <label class="form-check-label" for="deptActive">Active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="saveDept()">
                    <i class="bi bi-floppy me-1"></i> Save Department
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══ Shift Modal ════════════════════════════════════════════════════ --}}
<div class="modal fade" id="shiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shiftModalTitle">Add Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="shiftId">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Shift Name <span class="text-danger">*</span></label>
                        <input type="text" id="shiftName" class="form-control" placeholder="e.g. Morning Shift">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Color</label>
                        <input type="color" id="shiftColor" class="form-control form-control-color w-100" value="#4e9af1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                        <input type="time" id="shiftStart" class="form-control" value="09:00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                        <input type="time" id="shiftEnd" class="form-control" value="17:00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Break (minutes)</label>
                        <input type="number" id="shiftBreak" class="form-control" value="60" min="0" max="480">
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="shiftOvernight">
                            <label class="form-check-label" for="shiftOvernight">Overnight Shift</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Working Days</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach([1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',0=>'Sun'] as $day => $label)
                            <div class="form-check">
                                <input class="form-check-input shift-day" type="checkbox" id="sd_{{ $day }}"
                                       value="{{ $day }}" {{ $day >= 1 && $day <= 5 ? 'checked' : '' }}>
                                <label class="form-check-label" for="sd_{{ $day }}">{{ $label }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12 d-none" id="shiftActiveWrap">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="shiftActive" checked>
                            <label class="form-check-label" for="shiftActive">Active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="saveShift()">
                    <i class="bi bi-floppy me-1"></i> Save Shift
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Toast --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="toast" class="toast align-items-center text-white border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function toast(msg, type = 'success') {
    const el = document.getElementById('toast');
    el.className = `toast align-items-center text-white border-0 bg-${type}`;
    document.getElementById('toastMsg').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(el, { delay: 3000 }).show();
}

async function apiFetch(url, method, body = null) {
    const opts = {
        method,
        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' },
    };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(url, opts);
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Request failed');
    return data;
}

// ── Company Form ─────────────────────────────────────────────────────
document.getElementById('companyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const body = Object.fromEntries(fd.entries());
    try {
        await apiFetch('{{ route("company.upsert") }}', 'POST', body);
        toast('Company profile saved.');
    } catch(err) { toast(err.message, 'danger'); }
});

// ── Branch ───────────────────────────────────────────────────────────
const branchModal = new bootstrap.Modal(document.getElementById('branchModal'));

function openBranchModal() {
    document.getElementById('branchModalTitle').textContent = 'Add Branch';
    document.getElementById('branchId').value    = '';
    document.getElementById('branchName').value  = '';
    document.getElementById('branchCode').value  = '';
    document.getElementById('branchEmail').value = '';
    document.getElementById('branchPhone').value = '';
    document.getElementById('branchAddress').value = '';
    document.getElementById('branchCity').value   = '';
    document.getElementById('branchState').value  = '';
    document.getElementById('branchCountry').value = '';
    document.getElementById('branchPostal').value  = '';
    document.getElementById('branchHQ').checked = false;
    resetOfficeHours();
    branchModal.show();
}

function resetOfficeHours() {
    document.querySelectorAll('.office-hour-row').forEach(row => {
        const day = parseInt(row.dataset.day);
        const isWork = [1,2,3,4,5].includes(day);
        row.querySelector('.oh-working').checked = isWork;
        row.querySelector('.oh-open').value  = isWork ? '09:00' : '';
        row.querySelector('.oh-close').value = isWork ? '17:00' : '';
        toggleOHRow(row.querySelector('.oh-working'), day);
    });
}

function toggleOHRow(checkbox, day) {
    const timesDiv = document.getElementById(`oh-times-${day}`);
    timesDiv.style.opacity         = checkbox.checked ? '1'    : '0.3';
    timesDiv.style.pointerEvents   = checkbox.checked ? 'auto' : 'none';
}

function gatherOfficeHours() {
    const hours = [];
    document.querySelectorAll('.office-hour-row').forEach(row => {
        const day  = parseInt(row.dataset.day);
        const isW  = row.querySelector('.oh-working').checked;
        hours.push({
            day_of_week: day,
            is_working:  isW,
            open_time:   row.querySelector('.oh-open').value  || null,
            close_time:  row.querySelector('.oh-close').value || null,
        });
    });
    return hours;
}

function editBranch(id) {
    // Fetch from existing table data
    const row = document.getElementById(`branch-row-${id}`);
    document.getElementById('branchModalTitle').textContent = 'Edit Branch';
    document.getElementById('branchId').value = id;
    // Populate from server — in a real app you'd store data attributes or fetch
    // Here we simply open the modal; for full data we'd fetch via AJAX
    branchModal.show();
}

async function saveBranch() {
    const id = document.getElementById('branchId').value;
    const body = {
        name:            document.getElementById('branchName').value,
        code:            document.getElementById('branchCode').value,
        email:           document.getElementById('branchEmail').value,
        phone:           document.getElementById('branchPhone').value,
        address:         document.getElementById('branchAddress').value,
        city:            document.getElementById('branchCity').value,
        state:           document.getElementById('branchState').value,
        country:         document.getElementById('branchCountry').value,
        postal_code:     document.getElementById('branchPostal').value,
        is_headquarters: document.getElementById('branchHQ').checked ? 1 : 0,
        office_hours:    gatherOfficeHours(),
    };
    try {
        const url    = id ? `/company/branches/${id}` : '{{ route("company.branches.store") }}';
        const method = id ? 'PATCH' : 'POST';
        await apiFetch(url, method, body);
        toast(id ? 'Branch updated.' : 'Branch created.');
        branchModal.hide();
        setTimeout(() => location.reload(), 800);
    } catch(err) { toast(err.message, 'danger'); }
}

async function toggleBranch(id, btn) {
    try {
        const data = await apiFetch(`/company/branches/${id}/toggle`, 'PATCH');
        toast(data.message);
        setTimeout(() => location.reload(), 600);
    } catch(err) { toast(err.message, 'danger'); }
}

async function deleteBranch(id) {
    if (!confirm('Delete this branch?')) return;
    try {
        await apiFetch(`/company/branches/${id}`, 'DELETE');
        document.getElementById(`branch-row-${id}`)?.remove();
        toast('Branch deleted.');
    } catch(err) { toast(err.message, 'danger'); }
}

// ── Departments ──────────────────────────────────────────────────────
const deptModal = new bootstrap.Modal(document.getElementById('deptModal'));

function openDeptModal() {
    document.getElementById('deptModalTitle').textContent = 'Add Department';
    document.getElementById('deptId').value      = '';
    document.getElementById('deptName').value    = '';
    document.getElementById('deptCode').value    = '';
    document.getElementById('deptBranch').value  = '';
    document.getElementById('deptParent').value  = '';
    document.getElementById('deptManager').value = '';
    document.getElementById('deptEmail').value   = '';
    document.getElementById('deptDesc').value    = '';
    document.getElementById('deptActiveWrap').classList.add('d-none');
    deptModal.show();
}

function editDept(id) {
    document.getElementById('deptModalTitle').textContent = 'Edit Department';
    document.getElementById('deptId').value = id;
    document.getElementById('deptActiveWrap').classList.remove('d-none');
    deptModal.show();
}

async function saveDept() {
    const id = document.getElementById('deptId').value;
    const body = {
        name:         document.getElementById('deptName').value,
        code:         document.getElementById('deptCode').value,
        branch_id:    document.getElementById('deptBranch').value || null,
        parent_id:    document.getElementById('deptParent').value || null,
        manager_name: document.getElementById('deptManager').value,
        email:        document.getElementById('deptEmail').value,
        description:  document.getElementById('deptDesc').value,
    };
    if (id) body.is_active = document.getElementById('deptActive').checked ? 1 : 0;

    try {
        const url    = id ? `/company/departments/${id}` : '{{ route("company.departments.store") }}';
        const method = id ? 'PATCH' : 'POST';
        await apiFetch(url, method, body);
        toast(id ? 'Department updated.' : 'Department created.');
        deptModal.hide();
        setTimeout(() => location.reload(), 800);
    } catch(err) { toast(err.message, 'danger'); }
}

async function deleteDept(id) {
    if (!confirm('Delete this department?')) return;
    try {
        await apiFetch(`/company/departments/${id}`, 'DELETE');
        document.getElementById(`dept-row-${id}`)?.remove();
        toast('Department deleted.');
    } catch(err) { toast(err.message, 'danger'); }
}

// ── Shifts ───────────────────────────────────────────────────────────
const shiftModal = new bootstrap.Modal(document.getElementById('shiftModal'));

function openShiftModal() {
    document.getElementById('shiftModalTitle').textContent = 'Add Shift';
    document.getElementById('shiftId').value    = '';
    document.getElementById('shiftName').value  = '';
    document.getElementById('shiftStart').value = '09:00';
    document.getElementById('shiftEnd').value   = '17:00';
    document.getElementById('shiftBreak').value = '60';
    document.getElementById('shiftColor').value = '#4e9af1';
    document.getElementById('shiftOvernight').checked = false;
    document.getElementById('shiftActiveWrap').classList.add('d-none');
    document.querySelectorAll('.shift-day').forEach(cb => {
        cb.checked = [1,2,3,4,5].includes(parseInt(cb.value));
    });
    shiftModal.show();
}

function editShift(id) {
    document.getElementById('shiftModalTitle').textContent = 'Edit Shift';
    document.getElementById('shiftId').value = id;
    document.getElementById('shiftActiveWrap').classList.remove('d-none');
    shiftModal.show();
}

async function saveShift() {
    const id = document.getElementById('shiftId').value;
    const working_days = Array.from(document.querySelectorAll('.shift-day:checked'))
                              .map(cb => parseInt(cb.value));
    const body = {
        name:          document.getElementById('shiftName').value,
        start_time:    document.getElementById('shiftStart').value,
        end_time:      document.getElementById('shiftEnd').value,
        break_minutes: parseInt(document.getElementById('shiftBreak').value) || 0,
        color:         document.getElementById('shiftColor').value,
        is_overnight:  document.getElementById('shiftOvernight').checked ? 1 : 0,
        working_days,
    };
    if (id) body.is_active = document.getElementById('shiftActive').checked ? 1 : 0;

    try {
        const url    = id ? `/company/shifts/${id}` : '{{ route("company.shifts.store") }}';
        const method = id ? 'PATCH' : 'POST';
        await apiFetch(url, method, body);
        toast(id ? 'Shift updated.' : 'Shift created.');
        shiftModal.hide();
        setTimeout(() => location.reload(), 800);
    } catch(err) { toast(err.message, 'danger'); }
}

async function toggleShift(id, btn) {
    try {
        const data = await apiFetch(`/company/shifts/${id}/toggle`, 'PATCH');
        toast(data.message);
        setTimeout(() => location.reload(), 600);
    } catch(err) { toast(err.message, 'danger'); }
}

async function deleteShift(id) {
    if (!confirm('Delete this shift?')) return;
    try {
        await apiFetch(`/company/shifts/${id}`, 'DELETE');
        document.getElementById(`shift-row-${id}`)?.remove();
        toast('Shift deleted.');
    } catch(err) { toast(err.message, 'danger'); }
}
</script>
@endpush

</x-app-layout>
