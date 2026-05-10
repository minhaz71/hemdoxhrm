<x-app-layout>
    <x-slot name="title">Activity Logs</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">User Activity Logs</h5>
            <small class="text-muted">Login, logout, and failed attempt history</small>
        </div>
    </div>

    {{-- ── Summary Stats ──────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="hrms-card p-3 d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:10px;background:#d1fae5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-box-arrow-in-right text-success fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold fs-4 lh-1">{{ number_format($stats['logins']) }}</div>
                    <div class="text-muted small">Logins (30 days)</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="hrms-card p-3 d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:10px;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-shield-x text-danger fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold fs-4 lh-1">{{ number_format($stats['failed']) }}</div>
                    <div class="text-muted small">Failed Attempts (30 days)</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="hrms-card p-3 d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:10px;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-people text-primary fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold fs-4 lh-1">{{ number_format($stats['unique']) }}</div>
                    <div class="text-muted small">Unique Users (30 days)</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="hrms-card p-3 d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:10px;background:#fef3c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-activity text-warning fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold fs-4 lh-1" id="activeNow">—</div>
                    <div class="text-muted small">Active (last 15 min)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ── Main log table ──────────────────────────────────── --}}
        <div class="col-lg-9">

            {{-- Filters --}}
            <form method="GET" class="hrms-card p-3 mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Search User</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Name or email…" value="{{ $filters['search'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Event</label>
                        <select name="event" class="form-select form-select-sm">
                            <option value="">All Events</option>
                            <option value="login"  {{ ($filters['event'] ?? '') === 'login'  ? 'selected' : '' }}>Login</option>
                            <option value="logout" {{ ($filters['event'] ?? '') === 'logout' ? 'selected' : '' }}>Logout</option>
                            <option value="failed" {{ ($filters['event'] ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">IP Address</label>
                        <input type="text" name="ip" class="form-control form-control-sm"
                               placeholder="192.168…" value="{{ $filters['ip'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                               value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                               value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        @if(array_filter($filters))
                        <a href="{{ route('activity.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-lg"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Log Table --}}
            <div class="hrms-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle" style="font-size:.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Event</th>
                                <th>Device / Browser</th>
                                <th>OS</th>
                                <th>IP Address</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>
                                    @if($log->user)
                                    <div class="fw-semibold">{{ $log->user->name }}</div>
                                    <div class="text-muted" style="font-size:.75rem;">{{ $log->user->email }}</div>
                                    @else
                                    <div class="text-muted fst-italic small">
                                        {{ $log->identifier ?? 'Unknown' }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-1">No account</span>
                                    </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->event_color }}-subtle text-{{ $log->event_color }}">
                                        <i class="bi {{ $log->event_icon }} me-1"></i>{{ $log->event_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="bi {{ $log->device_icon }} text-muted"></i>
                                        <span>{{ $log->device ?? '—' }}</span>
                                    </div>
                                    <div class="text-muted" style="font-size:.73rem;">
                                        {{ $log->browser ?? '—' }}
                                        @if($log->browser_version) {{ $log->browser_version }} @endif
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $log->os ?? '—' }}</div>
                                    @if($log->os_version)
                                    <div class="text-muted" style="font-size:.73rem;">{{ $log->os_version }}</div>
                                    @endif
                                </td>
                                <td>
                                    <code style="font-size:.75rem;">{{ $log->ip_address ?? '—' }}</code>
                                </td>
                                <td style="white-space:nowrap;">
                                    <div>{{ $log->created_at->format('d M Y') }}</div>
                                    <div class="text-muted" style="font-size:.73rem;">
                                        {{ $log->created_at->format('H:i:s') }}
                                        <span class="ms-1 text-muted opacity-75" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                            ({{ $log->created_at->diffForHumans() }})
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                                    No activity logs match your filters.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($logs->hasPages())
                <div class="p-3 border-top">
                    {{ $logs->appends($filters)->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>
        </div>

        {{-- ── Right sidebar ────────────────────────────────────── --}}
        <div class="col-lg-3">

            {{-- Failed login IPs --}}
            <div class="hrms-card p-3 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em;">
                    <i class="bi bi-exclamation-triangle me-1 text-danger"></i>
                    Top Failed IPs (7 days)
                </h6>
                @forelse($topFailedIps as $row)
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <code style="font-size:.75rem;">{{ $row->ip_address }}</code>
                    <span class="badge bg-danger-subtle text-danger">{{ $row->attempts }}×</span>
                </div>
                @empty
                <p class="text-muted small mb-0">No failed attempts in last 7 days.</p>
                @endforelse
            </div>

            {{-- User quick filter --}}
            <div class="hrms-card p-3">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em;">
                    <i class="bi bi-person me-1"></i> View User Activity
                </h6>
                <div class="list-group list-group-flush" style="font-size:.82rem;max-height:400px;overflow-y:auto;">
                    @foreach($users as $u)
                    <a href="{{ route('activity.user', $u) }}"
                       class="list-group-item list-group-item-action py-2 px-1">
                        <div class="fw-semibold">{{ $u->name }}</div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $u->email }}</div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    // Fetch active-now count
    fetch('{{ route('activity.stats') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(d => {
            document.getElementById('activeNow').textContent = d.active_now ?? '—';
        })
        .catch(() => {});
    </script>
    @endpush
</x-app-layout>
