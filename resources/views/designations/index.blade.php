<x-app-layout>
    <x-slot name="title">Designations</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Designations</h5>
            <small class="text-muted">Manage job designations used across the system</small>
        </div>
        <div class="d-flex gap-2">
            @if($isAdmin)
            <button class="btn btn-sm btn-outline-secondary" id="btnSettings" title="Access Settings">
                <i class="bi bi-gear"></i>
            </button>
            @endif
            <button class="btn btn-primary btn-sm" id="btnAdd">
                <i class="bi bi-plus-lg me-1"></i>New Designation
            </button>
        </div>
    </div>

    {{-- Settings panel (admin only) --}}
    @if($isAdmin)
    <div id="settingsPanel" class="hrms-card p-4 mb-4" style="display:none;">
        <h6 class="fw-semibold mb-3" style="font-size:.85rem;">
            <i class="bi bi-shield-lock me-2 text-primary"></i>Access Settings
        </h6>
        <div class="d-flex align-items-center gap-3">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="toggleHrAccess"
                       {{ $hrAccessEnabled ? 'checked' : '' }} style="width:2.5rem;height:1.25rem;">
                <label class="form-check-label fw-semibold ms-2" for="toggleHrAccess">
                    Allow HR to manage designations
                </label>
            </div>
            <span id="hrAccessStatus" class="badge {{ $hrAccessEnabled ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                {{ $hrAccessEnabled ? 'Enabled' : 'Disabled' }}
            </span>
        </div>
        <small class="text-muted d-block mt-2">
            When enabled, HR users can create, edit, and delete designations.
        </small>
    </div>
    @endif

    {{-- Designations table --}}
    <div class="hrms-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>All Designations</span>
            <span class="text-muted" style="font-size:.8rem;font-weight:400;">
                {{ $designations->total() }} total
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem;" id="designationTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th class="text-center">Employees</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Created By</th>
                        <th class="text-end" style="width:130px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="designationBody">
                    @forelse($designations as $d)
                    <tr id="row-{{ $d->id }}">
                        <td class="text-muted">{{ $loop->iteration }}</td>
                        <td class="fw-semibold">{{ $d->name }}</td>
                        <td class="text-muted">{{ $d->description ? Str::limit($d->description, 60) : '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary">{{ $d->employees_count }}</span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm px-2 py-0 toggle-status
                                {{ $d->status === 'active' ? 'btn-success' : 'btn-secondary' }}"
                                data-id="{{ $d->id }}" title="Toggle status"
                                style="font-size:.75rem;min-width:72px;">
                                {{ ucfirst($d->status) }}
                            </button>
                        </td>
                        <td class="text-center text-muted" style="font-size:.8rem;">
                            {{ $d->createdBy?->name ?? 'System' }}
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary btn-edit me-1"
                                    data-id="{{ $d->id }}" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete"
                                    data-id="{{ $d->id }}" data-name="{{ $d->name }}" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr id="emptyRow">
                        <td colspan="7" class="text-center py-4 text-muted">
                            No designations found. Click <strong>New Designation</strong> to add one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($designations->hasPages())
        <div class="p-3 d-flex justify-content-end">
            {{ $designations->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>

    {{-- ── Add / Edit Modal ──────────────────────────────────────────── --}}
    <div class="modal fade" id="designationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="modalTitle">New Designation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="modalErrors" class="alert alert-danger d-none" style="font-size:.875rem;"></div>
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" id="fieldName" class="form-control" placeholder="e.g. Senior Developer" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea id="fieldDescription" class="form-control" rows="3" maxlength="500"
                                  placeholder="Optional — brief description of the role"></textarea>
                    </div>
                    <div class="mb-1">
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
                        <span id="saveText">Create</span>
                        <span id="saveSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Delete Confirmation Modal ────────────────────────────────── --}}
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-semibold text-danger">
                        <i class="bi bi-exclamation-triangle me-1"></i>Confirm Delete
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="mb-0" style="font-size:.875rem;">
                        Delete <strong id="deleteName"></strong>? This cannot be undone.
                    </p>
                    <div id="deleteError" class="alert alert-warning mt-2 d-none p-2" style="font-size:.8rem;"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-danger" id="btnConfirmDelete">
                        Delete
                        <span id="deleteSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
    const modal    = new bootstrap.Modal(document.getElementById('designationModal'));
    const delModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    let editingId  = null;
    let deletingId = null;

    // ── Helpers ───────────────────────────────────────────────────────
    function api(url, method, body = null) {
        return fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: body ? JSON.stringify(body) : null,
        }).then(r => r.json().catch(() => ({ success: false, message: 'Server error — please try again.' })));
    }

    function showToast(msg, success = true) {
        const el = document.createElement('div');
        el.className = `toast align-items-center text-bg-${success ? 'success' : 'danger'} border-0 show`;
        el.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;min-width:260px;';
        el.innerHTML = `<div class="d-flex"><div class="toast-body">${msg}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button></div>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    }

    function clearModalErrors() {
        const box = document.getElementById('modalErrors');
        box.classList.add('d-none');
        box.innerHTML = '';
    }

    function showModalErrors(errors) {
        const box  = document.getElementById('modalErrors');
        const msgs = Object.values(errors).flat().map(e => `<div>${e}</div>`).join('');
        box.innerHTML = msgs;
        box.classList.remove('d-none');
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function buildRow(d, index) {
        const statusClass = d.status === 'active' ? 'btn-success' : 'btn-secondary';
        const desc = d.description
            ? escHtml(d.description.substring(0, 60)) + (d.description.length > 60 ? '…' : '')
            : '—';
        return `<tr id="row-${d.id}">
            <td class="text-muted">${index}</td>
            <td class="fw-semibold">${escHtml(d.name)}</td>
            <td class="text-muted">${desc}</td>
            <td class="text-center"><span class="badge bg-primary-subtle text-primary">${d.employees_count}</span></td>
            <td class="text-center">
                <button class="btn btn-sm px-2 py-0 toggle-status ${statusClass}" data-id="${d.id}"
                        style="font-size:.75rem;min-width:72px;">${escHtml(d.status.charAt(0).toUpperCase() + d.status.slice(1))}</button>
            </td>
            <td class="text-center text-muted" style="font-size:.8rem;">${escHtml(d.created_by_name)}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary btn-edit me-1" data-id="${d.id}" title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${d.id}" data-name="${escHtml(d.name)}" title="Delete"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`;
    }

    // ── Open "Add" modal ───────────────────────────────────────────────
    document.getElementById('btnAdd').addEventListener('click', () => {
        editingId = null;
        document.getElementById('modalTitle').textContent    = 'New Designation';
        document.getElementById('saveText').textContent      = 'Create';
        document.getElementById('fieldName').value           = '';
        document.getElementById('fieldDescription').value   = '';
        document.getElementById('fieldStatus').value        = 'active';
        clearModalErrors();
        modal.show();
    });

    // ── Save (create or update) ────────────────────────────────────────
    document.getElementById('btnSave').addEventListener('click', async () => {
        const name   = document.getElementById('fieldName').value.trim();
        const desc   = document.getElementById('fieldDescription').value.trim();
        const status = document.getElementById('fieldStatus').value;

        if (!name) {
            showModalErrors({ name: ['Name is required.'] });
            return;
        }

        const spinner = document.getElementById('saveSpinner');
        const btn     = document.getElementById('btnSave');
        btn.disabled  = true;
        spinner.classList.remove('d-none');
        clearModalErrors();

        const url    = editingId ? `/designations/${editingId}` : '/designations';
        const method = editingId ? 'PATCH' : 'POST';

        try {
            const res = await api(url, method, { name, description: desc, status });

            btn.disabled = false;
            spinner.classList.add('d-none');

            if (res.success) {
                modal.hide();
                showToast(res.message);

                const body     = document.getElementById('designationBody');
                const emptyRow = document.getElementById('emptyRow');
                if (emptyRow) emptyRow.remove();

                if (editingId) {
                    const existing = document.getElementById(`row-${editingId}`);
                    if (existing) {
                        const idx = [...body.rows].indexOf(existing) + 1;
                        existing.outerHTML = buildRow(res.designation, idx);
                    }
                } else {
                    body.insertAdjacentHTML('beforeend', buildRow(res.designation, body.rows.length + 1));
                }
            } else {
                showModalErrors(res.errors || { error: [res.message || 'Something went wrong.'] });
            }
        } catch (err) {
            btn.disabled = false;
            spinner.classList.add('d-none');
            showModalErrors({ error: ['Unexpected error. Please try again.'] });
        }
    });

    // ── Edit ───────────────────────────────────────────────────────────
    document.getElementById('designationBody').addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.btn-edit');
        if (!editBtn) return;

        try {
            const id  = editBtn.dataset.id;
            const res = await api(`/designations/${id}`, 'GET');

            editingId = id;
            document.getElementById('modalTitle').textContent    = 'Edit Designation';
            document.getElementById('saveText').textContent      = 'Save Changes';
            document.getElementById('fieldName').value           = res.designation.name;
            document.getElementById('fieldDescription').value   = res.designation.description || '';
            document.getElementById('fieldStatus').value        = res.designation.status;
            clearModalErrors();
            modal.show();
        } catch (err) {
            showToast('Could not load designation. Please try again.', false);
        }
    });

    // ── Toggle status ──────────────────────────────────────────────────
    document.getElementById('designationBody').addEventListener('click', async (e) => {
        const toggleBtn = e.target.closest('.toggle-status');
        if (!toggleBtn) return;

        toggleBtn.disabled = true;
        try {
            const res = await api(`/designations/${toggleBtn.dataset.id}/toggle`, 'PATCH');
            if (res.success) {
                const isActive = res.status === 'active';
                toggleBtn.textContent = isActive ? 'Active' : 'Inactive';
                toggleBtn.className   = `btn btn-sm px-2 py-0 toggle-status ${isActive ? 'btn-success' : 'btn-secondary'}`;
                toggleBtn.style.cssText = 'font-size:.75rem;min-width:72px;';
                showToast(res.message);
            }
        } catch (err) {
            showToast('Could not toggle status. Please try again.', false);
        } finally {
            toggleBtn.disabled = false;
        }
    });

    // ── Delete ─────────────────────────────────────────────────────────
    document.getElementById('designationBody').addEventListener('click', (e) => {
        const delBtn = e.target.closest('.btn-delete');
        if (!delBtn) return;

        deletingId = delBtn.dataset.id;
        document.getElementById('deleteName').textContent = delBtn.dataset.name;
        document.getElementById('deleteError').classList.add('d-none');
        delModal.show();
    });

    document.getElementById('btnConfirmDelete').addEventListener('click', async () => {
        const spinner = document.getElementById('deleteSpinner');
        const btn     = document.getElementById('btnConfirmDelete');
        btn.disabled  = true;
        spinner.classList.remove('d-none');

        try {
            const res = await api(`/designations/${deletingId}`, 'DELETE');
            btn.disabled = false;
            spinner.classList.add('d-none');

            if (res.success) {
                delModal.hide();
                document.getElementById(`row-${deletingId}`)?.remove();
                showToast(res.message);

                // Show empty state if table is now empty
                const body = document.getElementById('designationBody');
                if (body.rows.length === 0) {
                    body.insertAdjacentHTML('beforeend', `<tr id="emptyRow">
                        <td colspan="7" class="text-center py-4 text-muted">
                            No designations found. Click <strong>New Designation</strong> to add one.
                        </td>
                    </tr>`);
                }
            } else {
                document.getElementById('deleteError').textContent = res.message;
                document.getElementById('deleteError').classList.remove('d-none');
            }
        } catch (err) {
            btn.disabled = false;
            spinner.classList.add('d-none');
            document.getElementById('deleteError').textContent = 'Unexpected error. Please try again.';
            document.getElementById('deleteError').classList.remove('d-none');
        }
    });

    @if($isAdmin)
    // ── Settings panel toggle ──────────────────────────────────────────
    document.getElementById('btnSettings').addEventListener('click', () => {
        const panel = document.getElementById('settingsPanel');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    });

    // ── HR Access toggle ───────────────────────────────────────────────
    document.getElementById('toggleHrAccess').addEventListener('change', async function () {
        try {
            const res = await api('/designations/settings/update', 'POST', { designation_hr_access: this.checked });

            if (res.success) {
                const badge = document.getElementById('hrAccessStatus');
                if (res.value) {
                    badge.textContent = 'Enabled';
                    badge.className   = 'badge bg-success-subtle text-success';
                } else {
                    badge.textContent = 'Disabled';
                    badge.className   = 'badge bg-secondary-subtle text-secondary';
                }
                showToast(res.message);
            } else {
                this.checked = !this.checked; // revert on failure
            }
        } catch (err) {
            this.checked = !this.checked;
            showToast('Could not save setting. Please try again.', false);
        }
    });
    @endif
    </script>
    @endpush

</x-app-layout>
