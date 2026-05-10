<x-app-layout>
    <x-slot name="title">Menu Manager</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Menu Manager</h5>
            <small class="text-muted">Reorder, enable/disable, and assign visibility rules to sidebar items</small>
        </div>
        <button class="btn btn-primary" id="btnAddMenu">
            <i class="bi bi-plus-lg me-1"></i> Add Menu Item
        </button>
    </div>

    {{-- ── Tab navigation ───────────────────────────────────────── --}}
    <ul class="nav nav-pills mb-4 gap-1" id="menuTabs">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#paneMenus">
                <i class="bi bi-list-nested me-1"></i> Menu Items
                <span class="badge bg-white text-primary ms-1">{{ $menus->count() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#paneOverrides">
                <i class="bi bi-person-gear me-1"></i> User Overrides
            </button>
        </li>
    </ul>

    <div class="tab-content">

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- ── Menu Items Tab ────────────────────────────────────── --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade show active" id="paneMenus">

            <div class="hrms-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>All Menu Items</span>
                    <small class="text-muted">Drag rows to reorder — changes save automatically</small>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:.875rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width:32px;"></th>{{-- drag --}}
                                <th>Label</th>
                                <th>Type</th>
                                <th>Route</th>
                                <th>Permissions</th>
                                <th class="text-center">Active</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="menuSortable">
                            @foreach($menus as $menu)
                            <tr id="menu-row-{{ $menu->id }}" data-id="{{ $menu->id }}"
                                class="{{ $menu->type === 'header' ? 'table-light' : ($menu->type === 'divider' ? 'table-secondary' : '') }}">
                                <td class="drag-handle text-center" style="cursor:grab;color:#ccc;">
                                    <i class="bi bi-grip-vertical"></i>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($menu->icon)
                                            <i class="bi {{ $menu->icon }} text-muted" style="min-width:18px;"></i>
                                        @else
                                            <span style="min-width:18px;"></span>
                                        @endif
                                        <span class="fw-semibold {{ !$menu->is_active ? 'text-muted text-decoration-line-through' : '' }}">
                                            {{ $menu->label ?: '— divider —' }}
                                        </span>
                                        @if($menu->is_system)
                                            <span class="badge bg-warning-subtle text-warning" style="font-size:.65rem;">sys</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($menu->type === 'header')
                                        <span class="badge bg-secondary-subtle text-secondary">Header</span>
                                    @elseif($menu->type === 'divider')
                                        <span class="badge bg-light text-muted border">Divider</span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary">Link</span>
                                    @endif
                                </td>
                                <td>
                                    @if($menu->route)
                                        <code style="font-size:.75rem;color:#6c757d;">{{ $menu->route }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="max-width:200px;">
                                    @if($menu->permissions)
                                        <div class="d-flex flex-wrap gap-1">
                                        @foreach($menu->permissions as $perm)
                                            <span class="badge bg-info-subtle text-info" style="font-size:.65rem;">{{ $perm }}</span>
                                        @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted small">public</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-flex mb-0">
                                        <input class="form-check-input menu-toggle"
                                               type="checkbox"
                                               data-id="{{ $menu->id }}"
                                               style="cursor:pointer;width:2rem;height:1.1rem;"
                                               {{ $menu->is_active ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary btn-edit-menu"
                                            data-menu="{{ json_encode($menu) }}"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @unless($menu->is_system)
                                    <button class="btn btn-sm btn-outline-danger btn-delete-menu"
                                            data-id="{{ $menu->id }}"
                                            data-label="{{ $menu->label }}"
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
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- ── User Overrides Tab ─────────────────────────────────── --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="paneOverrides">
            <div class="row g-4">

                {{-- User search panel --}}
                <div class="col-lg-4">
                    <div class="hrms-card p-3">
                        <div class="fw-semibold mb-3" style="font-size:.9rem;">
                            <i class="bi bi-people me-2 text-primary"></i>Select User
                        </div>
                        <input type="text" id="userSearch" class="form-control form-control-sm mb-3" placeholder="Search by name or email…">
                        <div id="userList" style="max-height:420px;overflow-y:auto;">
                            @foreach($users as $u)
                            <div class="user-item d-flex align-items-center gap-2 p-2 rounded mb-1"
                                 style="cursor:pointer;transition:background .12s;"
                                 data-id="{{ $u->id }}"
                                 data-name="{{ $u->name }}"
                                 onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background=''">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white flex-shrink-0"
                                     style="width:30px;height:30px;font-size:.7rem;font-weight:700;">
                                    {{ strtoupper(substr($u->name,0,2)) }}
                                </div>
                                <div>
                                    <div style="font-size:.83rem;font-weight:600;">{{ $u->name }}</div>
                                    <div class="text-muted" style="font-size:.72rem;">{{ $u->email }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Override matrix --}}
                <div class="col-lg-8">
                    <div class="hrms-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span id="overrideTitle">
                                <i class="bi bi-person-gear me-2 text-muted"></i>Select a user to manage their menu
                            </span>
                            <button class="btn btn-sm btn-primary d-none" id="btnSaveOverrides">
                                <i class="bi bi-check2 me-1"></i> Save Overrides
                            </button>
                        </div>
                        <div id="overrideBody" class="p-4 text-muted text-center" style="min-height:200px;">
                            <i class="bi bi-person-gear fs-1 d-block mb-2 opacity-25"></i>
                            Click a user on the left to view and manage their menu visibility overrides.
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <span class="badge bg-success me-1">Force Show</span> Always visible regardless of permissions.
                            <span class="badge bg-danger ms-2 me-1">Force Hide</span> Always hidden regardless of permissions.
                            <span class="badge bg-light text-dark border ms-2 me-1">Default</span> Follows role/permission rules.
                        </small>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- end tab-content --}}

    {{-- ── Add / Edit Menu Modal ────────────────────────────────── --}}
    <div class="modal fade" id="menuModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="menuModalTitle">Add Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="menuFormErr" class="alert alert-danger d-none mb-3"></div>
                    <input type="hidden" id="menuEditId">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Label <span class="text-danger">*</span></label>
                            <input type="text" id="fldLabel" class="form-control" placeholder="Employees" maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select id="fldType" class="form-select">
                                <option value="link">Link</option>
                                <option value="header">Section Header</option>
                                <option value="divider">Divider</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Active</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="fldActive" checked style="width:2.2rem;height:1.2rem;">
                                <label class="form-check-label" for="fldActive">Enabled</label>
                            </div>
                        </div>

                        <div id="linkFields">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Icon <span class="text-muted small">(Bootstrap Icon class)</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="iconPreview"><i class="bi bi-question-lg"></i></span>
                                        <input type="text" id="fldIcon" class="form-control font-monospace" placeholder="bi-people">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Route Name</label>
                                    <input type="text" id="fldRoute" class="form-control font-monospace" placeholder="employees.index" maxlength="120">
                                    <div class="form-text">Laravel named route (leave blank for headers/external links)</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Active-State Pattern</label>
                                    <input type="text" id="fldRoutePattern" class="form-control font-monospace" placeholder="employees.*" maxlength="120">
                                    <div class="form-text">Used with <code>routeIs()</code> to highlight the active link</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Required Permissions
                                <span class="text-muted small">(canAny — user needs at least one)</span>
                            </label>
                            <div class="d-flex flex-wrap gap-1 p-2 border rounded" id="permChips" style="min-height:42px;cursor:pointer;">
                                <small class="text-muted align-self-center" id="permChipsHint">Click to add…</small>
                            </div>
                            <div class="mt-2">
                                <select id="permAdd" class="form-select form-select-sm" style="max-width:300px;">
                                    <option value="">— Add permission —</option>
                                    @foreach($permissions as $module => $perms)
                                    <optgroup label="{{ strtoupper($module) }}">
                                        @foreach($perms as $perm)
                                        <option value="{{ $perm->name }}">{{ $perm->label }} ({{ $perm->name }})</option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-text">Leave empty = visible to all authenticated users</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSaveMenu">
                        <span id="btnSaveMenuTxt">Save</span>
                        <span id="btnSaveMenuSpin" class="spinner-border spinner-border-sm d-none ms-1"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete confirm modal --}}
    <div class="modal fade" id="deleteMenuModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="bi bi-exclamation-triangle text-danger fs-1 d-block mb-2"></i>
                    <p>Delete <strong id="deleteMenuLabel"></strong>?</p>
                    <div id="deleteMenuErr" class="alert alert-danger d-none small"></div>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger btn-sm" id="btnConfirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1200">
        <div id="menuToast" class="toast align-items-center text-white border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="menuToastMsg"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
    .drag-handle { cursor: grab; }
    .drag-handle:active { cursor: grabbing; }
    .sortable-ghost { opacity: 0.4; background: #e8f0fe !important; }
    .sortable-chosen { background: #f0f4ff; }
    .perm-chip {
        display: inline-flex; align-items: center; gap: 4px;
        background: #e8f0fe; color: #1a73e8; border-radius: 4px;
        padding: 2px 8px; font-size: .75rem; cursor: default;
    }
    .perm-chip .rm { cursor: pointer; opacity: .6; }
    .perm-chip .rm:hover { opacity: 1; }
    .override-row { transition: background .1s; }
    .override-row:hover { background: #f8f9fa; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
    const toastEl  = document.getElementById('menuToast');
    const toastInst = new bootstrap.Toast(toastEl, { delay: 3000 });
    const menuModal  = new bootstrap.Modal(document.getElementById('menuModal'));
    const deleteModal= new bootstrap.Modal(document.getElementById('deleteMenuModal'));

    function showToast(msg, ok = true) {
        toastEl.className = `toast align-items-center text-white border-0 ${ok ? 'bg-success' : 'bg-danger'}`;
        document.getElementById('menuToastMsg').textContent = msg;
        toastInst.show();
    }

    function api(url, method, data) {
        const opts = {
            method,
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        };
        if (data) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(data);
        }
        return fetch(url, opts).then(r => r.json());
    }

    // ── Drag-drop reorder ──────────────────────────────────────────
    const sortable = Sortable.create(document.getElementById('menuSortable'), {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd() {
            const ids = [...document.querySelectorAll('#menuSortable tr[data-id]')]
                .map(r => parseInt(r.dataset.id));
            api('/menus/reorder', 'POST', { ids })
                .then(d => showToast(d.message, !!d.success))
                .catch(() => showToast('Reorder failed.', false));
        }
    });

    // ── Toggle active ─────────────────────────────────────────────
    document.addEventListener('change', e => {
        const cb = e.target.closest('.menu-toggle');
        if (!cb) return;
        api(`/menus/${cb.dataset.id}/toggle`, 'PATCH')
            .then(d => {
                showToast(d.message, !!d.success);
                const row = document.getElementById(`menu-row-${cb.dataset.id}`);
                const lbl = row?.querySelector('span.fw-semibold');
                if (lbl) {
                    lbl.classList.toggle('text-muted', !d.is_active);
                    lbl.classList.toggle('text-decoration-line-through', !d.is_active);
                }
            })
            .catch(() => showToast('Toggle failed.', false));
    });

    // ── Permission chips ───────────────────────────────────────────
    let selectedPerms = [];

    function renderChips() {
        const container = document.getElementById('permChips');
        const hint      = document.getElementById('permChipsHint');
        const existing  = container.querySelectorAll('.perm-chip');
        existing.forEach(e => e.remove());
        hint.classList.toggle('d-none', selectedPerms.length > 0);

        selectedPerms.forEach(p => {
            const chip = document.createElement('span');
            chip.className = 'perm-chip';
            chip.innerHTML = `<code style="font-size:.72rem;">${escHtml(p)}</code><span class="rm" data-perm="${escHtml(p)}">×</span>`;
            container.appendChild(chip);
        });
    }

    document.getElementById('permChips').addEventListener('click', e => {
        const rm = e.target.closest('.rm');
        if (!rm) return;
        selectedPerms = selectedPerms.filter(p => p !== rm.dataset.perm);
        renderChips();
    });

    document.getElementById('permAdd').addEventListener('change', function() {
        if (!this.value) return;
        if (!selectedPerms.includes(this.value)) {
            selectedPerms.push(this.value);
            renderChips();
        }
        this.value = '';
    });

    // ── Add menu ───────────────────────────────────────────────────
    document.getElementById('btnAddMenu').addEventListener('click', () => {
        document.getElementById('menuEditId').value = '';
        document.getElementById('menuModalTitle').textContent = 'Add Menu Item';
        document.getElementById('btnSaveMenuTxt').textContent = 'Create';
        document.getElementById('fldLabel').value = '';
        document.getElementById('fldType').value  = 'link';
        document.getElementById('fldIcon').value  = '';
        document.getElementById('fldRoute').value = '';
        document.getElementById('fldRoutePattern').value = '';
        document.getElementById('fldActive').checked = true;
        document.getElementById('menuFormErr').classList.add('d-none');
        selectedPerms = [];
        renderChips();
        toggleLinkFields('link');
        menuModal.show();
    });

    // ── Edit menu ──────────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-edit-menu');
        if (!btn) return;
        const m = JSON.parse(btn.dataset.menu);
        document.getElementById('menuEditId').value = m.id;
        document.getElementById('menuModalTitle').textContent = 'Edit Menu Item';
        document.getElementById('btnSaveMenuTxt').textContent = 'Save Changes';
        document.getElementById('fldLabel').value = m.label ?? '';
        document.getElementById('fldType').value  = m.type ?? 'link';
        document.getElementById('fldIcon').value  = m.icon ?? '';
        document.getElementById('fldRoute').value = m.route ?? '';
        document.getElementById('fldRoutePattern').value = m.route_pattern ?? '';
        document.getElementById('fldActive').checked = !!m.is_active;
        document.getElementById('menuFormErr').classList.add('d-none');
        selectedPerms = m.permissions ? (Array.isArray(m.permissions) ? m.permissions : [m.permissions]) : [];
        renderChips();
        toggleLinkFields(m.type);
        updateIconPreview(m.icon ?? '');
        menuModal.show();
    });

    document.getElementById('fldType').addEventListener('change', function() {
        toggleLinkFields(this.value);
    });

    function toggleLinkFields(type) {
        document.getElementById('linkFields').style.display = type === 'divider' ? 'none' : '';
    }

    // ── Icon live preview ──────────────────────────────────────────
    document.getElementById('fldIcon').addEventListener('input', function() {
        updateIconPreview(this.value);
    });
    function updateIconPreview(cls) {
        const preview = document.getElementById('iconPreview');
        preview.innerHTML = cls ? `<i class="bi ${escHtml(cls)}"></i>` : '<i class="bi bi-question-lg"></i>';
    }

    // ── Save menu ──────────────────────────────────────────────────
    document.getElementById('btnSaveMenu').addEventListener('click', () => {
        const id  = document.getElementById('menuEditId').value;
        const err = document.getElementById('menuFormErr');
        err.classList.add('d-none');

        const payload = {
            label:         document.getElementById('fldLabel').value.trim(),
            type:          document.getElementById('fldType').value,
            icon:          document.getElementById('fldIcon').value.trim() || null,
            route:         document.getElementById('fldRoute').value.trim() || null,
            route_pattern: document.getElementById('fldRoutePattern').value.trim() || null,
            permissions:   selectedPerms.length ? selectedPerms : null,
            is_active:     document.getElementById('fldActive').checked,
        };

        const spin = document.getElementById('btnSaveMenuSpin');
        spin.classList.remove('d-none');

        const url    = id ? `/menus/${id}` : '/menus';
        const method = id ? 'PATCH' : 'POST';

        api(url, method, payload)
            .then(d => {
                if (!d.success) throw new Error(d.message ?? 'Save failed.');
                menuModal.hide();
                showToast(d.message);
                setTimeout(() => location.reload(), 800);
            })
            .catch(e => { err.textContent = e.message; err.classList.remove('d-none'); })
            .finally(() => spin.classList.add('d-none'));
    });

    // ── Delete menu ────────────────────────────────────────────────
    let deleteId = null;
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-delete-menu');
        if (!btn) return;
        deleteId = btn.dataset.id;
        document.getElementById('deleteMenuLabel').textContent = btn.dataset.label;
        document.getElementById('deleteMenuErr').classList.add('d-none');
        deleteModal.show();
    });

    document.getElementById('btnConfirmDelete').addEventListener('click', () => {
        api(`/menus/${deleteId}`, 'DELETE')
            .then(d => {
                if (!d.success) throw new Error(d.message);
                deleteModal.hide();
                showToast(d.message);
                document.getElementById(`menu-row-${deleteId}`)?.remove();
            })
            .catch(e => {
                document.getElementById('deleteMenuErr').textContent = e.message;
                document.getElementById('deleteMenuErr').classList.remove('d-none');
            });
    });

    // ── User search filter ────────────────────────────────────────
    document.getElementById('userSearch').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.user-item').forEach(el => {
            el.style.display = el.dataset.name.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // ── User overrides ────────────────────────────────────────────
    let selectedUserId   = null;
    let pendingOverrides = {}; // menuId → true|false|null

    document.querySelectorAll('.user-item').forEach(el => {
        el.addEventListener('click', function() {
            document.querySelectorAll('.user-item').forEach(i => i.style.background = '');
            this.style.background = '#e8f0fe';
            selectedUserId = this.dataset.id;
            loadUserOverrides(selectedUserId, this.dataset.name);
        });
    });

    function loadUserOverrides(userId, userName) {
        const body  = document.getElementById('overrideBody');
        const title = document.getElementById('overrideTitle');
        const saveBtn = document.getElementById('btnSaveOverrides');

        title.innerHTML = `<i class="bi bi-person-gear me-2 text-primary"></i>${escHtml(userName)}`;
        body.innerHTML  = '<div class="text-center py-4"><span class="spinner-border text-primary"></span></div>';
        saveBtn.classList.remove('d-none');
        pendingOverrides = {};

        fetch(`/menus/user-overrides?user_id=${userId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error('Failed');
            renderOverrideMatrix(data.menus);
        })
        .catch(() => { body.innerHTML = '<p class="text-danger text-center">Failed to load overrides.</p>'; });
    }

    function renderOverrideMatrix(menus) {
        let html = '<table class="table table-sm mb-0">';
        html += '<thead class="table-light"><tr><th>Menu Item</th><th class="text-center">Visibility Override</th></tr></thead><tbody>';

        menus.forEach(m => {
            const ov = m.override; // true | false | null
            const icon = m.icon ? `<i class="bi ${escHtml(m.icon)} me-2 text-muted"></i>` : '';
            const isHeader = m.type === 'header';
            const isDivider = m.type === 'divider';

            html += `<tr class="override-row" data-menu-id="${m.id}">`;
            html += `<td style="padding-left:${isHeader ? '12' : '28'}px">`;
            if (isHeader) {
                html += `<span class="text-muted fw-semibold small text-uppercase" style="font-size:.7rem;">${escHtml(m.label)}</span>`;
            } else if (isDivider) {
                html += `<span class="text-muted small">— divider —</span>`;
            } else {
                html += `${icon}<span>${escHtml(m.label)}</span>`;
            }
            html += '</td><td class="text-center">';
            html += `
                <div class="btn-group btn-group-sm override-btns" data-menu-id="${m.id}" role="group">
                    <button class="btn ${ov === true  ? 'btn-success'         : 'btn-outline-success'}  override-opt" data-val="true"  title="Force Show">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                    <button class="btn ${ov === null  ? 'btn-secondary'       : 'btn-outline-secondary'} override-opt" data-val="null" title="Default (follow permissions)">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <button class="btn ${ov === false ? 'btn-danger'          : 'btn-outline-danger'}   override-opt" data-val="false" title="Force Hide">
                        <i class="bi bi-eye-slash-fill"></i>
                    </button>
                </div>`;
            html += '</td></tr>';
        });

        html += '</tbody></table>';
        document.getElementById('overrideBody').innerHTML = html;

        // Button group toggle
        document.querySelectorAll('.override-opt').forEach(btn => {
            btn.addEventListener('click', function() {
                const group  = this.closest('.override-btns');
                const menuId = group.dataset.menuId;
                const val    = this.dataset.val === 'null' ? null
                             : this.dataset.val === 'true' ? true : false;

                // Update pending
                pendingOverrides[menuId] = val;

                // Update button styles
                group.querySelectorAll('.override-opt').forEach(b => {
                    const bv = b.dataset.val;
                    b.className = b.className.replace(/btn-(outline-)?(success|danger|secondary)\s*/g, '');
                    if (bv === 'true')  b.classList.add(val === true  ? 'btn-success'   : 'btn-outline-success');
                    if (bv === 'null')  b.classList.add(val === null  ? 'btn-secondary' : 'btn-outline-secondary');
                    if (bv === 'false') b.classList.add(val === false ? 'btn-danger'    : 'btn-outline-danger');
                });
            });
        });
    }

    document.getElementById('btnSaveOverrides').addEventListener('click', () => {
        if (!selectedUserId) return;
        api('/menus/user-overrides', 'POST', {
            user_id:   parseInt(selectedUserId),
            overrides: pendingOverrides,
        })
        .then(d => showToast(d.message, !!d.success))
        .catch(() => showToast('Save failed.', false));
    });

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    </script>
    @endpush
</x-app-layout>
