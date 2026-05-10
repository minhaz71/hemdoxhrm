<x-app-layout>
    <x-slot name="title">Degree Names</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Degree Names</h5>
            <small class="text-muted">Manage specific degrees and qualifications</small>
        </div>
        <button class="btn btn-primary" id="btnAdd">
            <i class="bi bi-plus-lg me-1"></i> Add Degree Name
        </button>
    </div>

    <div class="hrms-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>All Degree Names</span>
            <span class="badge bg-secondary">{{ $degreeNames->total() }} total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Name</th>
                        <th>Linked Type</th>
                        <th class="text-center">Used In</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="namesTbody">
                    @foreach($degreeNames as $i => $dn)
                    <tr id="row-{{ $dn->id }}">
                        <td class="text-muted">{{ $degreeNames->firstItem() + $i }}</td>
                        <td class="fw-semibold">{{ $dn->name }}</td>
                        <td>
                            @if($dn->degreeType)
                                <span class="badge bg-primary-subtle text-primary">{{ $dn->degreeType->name }}</span>
                            @else
                                <span class="text-muted small">All types</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $dn->employee_educations_count }}</td>
                        <td class="text-center">
                            <span class="badge {{ $dn->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                {{ ucfirst($dn->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary btn-edit" data-id="{{ $dn->id }}" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm {{ $dn->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }} btn-toggle"
                                    data-id="{{ $dn->id }}" title="Toggle Status">
                                <i class="bi bi-{{ $dn->status === 'active' ? 'pause' : 'play' }}"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete"
                                    data-id="{{ $dn->id }}" data-name="{{ $dn->name }}"
                                    {{ $dn->employee_educations_count > 0 ? 'disabled title=Cannot delete: used in records' : 'title=Delete' }}>
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                    @if($degreeNames->isEmpty())
                    <tr id="emptyRow">
                        <td colspan="6" class="text-center text-muted py-4">No degree names yet. Add one to get started.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if($degreeNames->hasPages())
        <div class="p-3">{{ $degreeNames->links() }}</div>
        @endif
    </div>

    {{-- Add / Edit Modal --}}
    <div class="modal fade" id="formModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Degree Name</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="formError" class="alert alert-danger d-none"></div>
                    <input type="hidden" id="editId">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" id="fieldName" class="form-control" placeholder="e.g. Computer Science" maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            Linked Degree Type
                            <span class="text-muted small ms-1">(optional — for cascading dropdown)</span>
                        </label>
                        <select id="fieldDegreeType" class="form-select">
                            <option value="">— All Types (always visible) —</option>
                            @foreach($degreeTypes as $dt)
                            <option value="{{ $dt->id }}">{{ $dt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select id="fieldStatus" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSave">
                        <span id="btnSaveText">Save</span>
                        <span id="btnSaveSpinner" class="spinner-border spinner-border-sm d-none ms-1"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirm Modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Delete <strong id="deleteName"></strong>?<br>
                    <small class="text-muted">This cannot be undone.</small>
                    <div id="deleteError" class="alert alert-danger mt-2 d-none"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm" id="btnConfirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

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
    const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
    const modal  = new bootstrap.Modal(document.getElementById('formModal'));
    const delMod = new bootstrap.Modal(document.getElementById('deleteModal'));
    const toast  = new bootstrap.Toast(document.getElementById('toast'), { delay: 3000 });
    let deleteId = null;

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showToast(msg, ok = true) {
        const el = document.getElementById('toast');
        el.className = `toast align-items-center text-white border-0 ${ok ? 'bg-success' : 'bg-danger'}`;
        document.getElementById('toastMsg').textContent = msg;
        toast.show();
    }

    function buildRow(n) {
        const typeBadge   = n.degree_type_name !== '—'
            ? `<span class="badge bg-primary-subtle text-primary">${escHtml(n.degree_type_name)}</span>`
            : '<span class="text-muted small">All types</span>';
        const statusBadge = n.status === 'active'
            ? '<span class="badge bg-success-subtle text-success">Active</span>'
            : '<span class="badge bg-secondary-subtle text-secondary">Inactive</span>';
        const toggleIcon  = n.status === 'active' ? 'pause' : 'play';
        const toggleClass = n.status === 'active' ? 'btn-outline-warning' : 'btn-outline-success';
        const delDisabled = n.employee_educations_count > 0 ? 'disabled title="Cannot delete: used in records"' : 'title="Delete"';

        return `<tr id="row-${n.id}">
            <td class="text-muted">—</td>
            <td class="fw-semibold">${escHtml(n.name)}</td>
            <td>${typeBadge}</td>
            <td class="text-center">${n.employee_educations_count}</td>
            <td class="text-center">${statusBadge}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${n.id}" title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm ${toggleClass} btn-toggle" data-id="${n.id}" title="Toggle Status"><i class="bi bi-${toggleIcon}"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${n.id}" data-name="${escHtml(n.name)}" ${delDisabled}><i class="bi bi-trash"></i></button>
            </td>
        </tr>`;
    }

    // ── Add ────────────────────────────────────────────────────────
    document.getElementById('btnAdd').addEventListener('click', () => {
        document.getElementById('editId').value         = '';
        document.getElementById('fieldName').value      = '';
        document.getElementById('fieldDegreeType').value = '';
        document.getElementById('fieldStatus').value    = 'active';
        document.getElementById('formError').classList.add('d-none');
        document.getElementById('modalTitle').textContent = 'Add Degree Name';
        modal.show();
    });

    // ── Edit ───────────────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-edit');
        if (!btn) return;
        fetch(`/degree-names/${btn.dataset.id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                const n = data.degree_name;
                document.getElementById('editId').value          = n.id;
                document.getElementById('fieldName').value       = n.name;
                document.getElementById('fieldDegreeType').value = n.degree_type_id ?? '';
                document.getElementById('fieldStatus').value     = n.status;
                document.getElementById('formError').classList.add('d-none');
                document.getElementById('modalTitle').textContent = 'Edit Degree Name';
                modal.show();
            });
    });

    // ── Save ───────────────────────────────────────────────────────
    document.getElementById('btnSave').addEventListener('click', () => {
        const id           = document.getElementById('editId').value;
        const name         = document.getElementById('fieldName').value.trim();
        const degreeTypeId = document.getElementById('fieldDegreeType').value || null;
        const status       = document.getElementById('fieldStatus').value;

        if (!name) {
            document.getElementById('formError').textContent = 'Name is required.';
            document.getElementById('formError').classList.remove('d-none');
            return;
        }

        const isEdit = !!id;
        const url    = isEdit ? `/degree-names/${id}` : '/degree-names';
        const method = isEdit ? 'PATCH' : 'POST';

        document.getElementById('btnSaveSpinner').classList.remove('d-none');
        document.getElementById('btnSaveText').textContent = 'Saving…';

        fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ name, degree_type_id: degreeTypeId, status }),
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            modal.hide();
            showToast(data.message);
            const n = data.degree_name;
            const existing = document.getElementById(`row-${n.id}`);
            const emptyRow = document.getElementById('emptyRow');
            if (emptyRow) emptyRow.remove();
            if (existing) {
                existing.outerHTML = buildRow(n);
            } else {
                document.getElementById('namesTbody').insertAdjacentHTML('afterbegin', buildRow(n));
            }
        })
        .catch(err => {
            document.getElementById('formError').textContent = err.message;
            document.getElementById('formError').classList.remove('d-none');
        })
        .finally(() => {
            document.getElementById('btnSaveSpinner').classList.add('d-none');
            document.getElementById('btnSaveText').textContent = 'Save';
        });
    });

    // ── Toggle ─────────────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-toggle');
        if (!btn) return;
        fetch(`/degree-names/${btn.dataset.id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.message);
            const row = document.getElementById(`row-${btn.dataset.id}`);
            const badge = row.querySelector('.badge:last-of-type');
            if (data.status === 'active') {
                badge.className = 'badge bg-success-subtle text-success';
                badge.textContent = 'Active';
                btn.className = 'btn btn-sm btn-outline-warning btn-toggle';
                btn.innerHTML = '<i class="bi bi-pause"></i>';
            } else {
                badge.className = 'badge bg-secondary-subtle text-secondary';
                badge.textContent = 'Inactive';
                btn.className = 'btn btn-sm btn-outline-success btn-toggle';
                btn.innerHTML = '<i class="bi bi-play"></i>';
            }
        });
    });

    // ── Delete ─────────────────────────────────────────────────────
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-delete');
        if (!btn || btn.disabled) return;
        deleteId = btn.dataset.id;
        document.getElementById('deleteName').textContent = btn.dataset.name;
        document.getElementById('deleteError').classList.add('d-none');
        delMod.show();
    });

    document.getElementById('btnConfirmDelete').addEventListener('click', () => {
        fetch(`/degree-names/${deleteId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            delMod.hide();
            document.getElementById(`row-${deleteId}`)?.remove();
            showToast(data.message);
        })
        .catch(err => {
            document.getElementById('deleteError').textContent = err.message;
            document.getElementById('deleteError').classList.remove('d-none');
        });
    });
    </script>
    @endpush
</x-app-layout>
