<x-app-layout>
    <x-slot name="title">Activity — {{ $user->name }}</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
            <small class="text-muted">Login activity &amp; access details</small>
        </div>
        <a href="{{ route('activity.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> All Logs
        </a>
    </div>

    {{-- ── Summary cards ─────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        {{-- Last Login --}}
        <div class="col-md-4">
            <div class="hrms-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-box-arrow-in-right text-success fs-5"></i>
                    <span class="small fw-semibold text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em;">Last Login</span>
                </div>
                @if($lastLogin)
                <div class="fw-bold">{{ $lastLogin->created_at->format('d M Y, H:i') }}</div>
                <div class="text-muted small">{{ $lastLogin->created_at->diffForHumans() }}</div>
                <div class="mt-2 d-flex flex-wrap gap-1">
                    <span class="badge bg-light text-dark border" style="font-size:.7rem;">
                        <i class="bi {{ $lastLogin->device_icon }} me-1"></i>{{ $lastLogin->device ?? '—' }}
                    </span>
                    <span class="badge bg-light text-dark border" style="font-size:.7rem;">
                        {{ $lastLogin->browser ?? '—' }} {{ $lastLogin->browser_version }}
                    </span>
                </div>
                <code class="d-block mt-1" style="font-size:.72rem;">{{ $lastLogin->ip_address }}</code>
                @else
                <div class="text-muted small">No login recorded.</div>
                @endif
            </div>
        </div>

        {{-- Last Logout --}}
        <div class="col-md-4">
            <div class="hrms-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-box-arrow-right text-secondary fs-5"></i>
                    <span class="small fw-semibold text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em;">Last Logout</span>
                </div>
                @if($lastLogout)
                <div class="fw-bold">{{ $lastLogout->created_at->format('d M Y, H:i') }}</div>
                <div class="text-muted small">{{ $lastLogout->created_at->diffForHumans() }}</div>
                @else
                <div class="text-muted small">No logout recorded.</div>
                @endif
            </div>
        </div>

        {{-- Activity / Security --}}
        <div class="col-md-4">
            <div class="hrms-card p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-activity text-primary fs-5"></i>
                    <span class="small fw-semibold text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em;">Activity Status</span>
                </div>
                <div class="fw-bold">
                    @if($user->last_active_at)
                        @if($user->last_active_at->gte(now()->subMinutes(15)))
                            <span class="text-success"><i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Online Now</span>
                        @else
                            Last active {{ $user->last_active_at->diffForHumans() }}
                        @endif
                    @else
                        <span class="text-muted">Never active</span>
                    @endif
                </div>
                @if($failCount > 0)
                <div class="mt-2">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                        <i class="bi bi-shield-x me-1"></i>{{ $failCount }} failed attempt{{ $failCount > 1 ? 's' : '' }} (30 days)
                    </span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Log Table ─────────────────────────────────────────── --}}
    <div class="hrms-card">
        {{-- Filter tabs --}}
        <div class="p-3 border-bottom d-flex gap-2">
            <a href="{{ route('activity.user', $user) }}"
               class="btn btn-sm {{ !request('event') ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
            <a href="{{ route('activity.user', $user) }}?event=login"
               class="btn btn-sm {{ request('event') === 'login' ? 'btn-success' : 'btn-outline-secondary' }}">
               <i class="bi bi-box-arrow-in-right me-1"></i>Logins
            </a>
            <a href="{{ route('activity.user', $user) }}?event=logout"
               class="btn btn-sm {{ request('event') === 'logout' ? 'btn-secondary' : 'btn-outline-secondary' }}">
               <i class="bi bi-box-arrow-right me-1"></i>Logouts
            </a>
            <a href="{{ route('activity.user', $user) }}?event=failed"
               class="btn btn-sm {{ request('event') === 'failed' ? 'btn-danger' : 'btn-outline-secondary' }}">
               <i class="bi bi-shield-x me-1"></i>Failed
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.83rem;">
                <thead class="table-light">
                    <tr>
                        <th>Event</th>
                        <th>Device</th>
                        <th>Browser</th>
                        <th>OS</th>
                        <th>IP Address</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>
                            <span class="badge bg-{{ $log->event_color }}-subtle text-{{ $log->event_color }}">
                                <i class="bi {{ $log->event_icon }} me-1"></i>{{ $log->event_label }}
                            </span>
                        </td>
                        <td>
                            <i class="bi {{ $log->device_icon }} me-1 text-muted"></i>
                            {{ $log->device ?? '—' }}
                        </td>
                        <td>
                            {{ $log->browser ?? '—' }}
                            @if($log->browser_version)
                            <span class="text-muted">{{ $log->browser_version }}</span>
                            @endif
                        </td>
                        <td>
                            {{ $log->os ?? '—' }}
                            @if($log->os_version)
                            <span class="text-muted">{{ $log->os_version }}</span>
                            @endif
                        </td>
                        <td><code style="font-size:.75rem;">{{ $log->ip_address ?? '—' }}</code></td>
                        <td style="white-space:nowrap;">
                            <div>{{ $log->created_at->format('d M Y') }}</div>
                            <div class="text-muted" style="font-size:.73rem;">
                                {{ $log->created_at->format('H:i:s') }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                            No activity records found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="p-3 border-top">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</x-app-layout>
