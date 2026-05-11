<x-app-layout>
    <x-slot name="title">Error Logs</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0">Error Log Viewer</h5>
            <small class="text-muted">Admin-only Laravel log monitor</small>
        </div>
        <form method="POST" action="{{ route('admin.error-logs.clear') }}"
              onsubmit="return confirm('Clear this log file? This cannot be undone.')">
            @csrf
            <input type="hidden" name="file" value="{{ $filters['file'] }}">
            <button class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash me-1"></i> Clear Current Log
            </button>
        </form>
    </div>

    <div class="hrms-card p-4 mb-4">
        <form method="GET" action="{{ route('admin.error-logs.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Log File</label>
                <select name="file" class="form-select">
                    @foreach($files as $file)
                    <option value="{{ $file['name'] }}" {{ $filters['file'] === $file['name'] ? 'selected' : '' }}>
                        {{ $file['name'] }} ({{ number_format($file['size'] / 1024, 1) }} KB)
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Level</label>
                <select name="level" class="form-select">
                    <option value="">All</option>
                    @foreach(['error', 'warning', 'info', 'debug'] as $level)
                    <option value="{{ $level }}" {{ $filters['level'] === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Limit</label>
                <input type="number" name="limit" value="{{ $filters['limit'] }}" min="25" max="500" class="form-control">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <div class="hrms-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-bug me-2 text-danger"></i>Latest Entries</span>
            <span class="badge bg-secondary-subtle text-secondary">{{ $entries->count() }} shown</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width:170px;">Time</th>
                        <th style="width:95px;">Level</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    @php
                        $badge = match($entry['level']) {
                            'error' => 'danger',
                            'warning' => 'warning',
                            'debug' => 'secondary',
                            default => 'info',
                        };
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $entry['date'] ?? '—' }}</td>
                        <td><span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}">{{ strtoupper($entry['level']) }}</span></td>
                        <td>
                            <code class="text-wrap d-block" style="white-space:pre-wrap;">{{ $entry['message'] }}</code>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle fs-2 d-block mb-2 opacity-50"></i>
                            No log entries found for this filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
