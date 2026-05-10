<x-app-layout>
    <x-slot name="title">{{ $employee->full_name }} — Education</x-slot>

    <x-alert />

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Education Records</h5>
            <small class="text-muted">
                {{ $employee->full_name }}
                <span class="mx-1">·</span>
                {{ $employee->employee_code }}
            </small>
        </div>
        <div class="d-flex gap-2">
            @can('manageEducation', $employee)
            <button class="btn btn-primary" id="btnAdd">
                <i class="bi bi-plus-lg me-1"></i> Add Education
            </button>
            @endcan
            <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-mortarboard"></i></div>
                <div>
                    <div class="label">Total Records</div>
                    <div class="value" id="statTotal">{{ $educations->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="label">Latest Year</div>
                    <div class="value">{{ $educations->max('passing_year') ?? '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-building"></i></div>
                <div>
                    <div class="label">Institutions</div>
                    <div class="value">{{ $educations->unique('institute_name')->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-award"></i></div>
                <div>
                    <div class="label">Highest Level</div>
                    <div class="value" style="font-size:1rem;">{{ $educations->first()?->degreeType->name ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="hrms-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Education History</span>
            <span class="badge bg-secondary" id="recordCount">{{ $educations->count() }} records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Degree Type</th>
                        <th>Degree / Qualification</th>
                        <th>Institute</th>
                        <th class="text-center">Year</th>
                        <th class="text-center">Result</th>
                        <th>Board / University</th>
                        @can('manageEducation', $employee)
                        <th class="text-end">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody id="educationTbody">
                    @forelse($educations as $i => $edu)
                    <tr id="edu-row-{{ $edu->id }}">
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary">{{ $edu->degreeType->name }}</span>
                        </td>
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
                        @can('manageEducation', $employee)
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary btn-edit-edu" data-id="{{ $edu->id }}" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete-edu" data-id="{{ $edu->id }}" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                        @endcan
                    </tr>
                    @empty
                    <tr id="emptyRow">
                        <td colspan="@can('manageEducation', $employee) 8 @else 7 @endcan" class="text-center text-muted py-5">
                            <i class="bi bi-mortarboard fs-3 d-block mb-2 opacity-25"></i>
                            No education records yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('manageEducation', $employee)

    {{-- Add / Edit Modal --}}
    <div class="modal fade" id="eduModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eduModalTitle">Add Education Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="eduFormError" class="alert alert-danger d-none"></div>
                    <input type="hidden" id="eduEditId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Degree Type <span class="text-danger">*</span></label>
                            <select id="fieldDegreeType" class="form-select">
                                <option value="">— Select Degree Type —</option>
                                @foreach($degreeTypes as $dt)
                                <option value="{{ $dt->id }}">{{ $dt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                Degree / Qualification <span class="text-danger">*</span>
                            </label>
                            <select id="fieldDegreeName" class="form-select" disabled>
                                <option value="">— Select Degree Type first —</option>
                            </select>
                            <div id="degreeNameSpinner" class="text-muted small mt-1 d-none">
                                <span class="spinner-border spinner-border-sm me-1"></span> Loading…
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Institute Name <span class="text-danger">*</span></label>
                            <input type="text" id="fieldInstitute" class="form-control" placeholder="e.g. University of Dhaka" maxlength="200">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Passing Year <span class="text-danger">*</span></label>
                            <input type="number" id="fieldYear" class="form-control" min="1950" max="{{ date('Y') + 1 }}" placeholder="{{ date('Y') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Result / GPA <span class="text-muted small">(optional)</span></label>
                            <input type="text" id="fieldResult" class="form-control" placeholder="e.g. 3.85 / A+" maxlength="50">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Board / University <span class="text-muted small">(optional)</span></label>
                            <input type="text" id="fieldBoard" class="form-control" placeholder="e.g. Dhaka Board" maxlength="200">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSaveEdu">
                        <span id="btnSaveEduText">Save Record</span>
                        <span id="btnSaveEduSpinner" class="spinner-border spinner-border-sm d-none ms-1"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirm --}}
    <div class="modal fade" id="deleteEduModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Delete Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Remove this education record?<br>
                    <small class="text-muted">This cannot be undone.</small>
                    <div id="deleteEduError" class="alert alert-danger mt-2 d-none"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm" id="btnConfirmDeleteEdu">Delete</button>
                </div>
            </div>
        </div>
    </div>

    @endcan

    {{-- Toast --}}
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100">
        <div id="toast" class="toast align-items-center text-white border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="toastMsg"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    const CSRF      = document.querySelector('meta[name="csrf-token"]').content;
    const EMPLOYEE  = {{ $employee->id }};
    const BASE_URL  = `/employees/${EMPLOYEE}/education`;
    const NAMES_URL = `/degree-names/by-type`;

    const eduModal    = document.getElementById('eduModal')    ? new bootstrap.Modal(document.getElementById('eduModal'))    : null;
    const delEduModal = document.getElementById('deleteEduModal') ? new bootstrap.Modal(document.getElementById('deleteEduModal')) : null;
    const toastEl     = new bootstrap.Toast(document.getElementById('toast'), { delay: 3000 });

    let deleteEduId = null;
    let pendingDegreeNameId = null;

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showToast(msg, ok = true) {
        const el = document.getElementById('toast');
        el.className = `toast align-items-center text-white border-0 ${ok ? 'bg-success' : 'bg-danger'}`;
        document.getElementById('toastMsg').textContent = msg;
        toastEl.show();
    }

    function buildEduRow(e, idx) {
        const resultBadge = e.result
            ? `<span class="badge bg-success-subtle text-success">${escHtml(e.result)}</span>`
            : '<span class="text-muted">—</span>';
        return `<tr id="edu-row-${e.id}">
            <td class="text-muted">${idx}</td>
            <td><span class="badge bg-primary-subtle text-primary">${escHtml(e.degree_type_name)}</span></td>
            <td class="fw-semibold">${escHtml(e.degree_name_name)}</td>
            <td>${escHtml(e.institute_name)}</td>
            <td class="text-center">${e.passing_year}</td>
            <td class="text-center">${resultBadge}</td>
            <td class="text-muted small">${escHtml(e.board_university ?? '—')}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary btn-edit-edu" data-id="${e.id}" title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-delete-edu" data-id="${e.id}" title="Delete"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`;
    }

    function updateStats() {
        const rows = document.querySelectorAll('#educationTbody tr[id^="edu-row-"]');
        document.getElementById('statTotal').textContent = rows.length;
        document.getElementById('recordCount').textContent = `${rows.length} records`;
    }

    // ── Cascading Degree Name Dropdown ─────────────────────────────
    function loadDegreeNames(typeId, selectAfter = null) {
        const nameSelect = document.getElementById('fieldDegreeName');
        const spinner    = document.getElementById('degreeNameSpinner');
        if (!typeId) {
            nameSelect.innerHTML = '<option value="">— Select Degree Type first —</option>';
            nameSelect.disabled  = true;
            return;
        }
        nameSelect.disabled  = true;
        spinner.classList.remove('d-none');
        nameSelect.innerHTML = '<option value="">Loading…</option>';

        fetch(`${NAMES_URL}/${typeId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                nameSelect.innerHTML = '<option value="">— Select Qualification —</option>';
                if (data.names.length === 0) {
                    nameSelect.innerHTML += '<option disabled>No names for this type</option>';
                } else {
                    data.names.forEach(n => {
                        nameSelect.innerHTML += `<option value="${n.id}">${escHtml(n.name)}</option>`;
                    });
                }
                nameSelect.disabled = false;
                if (selectAfter) {
                    nameSelect.value = selectAfter;
                }
            })
            .catch(() => {
                nameSelect.innerHTML = '<option value="">Failed to load</option>';
                nameSelect.disabled  = false;
            })
            .finally(() => spinner.classList.add('d-none'));
    }

    document.getElementById('fieldDegreeType')?.addEventListener('change', function() {
        pendingDegreeNameId = null;
        loadDegreeNames(this.value);
    });

    // ── Reset modal form ───────────────────────────────────────────
    function resetEduForm() {
        document.getElementById('eduEditId').value       = '';
        document.getElementById('fieldDegreeType').value = '';
        document.getElementById('fieldDegreeName').innerHTML = '<option value="">— Select Degree Type first —</option>';
        document.getElementById('fieldDegreeName').disabled  = true;
        document.getElementById('fieldInstitute').value  = '';
        document.getElementById('fieldYear').value       = '';
        document.getElementById('fieldResult').value     = '';
        document.getElementById('fieldBoard').value      = '';
        document.getElementById('eduFormError').classList.add('d-none');
        pendingDegreeNameId = null;
    }

    // ── Add ────────────────────────────────────────────────────────
    document.getElementById('btnAdd')?.addEventListener('click', () => {
        resetEduForm();
        document.getElementById('eduModalTitle').textContent = 'Add Education Record';
        eduModal.show();
    });

    // ── Edit ───────────────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-edit-edu');
        if (!btn || !eduModal) return;
        const id = btn.dataset.id;
        fetch(`${BASE_URL}/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                const ed = data.education;
                resetEduForm();
                document.getElementById('eduEditId').value       = ed.id;
                document.getElementById('fieldDegreeType').value = ed.degree_type_id;
                document.getElementById('fieldInstitute').value  = ed.institute_name;
                document.getElementById('fieldYear').value       = ed.passing_year;
                document.getElementById('fieldResult').value     = ed.result ?? '';
                document.getElementById('fieldBoard').value      = ed.board_university ?? '';
                pendingDegreeNameId = ed.degree_name_id;
                loadDegreeNames(ed.degree_type_id, ed.degree_name_id);
                document.getElementById('eduModalTitle').textContent = 'Edit Education Record';
                eduModal.show();
            });
    });

    // ── Save ───────────────────────────────────────────────────────
    document.getElementById('btnSaveEdu')?.addEventListener('click', () => {
        const id           = document.getElementById('eduEditId').value;
        const degreeTypeId = document.getElementById('fieldDegreeType').value;
        const degreeNameId = document.getElementById('fieldDegreeName').value;
        const institute    = document.getElementById('fieldInstitute').value.trim();
        const year         = document.getElementById('fieldYear').value;
        const result       = document.getElementById('fieldResult').value.trim() || null;
        const board        = document.getElementById('fieldBoard').value.trim() || null;
        const errorEl      = document.getElementById('eduFormError');

        if (!degreeTypeId || !degreeNameId || !institute || !year) {
            errorEl.textContent = 'Degree Type, Degree Name, Institute, and Passing Year are required.';
            errorEl.classList.remove('d-none');
            return;
        }

        const isEdit = !!id;
        const url    = isEdit ? `${BASE_URL}/${id}` : BASE_URL;
        const method = isEdit ? 'PATCH' : 'POST';

        document.getElementById('btnSaveEduSpinner').classList.remove('d-none');
        document.getElementById('btnSaveEduText').textContent = 'Saving…';
        errorEl.classList.add('d-none');

        fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                degree_type_id:  degreeTypeId,
                degree_name_id:  degreeNameId,
                institute_name:  institute,
                passing_year:    parseInt(year),
                result,
                board_university: board,
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message ?? 'Save failed.');
            eduModal.hide();
            showToast(data.message);
            const ed      = data.education;
            const existing = document.getElementById(`edu-row-${ed.id}`);
            document.getElementById('emptyRow')?.remove();
            const rows = document.querySelectorAll('#educationTbody tr[id^="edu-row-"]');
            const idx  = existing ? existing.querySelector('td')?.textContent ?? '—' : rows.length + 1;
            if (existing) {
                existing.outerHTML = buildEduRow(ed, idx);
            } else {
                document.getElementById('educationTbody').insertAdjacentHTML('beforeend', buildEduRow(ed, rows.length + 1));
            }
            updateStats();
        })
        .catch(err => {
            errorEl.textContent = err.message;
            errorEl.classList.remove('d-none');
        })
        .finally(() => {
            document.getElementById('btnSaveEduSpinner').classList.add('d-none');
            document.getElementById('btnSaveEduText').textContent = 'Save Record';
        });
    });

    // ── Delete ─────────────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-delete-edu');
        if (!btn || !delEduModal) return;
        deleteEduId = btn.dataset.id;
        document.getElementById('deleteEduError').classList.add('d-none');
        delEduModal.show();
    });

    document.getElementById('btnConfirmDeleteEdu')?.addEventListener('click', () => {
        fetch(`${BASE_URL}/${deleteEduId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            delEduModal.hide();
            document.getElementById(`edu-row-${deleteEduId}`)?.remove();
            showToast(data.message);
            updateStats();
            if (!document.querySelectorAll('#educationTbody tr[id^="edu-row-"]').length) {
                document.getElementById('educationTbody').innerHTML =
                    `<tr id="emptyRow"><td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-mortarboard fs-3 d-block mb-2 opacity-25"></i>No education records yet.
                    </td></tr>`;
            }
        })
        .catch(err => {
            document.getElementById('deleteEduError').textContent = err.message;
            document.getElementById('deleteEduError').classList.remove('d-none');
        });
    });
    </script>
    @endpush
</x-app-layout>
