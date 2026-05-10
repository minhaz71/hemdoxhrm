<x-app-layout>
    <x-slot name="title">{{ $user->name }} — Access Override</x-slot>

    <x-alert />

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
            <small class="text-muted">{{ $user->email }}</small>
        </div>
        <a href="{{ route('rbac.users.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> All Users
        </a>
    </div>

    <div class="row g-4">

        {{-- ── Left column ── --}}
        <div class="col-lg-8">

            {{-- Role assignment card --}}
            <div class="hrms-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="fw-semibold" style="font-size:.9rem;">
                        <i class="bi bi-person-badge me-2 text-primary"></i>Assigned Roles
                    </div>
                    <button class="btn btn-sm btn-primary" id="btnSaveRoles">
                        <span id="saveRolesTxt">Save Roles</span>
                        <span id="saveRolesSpin" class="spinner-border spinner-border-sm d-none ms-1"></span>
                    </button>
                </div>
                <div class="d-flex flex-wrap gap-2" id="roleChips">
                    @foreach($roles as $role)
                    <label class="d-flex align-items-center gap-2 px-3 py-2 rounded border role-chip
                                  {{ in_array($role->id, $userRoles) ? 'border-primary bg-primary-subtle' : 'border-light bg-light' }}"
                           style="cursor:pointer;">
                        <input type="checkbox" class="form-check-input role-checkbox"
                               value="{{ $role->id }}"
                               {{ in_array($role->id, $userRoles) ? 'checked' : '' }}>
                        <div>
                            <div style="font-size:.85rem;font-weight:600;">{{ $role->display_name }}</div>
                            <code style="font-size:.7rem;color:#6c757d;">{{ $role->name }}</code>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- ── Quick grant / deny panel ── --}}
            <div class="hrms-card p-4 mb-4">
                <div class="fw-semibold mb-3" style="font-size:.9rem;">
                    <i class="bi bi-bolt me-2 text-warning"></i>Quick Override
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-6">
                        <label class="form-label form-label-sm">Permission</label>
                        <select id="quickPermSelect" class="form-select form-select-sm">
                            <option value="">— Select permission —</option>
                            @foreach($permissions as $module => $perms)
                            <optgroup label="{{ strtoupper($module) }}">
                                @foreach($perms as $perm)
                                <option value="{{ $perm->id }}" data-name="{{ $perm->name }}">
                                    {{ $perm->label }} ({{ $perm->name }})
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-auto">
                        <button class="btn btn-success btn-sm" id="btnQuickGrant">
                            <i class="bi bi-plus-circle me-1"></i> Grant
                        </button>
                        <button class="btn btn-danger btn-sm" id="btnQuickDeny">
                            <i class="bi bi-dash-circle me-1"></i> Deny
                        </button>
                    </div>
                </div>
                <div id="quickMsg" class="mt-2 d-none"></div>
            </div>

            {{-- ── Active overrides ── --}}
            @php
                $grantOverrides = $overrides->filter(fn($p) => $p->pivot->granted);
                $denyOverrides  = $overrides->filter(fn($p) => !$p->pivot->granted);
            @endphp

            @if($overrides->isNotEmpty())
            <div class="hrms-card mb-4">
                <div class="card-header"><i class="bi bi-toggles me-2 text-warning"></i>Active Overrides</div>
                <div class="p-3">

                    @if($grantOverrides->isNotEmpty())
                    <div class="mb-3">
                        <div class="small fw-semibold text-success text-uppercase mb-2" style="letter-spacing:.05em;">
                            <i class="bi bi-plus-circle-fill me-1"></i> Extra Grants ({{ $grantOverrides->count() }})
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($grantOverrides as $perm)
                            <span class="badge d-inline-flex align-items-center gap-2 bg-success px-3 py-2" style="font-size:.82rem;">
                                <i class="bi bi-check-circle-fill"></i>
                                {{ $perm->label }}
                                <code style="font-size:.72rem;opacity:.8;">{{ $perm->name }}</code>
                                <button class="btn-remove-override" data-id="{{ $perm->id }}"
                                        style="background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;padding:0;line-height:1;" title="Remove override">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($denyOverrides->isNotEmpty())
                    <div>
                        <div class="small fw-semibold text-danger text-uppercase mb-2" style="letter-spacing:.05em;">
                            <i class="bi bi-dash-circle-fill me-1"></i> Explicit Denies ({{ $denyOverrides->count() }})
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($denyOverrides as $perm)
                            <span class="badge d-inline-flex align-items-center gap-2 bg-danger px-3 py-2" style="font-size:.82rem;">
                                <i class="bi bi-x-circle-fill"></i>
                                {{ $perm->label }}
                                <code style="font-size:.72rem;opacity:.8;">{{ $perm->name }}</code>
                                <button class="btn-remove-override" data-id="{{ $perm->id }}"
                                        style="background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;padding:0;line-height:1;" title="Remove override (revert to role default)">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>
            </div>
            @endif

            {{-- ── Full permission matrix ── --}}
            <div class="hrms-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-table me-2"></i>Full Permission Matrix</span>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" id="matrixFilter" class="form-control form-control-sm" placeholder="Filter…" style="width:160px;">
                        <div class="d-flex gap-1" style="font-size:.78rem;">
                            <span class="badge bg-secondary-subtle text-secondary border">From role</span>
                            <span class="badge bg-success">Extra grant</span>
                            <span class="badge bg-danger">Denied</span>
                        </div>
                    </div>
                </div>
                <div class="p-3" id="matrixBody">
                    @foreach($permissions as $module => $perms)
                    <div class="module-section mb-4" data-module="{{ $module }}">
                        <div class="small fw-semibold text-uppercase text-muted mb-2" style="letter-spacing:.05em;">
                            {{ $module }}
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0" style="font-size:.82rem;">
                                <tbody>
                                    @foreach($perms as $perm)
                                    @php
                                        $override  = $overrides->get($perm->id);
                                        $fromRole  = in_array($perm->name, $effective);
                                        $granted   = $override?->pivot->granted;
                                        $isGrant   = $override !== null && $granted;
                                        $isDeny    = $override !== null && !$granted;
                                    @endphp
                                    <tr class="perm-row" data-name="{{ $perm->name }} {{ strtolower($perm->label) }}">
                                        <td style="width:45%">
                                            <div class="fw-semibold">{{ $perm->label }}</div>
                                            <code style="font-size:.72rem;color:#6c757d;">{{ $perm->name }}</code>
                                        </td>
                                        <td style="width:25%" class="text-center">
                                            @if($isGrant)
                                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Extra grant</span>
                                            @elseif($isDeny)
                                                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Denied</span>
                                            @elseif($fromRole)
                                                <span class="badge bg-secondary-subtle text-secondary border">From role</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end" style="white-space:nowrap;width:30%">
                                            @unless($user->hasRole('admin'))
                                            <button class="btn btn-xs btn-outline-success btn-mat-grant"
                                                    data-id="{{ $perm->id }}" title="Grant this permission">
                                                <i class="bi bi-plus"></i> Grant
                                            </button>
                                            <button class="btn btn-xs btn-outline-danger btn-mat-deny"
                                                    data-id="{{ $perm->id }}" title="Deny this permission">
                                                <i class="bi bi-dash"></i> Deny
                                            </button>
                                            @if($override !== null)
                                            <button class="btn btn-xs btn-outline-secondary btn-mat-remove"
                                                    data-id="{{ $perm->id }}" title="Remove override">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                            @endif
                                            @else
                                            <span class="text-muted small">Admin (all access)</span>
                                            @endunless
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- ── Right column: summary ── --}}
        <div class="col-lg-4">

            {{-- Effective permissions summary --}}
            <div class="hrms-card p-3 mb-3">
                <div class="fw-semibold mb-3" style="font-size:.85rem;">
                    <i class="bi bi-check2-all me-2 text-success"></i>Effective Permissions
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-4 text-center">
                        <div class="fw-bold text-success" style="font-size:1.6rem;" id="effCount">{{ count($effective) }}</div>
                        <div class="text-muted" style="font-size:.72rem;">Total</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="fw-bold text-success" style="font-size:1.6rem;">{{ $grantOverrides->count() }}</div>
                        <div class="text-muted" style="font-size:.72rem;">Extra</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="fw-bold text-danger" style="font-size:1.6rem;">{{ $denyOverrides->count() }}</div>
                        <div class="text-muted" style="font-size:.72rem;">Denied</div>
                    </div>
                </div>
                @if($user->hasRole('admin'))
                <div class="alert alert-warning mb-0" style="font-size:.8rem;">
                    <i class="bi bi-lightning-fill me-1"></i>Admin has full access — overrides have no effect.
                </div>
                @else
                <div id="effList" class="d-flex flex-wrap gap-1" style="max-height:220px;overflow-y:auto;">
                    @foreach($effective as $ep)
                    <code class="badge bg-success-subtle text-success border border-success"
                          style="font-size:.7rem;">{{ $ep }}</code>
                    @endforeach
                    @if(empty($effective))
                    <span class="text-muted small">No effective permissions.</span>
                    @endif
                </div>
                @endif
            </div>

            {{-- Quick reference --}}
            <div class="hrms-card p-3">
                <div class="fw-semibold mb-2" style="font-size:.85rem;">
                    <i class="bi bi-info-circle me-2 text-info"></i>Override Rules
                </div>
                <ul class="list-unstyled mb-0" style="font-size:.8rem;color:#6c757d;">
                    <li class="mb-2"><span class="badge bg-success me-1">Grant</span> Adds permission even if role doesn't have it</li>
                    <li class="mb-2"><span class="badge bg-danger me-1">Deny</span> Removes permission even if role has it</li>
                    <li class="mb-0"><i class="bi bi-arrow-counterclockwise me-1"></i>Remove reverts to role default</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100">
        <div id="upToast" class="toast align-items-center text-white border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="upToastMsg"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
    .btn-xs { padding: 2px 8px; font-size: .72rem; line-height: 1.5; border-radius: 4px; }
    </style>
    @endpush

    @push('scripts')
    <script>
    const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
    const UID    = {{ $user->id }};
    const toastI = new bootstrap.Toast(document.getElementById('upToast'), { delay: 3000 });

    function showToast(msg, ok = true) {
        const el = document.getElementById('upToast');
        el.className = `toast align-items-center text-white border-0 ${ok ? 'bg-success' : 'bg-danger'}`;
        document.getElementById('upToastMsg').textContent = msg;
        toastI.show();
    }

    function post(url, data, method = 'POST') {
        return fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(data),
        }).then(r => r.json());
    }

    function reload() { setTimeout(() => location.reload(), 600); }

    // ── Role chips ────────────────────────────────────────────────
    document.querySelectorAll('.role-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const chip = this.closest('.role-chip');
            if (this.checked) {
                chip.classList.add('border-primary','bg-primary-subtle');
                chip.classList.remove('border-light','bg-light');
            } else {
                chip.classList.remove('border-primary','bg-primary-subtle');
                chip.classList.add('border-light','bg-light');
            }
        });
    });

    document.getElementById('btnSaveRoles').addEventListener('click', () => {
        const roleIds = [...document.querySelectorAll('.role-checkbox:checked')].map(cb => parseInt(cb.value));
        document.getElementById('saveRolesSpin').classList.remove('d-none');
        document.getElementById('saveRolesTxt').textContent = 'Saving…';
        post(`/rbac/users/${UID}/roles`, { role_ids: roleIds })
            .then(data => { showToast(data.success ? data.message : data.message, !!data.success); if (data.success) reload(); })
            .catch(() => showToast('Failed.', false))
            .finally(() => {
                document.getElementById('saveRolesSpin').classList.add('d-none');
                document.getElementById('saveRolesTxt').textContent = 'Save Roles';
            });
    });

    // ── Override helpers ──────────────────────────────────────────
    function applyOverride(permId, action) {
        const url = `/rbac/users/${UID}/permissions/${action}`;
        return post(url, { permission_id: parseInt(permId) });
    }

    function removeOverride(permId) {
        return fetch(`/rbac/users/${UID}/permissions/remove`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ permission_id: parseInt(permId) }),
        }).then(r => r.json());
    }

    // ── Quick override panel ──────────────────────────────────────
    document.getElementById('btnQuickGrant').addEventListener('click', () => {
        const sel = document.getElementById('quickPermSelect');
        if (!sel.value) { showToast('Select a permission first.', false); return; }
        applyOverride(sel.value, 'grant')
            .then(data => { showToast(data.success ? data.message : data.message, !!data.success); if (data.success) reload(); })
            .catch(() => showToast('Failed.', false));
    });

    document.getElementById('btnQuickDeny').addEventListener('click', () => {
        const sel = document.getElementById('quickPermSelect');
        if (!sel.value) { showToast('Select a permission first.', false); return; }
        applyOverride(sel.value, 'deny')
            .then(data => { showToast(data.success ? data.message : data.message, !!data.success); if (data.success) reload(); })
            .catch(() => showToast('Failed.', false));
    });

    // ── Active override badges — remove ──────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-remove-override');
        if (btn) {
            removeOverride(btn.dataset.id)
                .then(data => { showToast(data.success ? data.message : data.message, !!data.success); if (data.success) reload(); })
                .catch(() => showToast('Failed.', false));
        }

        const grant  = e.target.closest('.btn-mat-grant');
        const deny   = e.target.closest('.btn-mat-deny');
        const remove = e.target.closest('.btn-mat-remove');

        if (grant)  { applyOverride(grant.dataset.id, 'grant').then(d => { showToast(d.message, !!d.success); if (d.success) reload(); }); }
        if (deny)   { applyOverride(deny.dataset.id, 'deny').then(d => { showToast(d.message, !!d.success); if (d.success) reload(); }); }
        if (remove) { removeOverride(remove.dataset.id).then(d => { showToast(d.message, !!d.success); if (d.success) reload(); }); }
    });

    // ── Matrix filter ─────────────────────────────────────────────
    document.getElementById('matrixFilter').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.perm-row').forEach(row => {
            row.style.display = row.dataset.name.includes(q) ? '' : 'none';
        });
        document.querySelectorAll('.module-section').forEach(sec => {
            const visible = [...sec.querySelectorAll('.perm-row')].some(r => r.style.display !== 'none');
            sec.style.display = visible ? '' : 'none';
        });
    });
    </script>
    @endpush
</x-app-layout>
