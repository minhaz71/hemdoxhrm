<x-app-layout>
    <x-slot name="title">{{ $role->display_name }} — Permissions</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">{{ $role->display_name }}</h5>
            <small class="text-muted">
                <code>{{ $role->name }}</code>
                @if($role->description) · {{ $role->description }} @endif
            </small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" id="btnSavePerms">
                <i class="bi bi-check2 me-1"></i> Save Permissions
                <span id="savePermsSpinner" class="spinner-border spinner-border-sm d-none ms-1"></span>
            </button>
            <a href="{{ route('rbac.roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Permission matrix --}}
        <div class="col-lg-8">
            <div class="hrms-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-shield-check me-2 text-primary"></i>Permission Matrix</span>
                    <div class="d-flex gap-2" style="font-size:.8rem;">
                        <button class="btn btn-sm btn-outline-success" id="btnSelectAll">Select All</button>
                        <button class="btn btn-sm btn-outline-secondary" id="btnClearAll">Clear All</button>
                    </div>
                </div>
                <div class="p-3" id="permMatrix">
                    @foreach($permissions as $module => $perms)
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold text-uppercase" style="font-size:.72rem;letter-spacing:.06em;color:#6c757d;">
                                <i class="bi bi-grid me-1"></i>{{ $module }}
                            </span>
                            <label class="form-check-label d-flex align-items-center gap-1" style="font-size:.78rem;cursor:pointer;">
                                <input type="checkbox" class="form-check-input module-toggle" data-module="{{ $module }}">
                                All
                            </label>
                        </div>
                        <div class="row g-2">
                            @foreach($perms as $perm)
                            <div class="col-sm-6 col-md-4">
                                <label class="d-flex align-items-center gap-2 p-2 rounded border {{ in_array($perm->id, $assignedIds) ? 'border-primary bg-primary-subtle' : 'border-light' }} perm-card"
                                       style="cursor:pointer;transition:all .15s;" data-module="{{ $module }}">
                                    <input type="checkbox"
                                           class="form-check-input perm-checkbox"
                                           value="{{ $perm->id }}"
                                           {{ in_array($perm->id, $assignedIds) ? 'checked' : '' }}>
                                    <div>
                                        <div style="font-size:.8rem;font-weight:600;">{{ $perm->label }}</div>
                                        <code style="font-size:.7rem;color:#6c757d;">{{ $perm->name }}</code>
                                    </div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar: summary + users --}}
        <div class="col-lg-4">
            <div class="hrms-card p-3 mb-3">
                <div class="fw-semibold mb-2" style="font-size:.85rem;">Selected Permissions</div>
                <div class="display-6 fw-bold text-primary" id="selectedCount">{{ count($assignedIds) }}</div>
                <div class="text-muted" style="font-size:.78rem;">of {{ $permissions->flatten()->count() }} total</div>
            </div>

            @if($role->is_system)
            <div class="alert alert-warning" style="font-size:.82rem;">
                <i class="bi bi-lock me-1"></i> This is a system role. Permissions can still be customised.
            </div>
            @endif

            <div class="hrms-card p-3">
                <div class="fw-semibold mb-2" style="font-size:.85rem;">
                    Users with this Role
                    <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $role->users()->count() }}</span>
                </div>
                @forelse($role->users()->with('employee')->limit(10)->get() as $u)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white"
                         style="width:28px;height:28px;font-size:.7rem;font-weight:700;flex-shrink:0;">
                        {{ strtoupper(substr($u->name,0,2)) }}
                    </div>
                    <div>
                        <div style="font-size:.82rem;font-weight:500;">{{ $u->name }}</div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $u->email }}</div>
                    </div>
                    <a href="{{ route('rbac.users.permissions', $u) }}" class="btn btn-sm btn-outline-secondary ms-auto" title="Manage Overrides">
                        <i class="bi bi-person-gear"></i>
                    </a>
                </div>
                @empty
                <p class="text-muted small mb-0">No users assigned to this role.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Save feedback toast --}}
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100">
        <div id="saveToast" class="toast align-items-center text-white border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="saveToastMsg"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
    const ROLE_ID  = {{ $role->id }};
    const toastInst = new bootstrap.Toast(document.getElementById('saveToast'), { delay: 3000 });

    function showToast(msg, ok = true) {
        const el = document.getElementById('saveToast');
        el.className = `toast align-items-center text-white border-0 ${ok ? 'bg-success' : 'bg-danger'}`;
        document.getElementById('saveToastMsg').textContent = msg;
        toastInst.show();
    }

    // Update selected count + card highlight
    function updateCount() {
        const checked = document.querySelectorAll('.perm-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = checked;
    }

    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const card = this.closest('.perm-card');
            if (this.checked) {
                card.classList.add('border-primary', 'bg-primary-subtle');
                card.classList.remove('border-light');
            } else {
                card.classList.remove('border-primary', 'bg-primary-subtle');
                card.classList.add('border-light');
            }
            updateCount();
        });
    });

    // Module toggle (all in group)
    document.querySelectorAll('.module-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const module = this.dataset.module;
            document.querySelectorAll(`.perm-card[data-module="${module}"] .perm-checkbox`).forEach(cb => {
                cb.checked = this.checked;
                cb.dispatchEvent(new Event('change'));
            });
        });
    });

    document.getElementById('btnSelectAll').addEventListener('click', () => {
        document.querySelectorAll('.perm-checkbox').forEach(cb => {
            if (!cb.checked) { cb.checked = true; cb.dispatchEvent(new Event('change')); }
        });
    });

    document.getElementById('btnClearAll').addEventListener('click', () => {
        document.querySelectorAll('.perm-checkbox').forEach(cb => {
            if (cb.checked) { cb.checked = false; cb.dispatchEvent(new Event('change')); }
        });
    });

    // Save
    document.getElementById('btnSavePerms').addEventListener('click', () => {
        const permissionIds = [...document.querySelectorAll('.perm-checkbox:checked')].map(cb => parseInt(cb.value));
        document.getElementById('savePermsSpinner').classList.remove('d-none');

        fetch(`/rbac/roles/${ROLE_ID}/permissions`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ permission_ids: permissionIds }),
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.success ? data.message : (data.message ?? 'Failed.'), data.success);
        })
        .catch(() => showToast('Save failed. Please try again.', false))
        .finally(() => document.getElementById('savePermsSpinner').classList.add('d-none'));
    });

    updateCount();
    </script>
    @endpush
</x-app-layout>
