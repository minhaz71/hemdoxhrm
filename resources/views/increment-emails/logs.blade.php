<x-app-layout>
<x-slot name="title">Increment Email Log</x-slot>
<x-alert />

@push('styles')
<style>
.filter-bar { background:#fff; border:1px solid #e9ecef; border-radius:10px; padding:14px 18px; margin-bottom:16px; }
</style>
@endpush

{{-- ── Header ──────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-secondary"></i>Email Send Log</h5>
        <small class="text-muted">History of all salary increment emails sent.</small>
    </div>
    <a href="{{ route('increment-emails.index') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-envelope-paper me-1"></i>Send Emails
    </a>
</div>

{{-- ── Filters ─────────────────────────────────────────────── --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('increment-emails.logs') }}" class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label class="form-label form-label-sm fw-semibold mb-1">Employee</label>
            <select name="employee_id" class="form-select form-select-sm">
                <option value="">All Employees</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ ($filters['employee_id'] ?? '') == $emp->id ? 'selected' : '' }}>
                        {{ $emp->full_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-3">
            <label class="form-label form-label-sm fw-semibold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="sent"    {{ ($filters['status'] ?? '') === 'sent'    ? 'selected' : '' }}>Sent</option>
                <option value="failed"  {{ ($filters['status'] ?? '') === 'failed'  ? 'selected' : '' }}>Failed</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
            </select>
        </div>
        <div class="col-sm-5 d-flex gap-2">
            <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="{{ route('increment-emails.logs') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>
    </form>
</div>

{{-- ── Table ────────────────────────────────────────────────── --}}
<div class="hrms-card">
    @if($logs->isEmpty())
        <p class="text-muted text-center py-5 mb-0">No email logs found.</p>
    @else
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Employee</th>
                    <th>To Email</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Sent By</th>
                    <th>Sent At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td class="text-muted" style="font-size:.8rem;">{{ $log->id }}</td>
                    <td class="fw-semibold" style="font-size:.88rem;">
                        {{ $log->employee?->full_name ?? '—' }}
                    </td>
                    <td style="font-size:.82rem;">{{ $log->email }}</td>
                    <td style="font-size:.82rem;" class="text-muted">
                        {{ \Str::limit($log->subject, 45) }}
                    </td>
                    <td>
                        <span class="badge bg-{{ $log->getStatusBadgeColor() }}-subtle text-{{ $log->getStatusBadgeColor() }}">
                            {{ ucfirst($log->status) }}
                        </span>
                        @if($log->hasFailed() && $log->error_message)
                            <i class="bi bi-info-circle text-danger ms-1"
                               title="{{ $log->error_message }}" data-bs-toggle="tooltip"></i>
                        @endif
                    </td>
                    <td style="font-size:.82rem;">{{ $log->sentBy?->name ?? '—' }}</td>
                    <td style="font-size:.82rem;">
                        {{ $log->sent_at?->format('M j, Y H:i') ?? '—' }}
                    </td>
                    <td>
                        <a href="{{ route('increment-emails.log-show', $log) }}"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="px-4 py-3">{{ $logs->links() }}</div>
    @endif
    @endif
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
@endpush

</x-app-layout>
