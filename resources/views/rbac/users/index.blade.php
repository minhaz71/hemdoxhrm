<x-app-layout>
    <x-slot name="title">User Access Management</x-slot>

    <x-alert />

    {{-- Force-reset status banner --}}
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">User Access Management</h5>
            <small class="text-muted">Assign roles and override individual permissions per user</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('rbac.staff-users.index') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i> Manage Staff Users
            </a>
            <a href="{{ route('rbac.roles.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-shield-lock me-1"></i> Roles & Permissions
            </a>
        </div>
    </div>

    {{-- Search --}}
    <div class="hrms-card p-3 mb-4">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search by name or email…" style="max-width:320px;">
            <button type="submit" class="btn btn-primary">Search</button>
            @if($search)
            <a href="{{ route('rbac.users.index') }}" class="btn btn-outline-secondary">Clear</a>
            @endif
        </form>
    </div>

    {{-- Users table --}}
    <div class="hrms-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>All Users</span>
            <small class="text-muted">{{ $users->total() }} users</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem;">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Status</th>
                        <th>Roles</th>
                        <th class="text-center">Granted</th>
                        <th class="text-center">Denied</th>
                        <th class="text-center">Password</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white flex-shrink-0"
                                     style="width:32px;height:32px;font-size:.72rem;font-weight:700;">
                                    {{ strtoupper(substr($u->name,0,2)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $u->name }}</div>
                                    <div class="text-muted" style="font-size:.78rem;">{{ $u->email }}</div>
                                    @if($u->login_id)
                                    <div style="font-size:.7rem;color:#6c757d;"><i class="bi bi-at"></i>{{ $u->login_id }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $statusClass = match($u->status) {
                                    'active' => 'success',
                                    'suspended' => 'warning',
                                    'terminated' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }} border border-{{ $statusClass }}-subtle">
                                {{ ucfirst($u->status ?? 'unknown') }}
                            </span>
                            @if($u->last_active_at)
                            <div class="text-muted" style="font-size:.7rem;">Active {{ $u->last_active_at->diffForHumans() }}</div>
                            @endif
                        </td>
                        <td>
                            @forelse($u->roles as $role)
                                <span class="badge bg-primary-subtle text-primary me-1">{{ $role->display_name }}</span>
                            @empty
                                <span class="text-muted small">No role</span>
                            @endforelse
                        </td>
                        <td class="text-center">
                            @if($u->grants_count > 0)
                                <span class="badge bg-success-subtle text-success">+{{ $u->grants_count }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($u->denies_count > 0)
                                <span class="badge bg-danger-subtle text-danger">−{{ $u->denies_count }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($u->force_password_reset)
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"
                                      title="Force reset pending">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Force Reset
                                </span>
                            @else
                                <span class="text-muted small">Normal</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary btn-quick-check"
                                    data-id="{{ $u->id }}" data-name="{{ $u->name }}"
                                    title="Check effective permissions">
                                <i class="bi bi-search"></i>
                            </button>
                            <a href="{{ route('rbac.users.permissions', $u) }}"
                               class="btn btn-sm btn-outline-primary" title="Manage Access">
                                <i class="bi bi-person-gear"></i> Manage
                            </a>
                            {{-- Password reset dropdown --}}
                            <div class="btn-group" title="Password Actions">
                                <button type="button" class="btn btn-sm btn-outline-warning dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false"
                                        title="Password Actions">
                                    <i class="bi bi-key"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:210px;">
                                    <li>
                                        <h6 class="dropdown-header text-muted" style="font-size:.7rem;">
                                            <i class="bi bi-shield-lock me-1"></i>Password Management
                                        </h6>
                                    </li>
                                    <li>
                                        <button class="dropdown-item small btn-send-link"
                                                data-id="{{ $u->id }}"
                                                data-name="{{ $u->name }}"
                                                data-email="{{ $u->email }}">
                                            <i class="bi bi-envelope-check me-2 text-info"></i>Send Reset Link
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item small btn-direct-reset"
                                                data-id="{{ $u->id }}"
                                                data-name="{{ $u->name }}">
                                            <i class="bi bi-key me-2 text-warning"></i>Set New Password
                                        </button>
                                    </li>
                                    <li>
                                        @if($u->force_password_reset)
                                        <button class="dropdown-item small btn-clear-force"
                                                data-id="{{ $u->id }}"
                                                data-name="{{ $u->name }}">
                                            <i class="bi bi-shield-check me-2 text-success"></i>Clear Force Reset
                                        </button>
                                        @else
                                        <button class="dropdown-item small btn-force-reset"
                                                data-id="{{ $u->id }}"
                                                data-name="{{ $u->name }}">
                                            <i class="bi bi-shield-exclamation me-2 text-danger"></i>Force Reset on Login
                                        </button>
                                        @endif
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item small btn-view-logs"
                                                data-id="{{ $u->id }}"
                                                data-name="{{ $u->name }}">
                                            <i class="bi bi-clock-history me-2 text-secondary"></i>View Audit Log
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="btn-group" title="Admin Actions">
                                <button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-sliders"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:230px;">
                                    <li><h6 class="dropdown-header text-muted" style="font-size:.7rem;">Account Control</h6></li>
                                    <li>
                                        <button class="dropdown-item small btn-view-login-history"
                                                data-id="{{ $u->id }}" data-name="{{ $u->name }}">
                                            <i class="bi bi-clock-history me-2 text-primary"></i>Login History
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item small btn-view-security-audit"
                                                data-id="{{ $u->id }}" data-name="{{ $u->name }}">
                                            <i class="bi bi-journal-lock me-2 text-secondary"></i>Security Audit
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    @if($u->id !== auth()->id())
                                        @if($u->status === 'suspended')
                                        <li>
                                            <form method="POST" action="{{ route('rbac.users.activate', $u) }}">
                                                @csrf
                                                <button class="dropdown-item small" onclick="return confirm('Activate {{ $u->name }}?')">
                                                    <i class="bi bi-person-check me-2 text-success"></i>Activate User
                                                </button>
                                            </form>
                                        </li>
                                        @elseif($u->status === 'active' && !$u->isAdmin())
                                        <li>
                                            <form method="POST" action="{{ route('rbac.users.suspend', $u) }}">
                                                @csrf
                                                <button class="dropdown-item small" onclick="return confirm('Suspend {{ $u->name }} and invalidate sessions?')">
                                                    <i class="bi bi-person-x me-2 text-warning"></i>Suspend User
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        <li>
                                            <form method="POST" action="{{ route('rbac.users.force-logout', $u) }}">
                                                @csrf
                                                <button class="dropdown-item small" onclick="return confirm('Force logout {{ $u->name }} from all sessions?')">
                                                    <i class="bi bi-door-closed me-2 text-danger"></i>Force Logout
                                                </button>
                                            </form>
                                        </li>
                                        @if(auth()->user()->isAdmin() && !$u->isAdmin() && $u->status === 'active')
                                        <li>
                                            <form method="POST" action="{{ route('rbac.users.impersonate', $u) }}">
                                                @csrf
                                                <button class="dropdown-item small" onclick="return confirm('Impersonate {{ $u->name }}? This will be audited.')">
                                                    <i class="bi bi-person-badge me-2 text-info"></i>Impersonate
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                    @else
                                        <li><span class="dropdown-item small text-muted">Self-management actions disabled</span></li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-people fs-3 d-block mb-2 opacity-25"></i>
                            No users found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="p-3">{{ $users->links() }}</div>
        @endif
    </div>

    {{-- Effective Permissions Checker Modal --}}
    <div class="modal fade" id="checkerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-search me-2 text-primary"></i>
                        Effective Permissions — <span id="checkerUserName"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="checkerLoading" class="text-center py-4">
                        <span class="spinner-border text-primary"></span>
                        <div class="text-muted mt-2">Loading permissions…</div>
                    </div>
                    <div id="checkerContent" class="d-none">
                        {{-- Summary stats --}}
                        <div class="row g-3 mb-4">
                            <div class="col-4">
                                <div class="text-center p-3 rounded bg-light">
                                    <div class="fw-bold fs-4 text-success" id="cTotal">0</div>
                                    <div class="text-muted small">Effective</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center p-3 rounded bg-success-subtle">
                                    <div class="fw-bold fs-4 text-success" id="cGrants">0</div>
                                    <div class="text-muted small">Extra Grants</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center p-3 rounded bg-danger-subtle">
                                    <div class="fw-bold fs-4 text-danger" id="cDenies">0</div>
                                    <div class="text-muted small">Overridden Denies</div>
                                </div>
                            </div>
                        </div>

                        {{-- Roles --}}
                        <div class="mb-3">
                            <div class="fw-semibold small text-muted text-uppercase mb-1">Assigned Roles</div>
                            <div id="cRoles"></div>
                        </div>

                        {{-- Permission search --}}
                        <div class="mb-3">
                            <input type="text" id="permSearch" class="form-control form-control-sm"
                                   placeholder="Filter permissions…">
                        </div>

                        {{-- Effective permission list --}}
                        <div class="d-flex flex-wrap gap-1" id="cPermList"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="checkerManageLink" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-person-gear me-1"></i> Manage Access
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Password Reset Modals ─────────────────────────────────── --}}

    {{-- Send Reset Link Confirmation Modal --}}
    <div class="modal fade" id="sendLinkModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-envelope-check me-2 text-info"></i>Send Reset Link</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0 small">Send a password reset link to <strong id="sendLinkUserName"></strong>?</p>
                    <p class="text-muted small mt-1 mb-0">An email will be sent to <span id="sendLinkEmail" class="fw-semibold"></span>.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <form id="sendLinkForm" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-info btn-sm text-white">
                            <i class="bi bi-send me-1"></i>Send Link
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Direct Password Reset Modal --}}
    <div class="modal fade" id="directResetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-key me-2 text-warning"></i>Set New Password</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="directResetForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="small mb-3">Set a new password for <strong id="directResetUserName"></strong>. They will be required to change it on next login.</p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">New Password</label>
                            <div class="input-group input-group-sm">
                                <input type="password" name="password" id="drNewPwd" class="form-control"
                                       minlength="13" placeholder="At least 13 chars with mixed case, number, symbol" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleDrPwd('drNewPwd',this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-semibold">Confirm Password</label>
                            <div class="input-group input-group-sm">
                                <input type="password" name="password_confirmation" id="drConfirmPwd" class="form-control"
                                       minlength="13" placeholder="Repeat new password" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleDrPwd('drConfirmPwd',this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="bi bi-check2 me-1"></i>Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Force Reset Confirmation Modal --}}
    <div class="modal fade" id="forceResetModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-shield-exclamation me-2 text-danger"></i>Force Reset</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-0"><strong id="forceResetUserName"></strong> will be required to change their password the next time they log in.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <form id="forceResetForm" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-shield-exclamation me-1"></i>Enable Force Reset
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Clear Force Reset Confirmation Modal --}}
    <div class="modal fade" id="clearForceModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-shield-check me-2 text-success"></i>Clear Force Reset</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-0">Clear the force-reset flag for <strong id="clearForceUserName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <form id="clearForceForm" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-check2 me-1"></i>Clear Flag
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Audit Log Modal --}}
    <div class="modal fade" id="logsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-clock-history me-2 text-secondary"></i>Password Reset Log — <span id="logsUserName"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height:440px;overflow-y:auto;">
                    <div id="logsLoading" class="text-center py-4">
                        <span class="spinner-border spinner-border-sm text-secondary"></span>
                        <div class="text-muted small mt-2">Loading logs…</div>
                    </div>
                    <div id="logsContent" class="d-none">
                        <div id="logsTimeline"></div>
                    </div>
                    <div id="logsEmpty" class="d-none text-center text-muted py-4">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                        No password reset actions recorded yet.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Login History Modal --}}
    <div class="modal fade" id="loginHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-clock-history me-2 text-primary"></i>Login History — <span id="loginHistoryUserName"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height:440px;overflow-y:auto;">
                    <div id="loginHistoryLoading" class="text-center py-4">
                        <span class="spinner-border spinner-border-sm text-primary"></span>
                        <div class="text-muted small mt-2">Loading login history…</div>
                    </div>
                    <div id="loginHistoryContent" class="d-none"></div>
                    <div id="loginHistoryEmpty" class="d-none text-center text-muted py-4">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                        No login activity recorded.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Security Audit Modal --}}
    <div class="modal fade" id="securityAuditModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-journal-lock me-2 text-secondary"></i>Security Audit — <span id="securityAuditUserName"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height:440px;overflow-y:auto;">
                    <div id="securityAuditLoading" class="text-center py-4">
                        <span class="spinner-border spinner-border-sm text-secondary"></span>
                        <div class="text-muted small mt-2">Loading audit events…</div>
                    </div>
                    <div id="securityAuditContent" class="d-none"></div>
                    <div id="securityAuditEmpty" class="d-none text-center text-muted py-4">
                        <i class="bi bi-shield-check fs-2 d-block mb-2 opacity-25"></i>
                        No security actions recorded.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100">
        <div id="uiToast" class="toast align-items-center text-white border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="uiToastMsg"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
    const checkerModal    = new bootstrap.Modal(document.getElementById('checkerModal'));
    const sendLinkModal   = new bootstrap.Modal(document.getElementById('sendLinkModal'));
    const directResetModal= new bootstrap.Modal(document.getElementById('directResetModal'));
    const forceResetModal = new bootstrap.Modal(document.getElementById('forceResetModal'));
    const clearForceModal = new bootstrap.Modal(document.getElementById('clearForceModal'));
    const logsModal       = new bootstrap.Modal(document.getElementById('logsModal'));
    const loginHistoryModal = new bootstrap.Modal(document.getElementById('loginHistoryModal'));
    const securityAuditModal = new bootstrap.Modal(document.getElementById('securityAuditModal'));
    const toastI          = new bootstrap.Toast(document.getElementById('uiToast'), { delay: 3000 });

    // ── Send Reset Link ─────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-send-link');
        if (!btn) return;
        document.getElementById('sendLinkUserName').textContent = btn.dataset.name;
        document.getElementById('sendLinkEmail').textContent    = btn.dataset.email;
        document.getElementById('sendLinkForm').action = `/rbac/users/${btn.dataset.id}/send-reset-link`;
        sendLinkModal.show();
    });

    // ── Login History ──────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-view-login-history');
        if (!btn) return;

        document.getElementById('loginHistoryUserName').textContent = btn.dataset.name;
        document.getElementById('loginHistoryLoading').classList.remove('d-none');
        document.getElementById('loginHistoryContent').classList.add('d-none');
        document.getElementById('loginHistoryEmpty').classList.add('d-none');
        loginHistoryModal.show();

        fetch(`/rbac/users/${btn.dataset.id}/login-history`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(rows => {
            document.getElementById('loginHistoryLoading').classList.add('d-none');
            if (!rows.length) {
                document.getElementById('loginHistoryEmpty').classList.remove('d-none');
                return;
            }
            document.getElementById('loginHistoryContent').innerHTML = rows.map(row => `
                <div class="d-flex justify-content-between gap-3 border-bottom pb-2 mb-2">
                    <div>
                        <div class="fw-semibold small">${escHtml(row.event)}</div>
                        <div class="text-muted" style="font-size:.78rem;">
                            ${escHtml(row.browser)} · ${escHtml(row.os)} · ${escHtml(row.device ?? '')}
                        </div>
                    </div>
                    <div class="text-end text-muted" style="font-size:.78rem;">
                        <div>${escHtml(row.created_at)}</div>
                        <code>${escHtml(row.ip_address ?? '')}</code>
                    </div>
                </div>
            `).join('');
            document.getElementById('loginHistoryContent').classList.remove('d-none');
        });
    });

    // ── Security Audit ─────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-view-security-audit');
        if (!btn) return;

        document.getElementById('securityAuditUserName').textContent = btn.dataset.name;
        document.getElementById('securityAuditLoading').classList.remove('d-none');
        document.getElementById('securityAuditContent').classList.add('d-none');
        document.getElementById('securityAuditEmpty').classList.add('d-none');
        securityAuditModal.show();

        fetch(`/rbac/users/${btn.dataset.id}/audit-history`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(rows => {
            document.getElementById('securityAuditLoading').classList.add('d-none');
            if (!rows.length) {
                document.getElementById('securityAuditEmpty').classList.remove('d-none');
                return;
            }
            document.getElementById('securityAuditContent').innerHTML = rows.map(row => `
                <div class="d-flex justify-content-between gap-3 border-bottom pb-2 mb-2">
                    <div>
                        <div class="fw-semibold small">
                            <span class="badge bg-light text-dark border me-1">${escHtml(row.severity)}</span>
                            ${escHtml(row.event)}
                        </div>
                        <div class="text-muted" style="font-size:.78rem;">By ${escHtml(row.actor)}</div>
                    </div>
                    <div class="text-end text-muted" style="font-size:.78rem;">
                        <div>${escHtml(row.created_at)}</div>
                        <code>${escHtml(row.ip_address ?? '')}</code>
                    </div>
                </div>
            `).join('');
            document.getElementById('securityAuditContent').classList.remove('d-none');
        });
    });

    // ── Direct Reset ────────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-direct-reset');
        if (!btn) return;
        document.getElementById('directResetUserName').textContent = btn.dataset.name;
        document.getElementById('directResetForm').action = `/rbac/users/${btn.dataset.id}/reset-password`;
        document.getElementById('drNewPwd').value = '';
        document.getElementById('drConfirmPwd').value = '';
        directResetModal.show();
    });

    // ── Force Reset ─────────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-force-reset');
        if (!btn) return;
        document.getElementById('forceResetUserName').textContent = btn.dataset.name;
        document.getElementById('forceResetForm').action = `/rbac/users/${btn.dataset.id}/force-reset`;
        forceResetModal.show();
    });

    // ── Clear Force Reset ───────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-clear-force');
        if (!btn) return;
        document.getElementById('clearForceUserName').textContent = btn.dataset.name;
        document.getElementById('clearForceForm').action = `/rbac/users/${btn.dataset.id}/clear-force-reset`;
        clearForceModal.show();
    });

    // ── View Audit Logs ─────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-view-logs');
        if (!btn) return;
        document.getElementById('logsUserName').textContent = btn.dataset.name;
        document.getElementById('logsLoading').classList.remove('d-none');
        document.getElementById('logsContent').classList.add('d-none');
        document.getElementById('logsEmpty').classList.add('d-none');
        logsModal.show();

        fetch(`/rbac/users/${btn.dataset.id}/reset-logs`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(logs => {
            document.getElementById('logsLoading').classList.add('d-none');
            if (!logs.length) {
                document.getElementById('logsEmpty').classList.remove('d-none');
                return;
            }
            const colorMap = {
                reset_link_sent:      'info',
                password_reset:       'warning',
                force_reset_enabled:  'danger',
                force_reset_disabled: 'success',
            };
            const iconMap = {
                reset_link_sent:      'bi-envelope-check',
                password_reset:       'bi-key',
                force_reset_enabled:  'bi-shield-exclamation',
                force_reset_disabled: 'bi-shield-check',
            };
            document.getElementById('logsTimeline').innerHTML = logs.map(l => `
                <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-${l.action_color}"
                             style="width:36px;height:36px;background:var(--bs-${l.action_color}-bg-subtle,#f8f9fa);font-size:1rem;">
                            <i class="bi ${l.action_icon}"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">${escHtml(l.action_label)}</div>
                        <div class="text-muted" style="font-size:.78rem;">
                            By <strong>${escHtml(l.admin_name)}</strong>
                            · ${escHtml(l.created_at)}
                            ${l.ip_address ? '· IP: <code>' + escHtml(l.ip_address) + '</code>' : ''}
                        </div>
                        ${l.note ? `<div class="text-muted mt-1" style="font-size:.78rem;">${escHtml(l.note)}</div>` : ''}
                    </div>
                </div>
            `).join('');
            document.getElementById('logsContent').classList.remove('d-none');
        })
        .catch(() => {
            document.getElementById('logsLoading').innerHTML = '<p class="text-danger text-center small">Failed to load logs.</p>';
        });
    });

    // ── Password field toggle ───────────────────────────────────────
    function toggleDrPwd(id, btn) {
        const input = document.getElementById(id);
        const icon  = btn.querySelector('i');
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
    }

    let allEffectivePerms = [];

    // Permission checker
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-quick-check');
        if (!btn) return;

        const uid  = btn.dataset.id;
        const name = btn.dataset.name;
        document.getElementById('checkerUserName').textContent = name;
        document.getElementById('checkerManageLink').href = `/rbac/users/${uid}/permissions`;
        document.getElementById('checkerLoading').classList.remove('d-none');
        document.getElementById('checkerContent').classList.add('d-none');
        document.getElementById('permSearch').value = '';
        checkerModal.show();

        fetch(`/rbac/users/${uid}/permissions/effective`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            allEffectivePerms = data.effective ?? [];

            document.getElementById('cTotal').textContent  = data.total_effective;
            document.getElementById('cGrants').textContent = data.overrides_granted?.length ?? 0;
            document.getElementById('cDenies').textContent = data.overrides_denied?.length ?? 0;

            // Roles
            const roles = data.roles ?? [];
            document.getElementById('cRoles').innerHTML = roles.length
                ? roles.map(r => `<span class="badge bg-primary-subtle text-primary me-1">${escHtml(r)}</span>`).join('')
                : '<span class="text-muted small">No roles assigned</span>';

            // Permission list
            renderPermList(allEffectivePerms, data.overrides_granted ?? [], data.overrides_denied ?? []);

            document.getElementById('checkerLoading').classList.add('d-none');
            document.getElementById('checkerContent').classList.remove('d-none');
        })
        .catch(() => {
            document.getElementById('checkerLoading').innerHTML =
                '<p class="text-danger text-center">Failed to load permissions.</p>';
        });
    });

    function renderPermList(perms, grants, denies, filter = '') {
        const grantSet = new Set(grants);
        const denySet  = new Set(denies);
        const container = document.getElementById('cPermList');
        const filtered  = filter ? perms.filter(p => p.includes(filter.toLowerCase())) : perms;

        if (filtered.length === 0) {
            container.innerHTML = '<span class="text-muted small">No permissions match.</span>';
            return;
        }

        container.innerHTML = filtered.map(p => {
            const isGrant = grantSet.has(p);
            const isDeny  = denySet.has(p);
            const cls = isGrant ? 'bg-success text-white' : (isDeny ? 'bg-danger text-white' : 'bg-light text-dark border');
            const icon = isGrant ? '↑ ' : (isDeny ? '↓ ' : '');
            return `<code class="badge ${cls}" style="font-size:.72rem;" title="${isGrant ? 'Extra grant' : isDeny ? 'Overridden deny' : 'From role'}">${icon}${escHtml(p)}</code>`;
        }).join(' ');
    }

    document.getElementById('permSearch').addEventListener('input', function() {
        // Re-render with filter — we'd need grants/denies in closure
        // Simple approach: reload from server or filter display
        const rows = document.querySelectorAll('#cPermList code');
        rows.forEach(el => {
            el.style.display = el.textContent.includes(this.value.toLowerCase()) ? '' : 'none';
        });
    });

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    </script>
    @endpush
</x-app-layout>
