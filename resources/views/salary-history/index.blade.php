<x-app-layout>
    <x-slot name="title">Salary History — {{ $employee->full_name }}</x-slot>
    <x-alert />

    {{-- ── Page Header ────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb" style="font-size:.82rem;">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.show', $employee) }}">{{ $employee->full_name }}</a></li>
                    <li class="breadcrumb-item active">Salary History</li>
                </ol>
            </nav>
            <h5 class="fw-bold mb-0">Salary History</h5>
            <small class="text-muted">{{ $employee->full_name }} &middot; {{ $employee->employee_code }}</small>
        </div>
        @can('create', [App\Models\SalaryHistory::class, $employee])
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddSalary">
            <i class="bi bi-plus-circle me-1"></i> New Salary Change
        </button>
        @endcan
    </div>

    {{-- ── Current Salary Summary ──────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="hrms-card p-4 h-100">
                <small class="text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">Current Base Salary</small>
                <div class="fs-2 fw-bold text-success mt-1">{{ currency($employee->base_salary) }}</div>
                @if($current)
                <small class="text-muted">
                    Effective {{ $current->effective_from->format('d M Y') }}
                    @if($current->effective_to) — {{ $current->effective_to->format('d M Y') }} @else — present @endif
                </small>
                @endif
            </div>
        </div>

        @if($pending->count())
        <div class="col-md-4">
            <div class="hrms-card p-4 h-100 border-warning border-start border-4">
                <small class="text-warning text-uppercase fw-semibold" style="font-size:.72rem;letter-spacing:.05em;">
                    <i class="bi bi-hourglass-split me-1"></i> Awaiting Approval
                </small>
                <div class="fs-2 fw-bold text-warning mt-1">{{ $pending->count() }}</div>
                <small class="text-muted">pending salary change{{ $pending->count() > 1 ? 's' : '' }}</small>
            </div>
        </div>
        @endif

        @php $futureCount = $histories->filter(fn($h) => $h->isFuture())->count(); @endphp
        @if($futureCount)
        <div class="col-md-4">
            <div class="hrms-card p-4 h-100 border-info border-start border-4">
                <small class="text-info text-uppercase fw-semibold" style="font-size:.72rem;letter-spacing:.05em;">
                    <i class="bi bi-calendar-event me-1"></i> Scheduled (Future)
                </small>
                <div class="fs-2 fw-bold text-info mt-1">{{ $futureCount }}</div>
                <small class="text-muted">upcoming salary change{{ $futureCount > 1 ? 's' : '' }}</small>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Pending Approval Queue (admin only) ────────────────────── --}}
    @if(auth()->user()->isAdmin() && $pendingQueue->where('employee_id', $employee->id)->count())
    <div class="hrms-card p-4 mb-4 border-warning border-start border-4">
        <h6 class="fw-semibold mb-3 text-warning"><i class="bi bi-hourglass-split me-2"></i>Pending Approval</h6>
        @foreach($pendingQueue->where('employee_id', $employee->id) as $rec)
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 border-bottom">
            <div>
                <span class="fw-semibold">{{ currency($rec->base_salary) }}</span>
                <span class="badge bg-{{ $rec->getTypeBadge() }} ms-2">{{ $rec->getTypeLabel() }}</span>
                <div class="text-muted small">
                    Effective {{ $rec->effective_from->format('M Y') }}
                    &middot; Submitted by {{ $rec->changedBy?->name ?? '—' }}
                    @if($rec->reason) &middot; {{ $rec->reason }} @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('employees.salary-history.approve', [$employee, $rec]) }}" method="POST">
                    @csrf @method('PATCH')
                    <button class="btn btn-success btn-sm" onclick="return confirm('Approve this salary change?')">
                        <i class="bi bi-check-circle me-1"></i> Approve
                    </button>
                </form>
                <button class="btn btn-outline-danger btn-sm"
                        data-bs-toggle="modal" data-bs-target="#modalReject{{ $rec->id }}">
                    <i class="bi bi-x-circle me-1"></i> Reject
                </button>
            </div>
        </div>

        {{-- Reject Modal --}}
        <div class="modal fade" id="modalReject{{ $rec->id }}" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title">Reject Salary Change</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('employees.salary-history.reject', [$employee, $rec]) }}" method="POST">
                        @csrf @method('PATCH')
                        <div class="modal-body">
                            <label class="form-label small">Rejection note (optional)</label>
                            <textarea name="note" class="form-control form-control-sm" rows="3"
                                      placeholder="Reason for rejection…"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── History Table ────────────────────────────────────────────── --}}
    <div class="hrms-card p-0 overflow-hidden">
        <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-semibold mb-0">All Salary Records</h6>
            <small class="text-muted">{{ $histories->count() }} record{{ $histories->count() !== 1 ? 's' : '' }}</small>
        </div>

        @if($histories->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-cash-stack fs-1 d-block mb-2 opacity-25"></i>
            No salary records found.
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Effective Period</th>
                        <th>Type</th>
                        <th>Previous Salary</th>
                        <th>New Salary</th>
                        <th>Change</th>
                        <th>Reason / Note</th>
                        <th>Changed By</th>
                        <th>Status</th>
                        @can('approve', App\Models\SalaryHistory::class)
                        <th class="pe-4">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach($histories as $record)
                    <tr class="{{ $record->isPending() ? 'table-warning' : ($record->isFuture() ? 'table-info bg-opacity-25' : '') }}">
                        <td class="ps-4">
                            <div class="fw-semibold">{{ $record->effective_from->format('M Y') }}</div>
                            <small class="text-muted">
                                @if($record->effective_to)
                                    to {{ $record->effective_to->format('M Y') }}
                                @elseif($record->status === 'approved')
                                    <span class="text-success fw-semibold">present</span>
                                @endif
                            </small>
                            @if($record->isActive())
                            <span class="badge bg-success-subtle text-success ms-1" style="font-size:.65rem;">ACTIVE</span>
                            @elseif($record->isFuture())
                            <span class="badge bg-info-subtle text-info ms-1" style="font-size:.65rem;">SCHEDULED</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $record->getTypeBadge() }}-subtle text-{{ $record->getTypeBadge() }}">
                                {{ $record->getTypeLabel() }}
                            </span>
                        </td>
                        <td class="text-muted">
                            {{ $record->previous_salary !== null ? currency($record->previous_salary) : '—' }}
                        </td>
                        <td class="fw-semibold text-success">{{ currency($record->base_salary) }}</td>
                        <td>
                            @if($record->previous_salary !== null)
                                @php $delta = $record->delta; @endphp
                                <span class="fw-semibold {{ $delta >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $delta >= 0 ? '+' : '' }}{{ currency($delta) }}
                                </span>
                                <br>
                                <small class="{{ $delta >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $record->delta_percent }}
                                </small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div style="max-width:200px;">
                                @if($record->reason)
                                <div class="text-truncate" title="{{ $record->reason }}">{{ $record->reason }}</div>
                                @endif
                                @if($record->note)
                                <small class="text-muted text-truncate d-block" title="{{ $record->note }}">{{ $record->note }}</small>
                                @endif
                                @if(! $record->reason && ! $record->note) <span class="text-muted">—</span> @endif
                            </div>
                        </td>
                        <td>
                            <div>{{ $record->changedBy?->name ?? '—' }}</div>
                            <small class="text-muted">{{ $record->created_at->format('d M Y') }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $record->getStatusBadge() }}-subtle text-{{ $record->getStatusBadge() }} text-capitalize">
                                {{ $record->status }}
                            </span>
                            @if($record->approvedBy && $record->status !== 'pending')
                            <div><small class="text-muted">by {{ $record->approvedBy->name }}</small></div>
                            @endif
                        </td>
                        @can('approve', App\Models\SalaryHistory::class)
                        <td class="pe-4">
                            @if($record->status === 'pending')
                            <div class="d-flex gap-1">
                                <form action="{{ route('employees.salary-history.approve', [$employee, $record]) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-success btn-xs py-0 px-2"
                                            onclick="return confirm('Approve this salary change?')"
                                            style="font-size:.75rem;">✓</button>
                                </form>
                                <button class="btn btn-outline-danger btn-xs py-0 px-2"
                                        data-bs-toggle="modal" data-bs-target="#modalRejectInline{{ $record->id }}"
                                        style="font-size:.75rem;">✗</button>
                            </div>
                            {{-- Reject Modal (inline table) --}}
                            <div class="modal fade" id="modalRejectInline{{ $record->id }}" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <div class="modal-header"><h6 class="modal-title">Reject</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <form action="{{ route('employees.salary-history.reject', [$employee, $record]) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <div class="modal-body">
                                                <textarea name="note" class="form-control form-control-sm" rows="2"
                                                          placeholder="Optional rejection note…"></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @elseif($record->salary_type !== 'initial' && ! $record->usedInPayroll() && $record->status !== 'rejected')
                            <form action="{{ route('employees.salary-history.destroy', [$employee, $record]) }}" method="POST"
                                  onsubmit="return confirm('Delete this salary record? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-xs py-0 px-2" style="font-size:.75rem;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                        @endcan
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── Add Salary Change Modal ──────────────────────────────────── --}}
    @can('create', [App\Models\SalaryHistory::class, $employee])
    <div class="modal fade" id="modalAddSalary" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold">
                        <i class="bi bi-cash-stack me-2"></i>New Salary Change — {{ $employee->full_name }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('employees.salary-history.store', $employee) }}" method="POST">
                    @csrf
                    <div class="modal-body">

                        {{-- Current salary reference --}}
                        <div class="alert alert-light border mb-3 py-2" style="font-size:.85rem;">
                            <i class="bi bi-info-circle me-1 text-primary"></i>
                            Current salary: <strong>{{ currency($employee->base_salary) }}</strong> / month
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">New Salary <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ currency_symbol() }}</span>
                                    <input type="number" name="base_salary" id="newSalaryInput"
                                           class="form-control @error('base_salary') is-invalid @enderror"
                                           value="{{ old('base_salary') }}"
                                           min="0" step="0.01" placeholder="0.00" required>
                                    @error('base_salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div id="salaryDelta" class="mt-1 small text-muted"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Effective From <span class="text-danger">*</span></label>
                                <input type="month" name="effective_from"
                                       class="form-control @error('effective_from') is-invalid @enderror"
                                       value="{{ old('effective_from', now()->format('Y-m')) }}" required>
                                @error('effective_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Can be past, current, or future month</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Change Type <span class="text-danger">*</span></label>
                                <select name="salary_type" id="salaryTypeSelect"
                                        class="form-select @error('salary_type') is-invalid @enderror" required>
                                    @foreach(\App\Models\SalaryHistory::TYPE_LABELS as $val => $label)
                                        @if($val !== 'initial')
                                        <option value="{{ $val }}" {{ old('salary_type') === $val ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('salary_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Reason</label>
                                <input type="text" name="reason"
                                       class="form-control @error('reason') is-invalid @enderror"
                                       value="{{ old('reason') }}" maxlength="300"
                                       placeholder="e.g. Annual increment, promotion, correction…">
                                @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Internal Note <span class="text-muted">(optional)</span></label>
                                <textarea name="note" class="form-control @error('note') is-invalid @enderror"
                                          rows="2" maxlength="500"
                                          placeholder="Any internal notes (not visible to employee)…">{{ old('note') }}</textarea>
                                @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        @if(! auth()->user()->isAdmin())
                        <div class="alert alert-warning mt-3 py-2 mb-0" style="font-size:.83rem;">
                            <i class="bi bi-hourglass-split me-1"></i>
                            This change will be submitted for <strong>admin approval</strong> before taking effect.
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-check-circle me-1"></i>
                            {{ auth()->user()->isAdmin() ? 'Save & Approve' : 'Submit for Approval' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const currentSalary = {{ (float) $employee->base_salary }};
        const newSalaryInput = document.getElementById('newSalaryInput');
        const deltaEl        = document.getElementById('salaryDelta');
        const typeSelect     = document.getElementById('salaryTypeSelect');

        function updateDelta() {
            if (!newSalaryInput || !deltaEl) return;
            const val = parseFloat(newSalaryInput.value);
            if (isNaN(val) || currentSalary === 0) { deltaEl.textContent = ''; return; }
            const delta = val - currentSalary;
            const pct   = ((delta / currentSalary) * 100).toFixed(1);
            const sign  = delta >= 0 ? '+' : '';
            deltaEl.innerHTML = `<span class="${delta >= 0 ? 'text-success' : 'text-danger'} fw-semibold">
                ${sign}${pct}% vs current</span>`;

            // Auto-select type based on delta
            if (typeSelect && !typeSelect.dataset.userChanged) {
                if (delta > 0) typeSelect.value = 'increment';
                else if (delta < 0) typeSelect.value = 'decrement';
                else typeSelect.value = 'adjustment';
            }
        }

        newSalaryInput?.addEventListener('input', updateDelta);
        typeSelect?.addEventListener('change', () => { typeSelect.dataset.userChanged = '1'; });

        // Re-open modal if validation failed
        @if($errors->any())
        const modal = document.getElementById('modalAddSalary');
        if (modal) { new bootstrap.Modal(modal).show(); }
        @endif
    });
    </script>
    @endpush
</x-app-layout>
