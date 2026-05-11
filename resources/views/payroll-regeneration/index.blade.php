<x-app-layout>
<x-slot name="title">Payroll Regeneration</x-slot>
<x-alert />

@push('styles')
<style>
.regen-card { background: #fff; border: 1px solid #e9ecef; border-radius: 12px; }
.snapshot-diff td { font-size: .82rem; }
.locked-warning { background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 6px; }
</style>
@endpush

{{-- ── Page Header ──────────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-arrow-repeat me-2 text-warning"></i>Payroll Regeneration</h5>
        <small class="text-muted">Recalculate payrolls using the latest salary and attendance data.</small>
    </div>
    <a href="{{ route('payroll-regeneration.logs') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-clock-history me-1"></i> View Audit Log
    </a>
</div>

{{-- ── Locked-payroll Confirmation Banner ──────────────────────────── --}}
@if(session('confirm_locked'))
@php $cl = session('confirm_locked'); @endphp
<div class="locked-warning p-4 mb-4">
    <h6 class="fw-bold text-warning mb-2">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Locked Payroll Override
    </h6>
    <p class="mb-3">
        The payroll for <strong>{{ $cl['employee'] }}</strong> — <strong>{{ $cl['period'] }}</strong>
        is <span class="badge bg-success">Paid &amp; Locked</span>.
        @if($cl['paid_at'])
            It was paid on <strong>{{ $cl['paid_at'] }}</strong>.
        @endif
        Regenerating it will reset it to <strong>Draft</strong> status and invalidate the existing payslip.
        The old data will be preserved in the audit log.
    </p>
    <form method="POST" action="{{ route('payroll-regeneration.store') }}">
        @csrf
        <input type="hidden" name="employee_id"           value="{{ $cl['employee_id'] }}">
        <input type="hidden" name="month"                 value="{{ $cl['month'] }}">
        <input type="hidden" name="year"                  value="{{ $cl['year'] }}">
        <input type="hidden" name="force_locked_override" value="1">
        <div class="mb-3">
            <label class="form-label fw-semibold">Reason for overriding locked payroll <span class="text-danger">*</span></label>
            <textarea name="reason" class="form-control @error('reason') is-invalid @enderror"
                      rows="2" required placeholder="e.g. Salary record was corrected retroactively…">{{ old('reason') }}</textarea>
            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning">
                <i class="bi bi-arrow-repeat me-1"></i> Confirm — Regenerate Locked Payroll
            </button>
            <a href="{{ route('payroll-regeneration.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endif

<div class="row g-4">

    {{-- ── Single-Employee Regeneration ──────────────────────────── --}}
    <div class="col-lg-6">
        <div class="regen-card p-4 h-100">
            <h6 class="fw-semibold mb-1"><i class="bi bi-person-fill me-2 text-primary"></i>Single Employee</h6>
            <p class="text-muted mb-3" style="font-size:.85rem;">
                Regenerate payroll for one employee in a specific month.
                If the payroll is already <strong>paid/locked</strong>, you must provide a reason and confirm the override.
            </p>

            <form method="POST" action="{{ route('payroll-regeneration.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                        <option value="">— Select Employee —</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->full_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                        <select name="month" class="form-select @error('month') is-invalid @enderror" required>
                            @foreach(range(1,12) as $m)
                                <option value="{{ $m }}" {{ old('month', now()->month) == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                        @error('month') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
                        <select name="year" class="form-select @error('year') is-invalid @enderror" required>
                            @foreach(range(now()->year, now()->year - 3) as $y)
                                <option value="{{ $y }}" {{ old('year', now()->year) == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                        @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Reason
                        <small class="text-muted fw-normal">(required if payroll is locked)</small>
                    </label>
                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror"
                              rows="2" placeholder="Optional note for audit trail…">{{ old('reason') }}</textarea>
                    @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-warning w-100">
                    <i class="bi bi-arrow-repeat me-2"></i>Regenerate Payroll
                </button>
            </form>
        </div>
    </div>

    {{-- ── Bulk Regeneration ───────────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="regen-card p-4 h-100">
            <h6 class="fw-semibold mb-1"><i class="bi bi-people-fill me-2 text-danger"></i>Bulk — Entire Period</h6>
            <p class="text-muted mb-3" style="font-size:.85rem;">
                Regenerate all payrolls for a given month. By default only regenerates <strong>unpaid</strong> payrolls.
                Check <em>Include Locked</em> to also override paid ones (admin-only; reason required).
            </p>

            <form method="POST" action="{{ route('payroll-regeneration.bulk') }}"
                  onsubmit="return confirmBulk(this)">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                        <select name="month" class="form-select" required>
                            @foreach(range(1,12) as $m)
                                <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
                        <select name="year" class="form-select" required>
                            @foreach(range(now()->year, now()->year - 3) as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if(auth()->user()->isAdmin())
                <div class="form-check mb-3" id="include-locked-wrap">
                    <input class="form-check-input" type="checkbox" name="include_locked" value="1"
                           id="includeLocked" onchange="toggleLockedReason(this)">
                    <label class="form-check-label" for="includeLocked">
                        <span class="badge bg-danger me-1">Admin</span>
                        Also regenerate <strong>paid/locked</strong> payrolls
                    </label>
                </div>

                <div class="mb-3 d-none" id="locked-reason-wrap">
                    <label class="form-label fw-semibold">Reason for overriding locked payrolls <span class="text-danger">*</span></label>
                    <textarea name="reason" id="lockedReason" class="form-control" rows="2"
                              placeholder="Required when overriding locked payrolls…"></textarea>
                </div>
                @endif

                <button type="submit" class="btn btn-danger w-100">
                    <i class="bi bi-arrow-repeat me-2"></i>Bulk Regenerate Period
                </button>

                <p class="text-muted mt-2 mb-0" style="font-size:.78rem;">
                    <i class="bi bi-info-circle me-1"></i>
                    Employees with no existing payroll for the period will be skipped.
                </p>
            </form>
        </div>
    </div>
</div>

{{-- ── Recent Logs ───────────────────────────────────────────────── --}}
<div class="regen-card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2"></i>Recent Regenerations</span>
        <a href="{{ route('payroll-regeneration.logs') }}" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="p-0">
        @if($logs->isEmpty())
            <p class="text-muted text-center py-4 mb-0">No regeneration history yet.</p>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Period</th>
                        <th>Was Locked</th>
                        <th>Reason</th>
                        <th>By</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->employee?->full_name ?? '—' }}</td>
                        <td>{{ $log->month_label }}</td>
                        <td>
                            @if($log->was_locked)
                                <span class="badge bg-danger-subtle text-danger">Locked override</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:.82rem;">
                            {{ $log->reason ? \Str::limit($log->reason, 40) : '—' }}
                        </td>
                        <td>{{ $log->regeneratedBy?->name ?? '—' }}</td>
                        <td style="font-size:.82rem;">{{ $log->created_at->format('M j, Y H:i') }}</td>
                        <td>
                            <a href="{{ route('payroll-regeneration.log-show', $log) }}" class="btn btn-sm btn-outline-secondary">
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
</div>

@push('scripts')
<script>
function toggleLockedReason(cb) {
    const wrap = document.getElementById('locked-reason-wrap');
    const ta   = document.getElementById('lockedReason');
    if (cb.checked) {
        wrap.classList.remove('d-none');
        ta.required = true;
    } else {
        wrap.classList.add('d-none');
        ta.required = false;
    }
}

function confirmBulk(form) {
    const includeLocked = form.querySelector('#includeLocked');
    if (includeLocked && includeLocked.checked) {
        return confirm('⚠️ You are about to regenerate ALL payrolls in this period, including paid/locked ones.\n\nThis will reset them to Draft status and invalidate existing payslips.\n\nThe old data will be preserved in the audit log.\n\nAre you sure?');
    }
    return confirm('Regenerate all UNPAID payrolls in this period? This cannot be undone.');
}
</script>
@endpush

</x-app-layout>
