<x-app-layout>
    <x-slot name="title">Time Doctor Dashboard</x-slot>

    @php
        $formatMinutes = fn (?int $minutes) => sprintf('%dh %02dm', intdiv((int) $minutes, 60), ((int) $minutes) % 60);
        $statusClass = fn (?string $status) => match ($status) {
            'present' => 'success',
            'late' => 'warning',
            'underworked' => 'info',
            default => 'danger',
        };
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h5 class="fw-bold mb-0">Time Doctor Dashboard</h5>
            <small class="text-muted">{{ $employee->full_name }} — {{ $periodLabel }}</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form method="GET" action="{{ route('dashboard.time-doctor') }}" class="d-flex gap-2">
                <select name="month" class="form-select form-select-sm" style="width:130px">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromDate($year, $m, 1)->format('F') }}
                        </option>
                    @endfor
                </select>
                <input type="number" name="year" value="{{ $year }}" min="2020" max="{{ now()->year + 1 }}" class="form-control form-control-sm" style="width:95px">
                <button class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
            </form>
            <a href="{{ route('dashboard.crm') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-grid-1x2 me-1"></i> CRM
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-stopwatch"></i></div>
                <div>
                    <div class="label">Tracked Time</div>
                    <div class="value">{{ $formatMinutes($summary['tracked_minutes']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-lightning-charge"></i></div>
                <div>
                    <div class="label">Productive Time</div>
                    <div class="value">{{ $formatMinutes($summary['productive_minutes']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-pause-circle"></i></div>
                <div>
                    <div class="label">Idle Rate</div>
                    <div class="value">{{ number_format($summary['idle_rate'], 1) }}%</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="label">Productivity</div>
                    <div class="value">{{ number_format($summary['productivity'], 1) }}%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">Monthly Snapshot</h6>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Tracked Days</span>
                    <span class="fw-semibold">{{ $summary['days'] }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Average Daily</span>
                    <span class="fw-semibold">{{ $formatMinutes($summary['avg_tracked_minutes']) }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Underworked</span>
                    <span class="fw-semibold text-info">{{ $summary['underworked_days'] }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Late</span>
                    <span class="fw-semibold text-warning">{{ $summary['late_days'] }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Leave Alerts</span>
                    <span class="fw-semibold text-danger">{{ $summary['leave_alerts'] }}</span>
                </div>
            </div>

            <div class="hrms-card">
                <div class="card-header"><i class="bi bi-bar-chart me-2 text-primary"></i>Weekly Trend</div>
                <div class="p-3">
                    @forelse($weekly as $week)
                        @php
                            $max = max($summary['tracked_minutes'], 1);
                            $width = min(100, round(($week['tracked_minutes'] / $max) * 100));
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold">{{ $week['week'] }}</span>
                                <span class="text-muted">{{ $formatMinutes($week['tracked_minutes']) }}</span>
                            </div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar" style="width:{{ $width }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No Time Doctor data for this period.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="hrms-card">
                <div class="card-header">
                    <i class="bi bi-calendar-week me-2 text-primary"></i>Daily Time Doctor Records
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Tracked</th>
                                <th>Productive</th>
                                <th>Idle</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $record->work_date->format('M j, Y') }}</div>
                                    <small class="text-muted">{{ $record->work_date->format('D') }}</small>
                                </td>
                                <td>{{ $formatMinutes($record->time_tracked_minutes) }}</td>
                                <td>
                                    {{ $formatMinutes($record->productive_minutes ?? $record->active_minutes) }}
                                    <small class="text-muted d-block">{{ number_format($record->productivity_percentage ?? $record->activity_percent, 1) }}%</small>
                                </td>
                                <td>
                                    {{ $formatMinutes($record->idle_minutes) }}
                                    <small class="text-muted d-block">{{ number_format($record->idle_minutes_percent, 1) }}%</small>
                                </td>
                                <td>{{ $record->start_time ? substr($record->start_time, 0, 5) : '—' }}</td>
                                <td>{{ $record->end_time ? substr($record->end_time, 0, 5) : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $statusClass($record->attendance_status) }}-subtle text-{{ $statusClass($record->attendance_status) }}">
                                        {{ ucfirst($record->attendance_status) }}
                                    </span>
                                    @if($record->worked_on_leave)
                                        <span class="badge bg-warning-subtle text-warning d-block mt-1">Leave alert</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                    No Time Doctor records found for {{ $periodLabel }}.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
