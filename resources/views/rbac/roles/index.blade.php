<x-app-layout>
    <x-slot name="title">Roles & Permissions</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Roles & Permissions</h5>
            <small class="text-muted">Manage roles and what they can access</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('rbac.users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-people-fill me-1"></i> User Access
            </a>
            <button class="btn btn-primary" id="btnCreateRole">
                <i class="bi bi-plus-lg me-1"></i> New Role
            </button>
        </div>
    </div>

    {{-- Roles table --}}
    <div class="hrms-card mb-4">
        <div class="card-header">All Roles</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem;">
                <thead class="table-light">
                    <tr>
                        <th>Role</th>
                        <th>Slug</th>
                        <th class="text-center">Permissions</th>
                        <th class="text-center">Users</th>
                        <th class="text-center">Type</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="rolesTbody">
                    @foreach($roles as $role)
                    <tr id="role-row-{{ $role->id }}">
                        <td>
                            <div class="fw-semibold">{{ $role->display_name }}</div>
                            @if($role->description)
                            <div class="text-muted" style="font-size:.78rem;">{{ $role->description }}</div>
                            @endif
                        </td>
                        <td><code class="text-muted">{{ $role->name }}</code></td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary">{{ $role->permissions_count }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-secondary">{{ $role->users_count }}</span>
                        </td>
                        <td class="text-center">
                            @if($role->is_system)
                                <span class="badge bg-warning-subtle text-warning">System</span>
                            @else
                                <span class="badge bg-success-subtle text-success">Custom</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('rbac.roles.show', $role) }}" class="btn btn-sm btn-outline-primary" title="Manage Permissions">
                                <i class="bi bi-shield-check"></i>
                            </a>
                            @unless($role->is_system)
                            <button class="btn btn-sm btn-outline-secondary btn-edit-role"
                                    data-id="{{ $role->id }}"
                                    data-display="{{ $role->display_name }}"
                                    data-description="{{ $role->description }}"
                                    title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete-role"
                                    data-id="{{ $role->id }}"
                                    data-name="{{ $role->display_name }}"
                                    title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endunless
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Permissions overview by module --}}
    <div class="hrms-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>All Permissions</span>
            <button class="btn btn-sm btn-outline-primary" id="btnCreatePerm">
                <i class="bi bi-plus-lg me-1"></i> Custom Permission
            </button>
        </div>
        <div class="p-3">
            @foreach($permissions as $module => $perms)
            <div class="mb-3">
                <div class="fw-semibold text-uppercase mb-2" style="font-size:.72rem;letter-spacing:.06em;color:#6c757d;">
                    {{ $module }}
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($perms as $perm)
                    <span class="badge d-flex align-items-center gap-1 {{ $perm->is_system ? 'bg-light text-dark border' : 'bg-primary-subtle text-primary' }}"
                          style="font-size:.8rem;padding:6px 10px;">
                        <code style="font-size:.78rem;">{{ $perm->name }}</code>
                        — {{ $perm->label }}
                        @unless($perm->is_system)
                        <button class="btn-close btn-close-sm btn-delete-perm ms-1"
                                style="font-size:.55rem;"
                                data-id="{{ $perm->id }}"
                                data-name="{{ $perm->name }}"
                                title="Delete permission"></button>
                        @endunless
                    </span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Create / Edit Role Modal --}}
    <div class="modal fade" id="roleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalTitle">New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="roleFormErr" class="alert alert-danger d-none"></div>
                    <input type="hidden" id="roleEditId">
                    <div class="mb-3">
                        <label class="form-label">Slug <span class="text-danger">*</span>
                            <small class="text-muted">(lowercase letters, numbers, - and _ only)</small>
                        </label>
                        <input type="text" id="fldRoleName" class="form-control font-monospace" placeholder="e.g. finance_manager">
                        <div class="form-text d-none" id="roleNameHelp">Cannot be changed after creation.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display Name <span class="text-danger">*</span></label>
                        <input type="text" id="fldRoleDisplay" class="form-control" placeholder="e.g. Finance Manager">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-muted small">(optional)</span></label>
                        <input type="text" id="fldRoleDesc" class="form-control" placeholder="Brief description of this role">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSaveRole">
                        <span id="btnSaveRoleTxt">Create Role</span>
                        <span id="btnSaveRoleSpin" class="spinner-border spinner-border-sm d-none ms-1"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Permission Modal --}}
    <div class="modal fade" id="permModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Custom Permission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="permFormErr" class="alert alert-danger d-none"></div>
                    <div class="mb-3">
                        <label class="form-label">Permission Name <span class="text-danger">*</span>
                            <small class="text-muted">(module.action format)</small>
                        </label>
                        <input type="text" id="fldPermName" class="form-control font-monospace" placeholder="e.g. invoices.generate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label <span class="text-danger">*</span></label>
                        <input type="text" id="fldPermLabel" class="form-control" placeholder="e.g. Generate Invoices">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Module <span class="text-danger">*</span></label>
                        <input type="text" id="fldPermModule" class="form-control" placeholder="e.g. invoices">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-muted small">(optional)</span></label>
                        <input type="text" id="fldPermDesc" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSavePerm">
                        <span id="btnSavePermTxt">Create Permission</span>
                        <span id="btnSavePermSpin" class="spinner-border spinner-border-sm d-none ms-1"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Role Confirm --}}
    <div class="modal fade" id="deleteRoleModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Delete Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Delete role <strong id="deleteRoleName"></strong>?
                    <small class="text-muted d-block">Users will lose this role.</small>
                    <div id="deleteRoleErr" class="alert alert-danger mt-2 d-none"></div>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger btn-sm" id="btnConfirmDeleteRole">Delete</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100">
        <div id="rbacToast" class="toast align-items-center text-white border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="rbacToastMsg"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    const roleModal       = new bootstrap.Modal(document.getElementById('roleModal'));
    const permModal       = new bootstrap.Modal(document.getElementById('permModal'));
    const deleteRoleModal = new bootstrap.Modal(document.getElementById('deleteRoleModal'));
    const toastInst       = new bootstrap.Toast(document.getElementById('rbacToast'), { delay: 3500 });

    let deleteRoleId = null;

    function showToast(msg, ok = true) {
        const el = document.getElementById('rbacToast');
        el.className = `toast align-items-center text-white border-0 ${ok ? 'bg-success' : 'bg-danger'}`;
        document.getElementById('rbacToastMsg').textContent = msg;
        toastInst.show();
    }

    function post(url, data, method = 'POST') {
        return fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(data),
        }).then(r => r.json());
    }

    // ── Create / Edit Role ────────────────────────────────────────
    document.getElementById('btnCreateRole').addEventListener('click', () => {
        document.getElementById('roleEditId').value      = '';
        document.getElementById('fldRoleName').value     = '';
        document.getElementById('fldRoleDisplay').value  = '';
        document.getElementById('fldRoleDesc').value     = '';
        document.getElementById('fldRoleName').disabled  = false;
        document.getElementById('roleNameHelp').classList.add('d-none');
        document.getElementById('roleModalTitle').textContent = 'New Role';
        document.getElementById('btnSaveRoleTxt').textContent = 'Create Role';
        document.getElementById('roleFormErr').classList.add('d-none');
        roleModal.show();
    });

    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-edit-role');
        if (!btn) return;
        document.getElementById('roleEditId').value      = btn.dataset.id;
        document.getElementById('fldRoleName').value     = btn.dataset.display; // shown but disabled
        document.getElementById('fldRoleDisplay').value  = btn.dataset.display;
        document.getElementById('fldRoleDesc').value     = btn.dataset.description ?? '';
        document.getElementById('fldRoleName').disabled  = true;
        document.getElementById('roleNameHelp').classList.remove('d-none');
        document.getElementById('roleModalTitle').textContent = 'Edit Role';
        document.getElementById('btnSaveRoleTxt').textContent = 'Save Changes';
        document.getElementById('roleFormErr').classList.add('d-none');
        roleModal.show();
    });

    document.getElementById('btnSaveRole').addEventListener('click', () => {
        const id      = document.getElementById('roleEditId').value;
        const name    = document.getElementById('fldRoleName').value.trim();
        const display = document.getElementById('fldRoleDisplay').value.trim();
        const desc    = document.getElementById('fldRoleDesc').value.trim();
        const errEl   = document.getElementById('roleFormErr');
        errEl.classList.add('d-none');

        document.getElementById('btnSaveRoleSpin').classList.remove('d-none');
        document.getElementById('btnSaveRoleTxt').textContent = 'Saving…';

        const url    = id ? `/rbac/roles/${id}` : '/rbac/roles';
        const method = id ? 'PATCH' : 'POST';
        const body   = id ? { display_name: display, description: desc } : { name, display_name: display, description: desc };

        post(url, body, method)
            .then(data => {
                if (!data.success) throw new Error(data.message ?? 'Save failed.');
                roleModal.hide();
                showToast(data.message);
                setTimeout(() => location.reload(), 800);
            })
            .catch(err => { errEl.textContent = err.message; errEl.classList.remove('d-none'); })
            .finally(() => {
                document.getElementById('btnSaveRoleSpin').classList.add('d-none');
                document.getElementById('btnSaveRoleTxt').textContent = id ? 'Save Changes' : 'Create Role';
            });
    });

    // ── Delete Role ───────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-delete-role');
        if (!btn) return;
        deleteRoleId = btn.dataset.id;
        document.getElementById('deleteRoleName').textContent = btn.dataset.name;
        document.getElementById('deleteRoleErr').classList.add('d-none');
        deleteRoleModal.show();
    });

    document.getElementById('btnConfirmDeleteRole').addEventListener('click', () => {
        post(`/rbac/roles/${deleteRoleId}`, {}, 'DELETE')
            .then(data => {
                if (!data.success) throw new Error(data.message);
                deleteRoleModal.hide();
                showToast(data.message);
                document.getElementById(`role-row-${deleteRoleId}`)?.remove();
            })
            .catch(err => {
                document.getElementById('deleteRoleErr').textContent = err.message;
                document.getElementById('deleteRoleErr').classList.remove('d-none');
            });
    });

    // ── Create Custom Permission ──────────────────────────────────
    document.getElementById('btnCreatePerm').addEventListener('click', () => {
        ['fldPermName','fldPermLabel','fldPermModule','fldPermDesc'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('permFormErr').classList.add('d-none');
        permModal.show();
    });

    document.getElementById('fldPermName').addEventListener('input', function() {
        const parts = this.value.split('.');
        if (parts.length >= 1) document.getElementById('fldPermModule').value = parts[0];
    });

    document.getElementById('btnSavePerm').addEventListener('click', () => {
        const body = {
            name:        document.getElementById('fldPermName').value.trim(),
            label:       document.getElementById('fldPermLabel').value.trim(),
            module:      document.getElementById('fldPermModule').value.trim(),
            description: document.getElementById('fldPermDesc').value.trim() || null,
        };
        const errEl = document.getElementById('permFormErr');
        errEl.classList.add('d-none');
        document.getElementById('btnSavePermSpin').classList.remove('d-none');
        document.getElementById('btnSavePermTxt').textContent = 'Creating…';

        post('/rbac/permissions', body)
            .then(data => {
                if (!data.success) throw new Error(data.message ?? 'Failed.');
                permModal.hide();
                showToast(data.message);
                setTimeout(() => location.reload(), 800);
            })
            .catch(err => { errEl.textContent = err.message; errEl.classList.remove('d-none'); })
            .finally(() => {
                document.getElementById('btnSavePermSpin').classList.add('d-none');
                document.getElementById('btnSavePermTxt').textContent = 'Create Permission';
            });
    });

    // ── Delete Custom Permission ──────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-delete-perm');
        if (!btn) return;
        if (!confirm(`Delete permission "${btn.dataset.name}"?`)) return;
        post(`/rbac/permissions/${btn.dataset.id}`, {}, 'DELETE')
            .then(data => {
                if (!data.success) throw new Error(data.message);
                showToast(data.message);
                setTimeout(() => location.reload(), 800);
            })
            .catch(err => showToast(err.message, false));
    });
    </script>
    @endpush
</x-app-layout>
