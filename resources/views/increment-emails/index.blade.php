<x-app-layout>
<x-slot name="title">Salary Increment Emails</x-slot>
<x-alert />

@push('styles')
<style>
.filter-bar      { background:#fff; border:1px solid #e9ecef; border-radius:10px; padding:14px 18px; }
.sent-badge      { font-size:.7rem; }
.salary-cell     { font-variant-numeric: tabular-nums; }
.ph-badge        { font-size:.68rem; background:#e8f0fe; color:#1a73e8; border-radius:4px;
                   padding:1px 5px; cursor:pointer; font-family:monospace; user-select:all; }
</style>
@endpush

{{-- ── Header ────────────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0">
            <i class="bi bi-envelope-paper me-2 text-primary"></i>Salary Increment Emails
        </h5>
        <small class="text-muted">Select employees and send salary revision notification emails.</small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#templateDrawer">
            <i class="bi bi-pencil-square me-1"></i>Edit Email Template
        </button>
        <a href="{{ route('increment-emails.logs') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clock-history me-1"></i>Email Log
        </a>
    </div>
</div>

{{-- ── Filters ─────────────────────────────────────────────────── --}}
<div class="filter-bar mb-3">
    <form method="GET" action="{{ route('increment-emails.index') }}" class="row g-2 align-items-end">
        <div class="col-sm-3">
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
        <div class="col-sm-2">
            <label class="form-label form-label-sm fw-semibold mb-1">Type</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">All Types</option>
                @foreach($types as $val => $label)
                    <option value="{{ $val }}" {{ ($filters['type'] ?? '') === $val ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label form-label-sm fw-semibold mb-1">Effective From</label>
            <input type="date" name="from" class="form-control form-control-sm"
                   value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="col-sm-2">
            <label class="form-label form-label-sm fw-semibold mb-1">Effective To</label>
            <input type="date" name="to" class="form-control form-control-sm"
                   value="{{ $filters['to'] ?? '' }}">
        </div>
        <div class="col-sm-3 d-flex gap-2">
            <button class="btn btn-primary btn-sm w-100">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
            <a href="{{ route('increment-emails.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>
    </form>
</div>

{{-- ── Selection actions ─────────────────────────────────────── --}}
<form id="emailForm" method="POST" action="{{ route('increment-emails.preview') }}">
    @csrf
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3" style="font-size:.85rem;">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleAll(this)">
                <label class="form-check-label fw-semibold" for="selectAll">Select All on Page</label>
            </div>
            <span id="selectedCount" class="text-muted">0 selected</span>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary btn-sm" id="previewBtn" disabled>
                <i class="bi bi-eye me-1"></i>Preview &amp; Send
            </button>
            <button type="button" class="btn btn-success btn-sm" id="sendDirectBtn" disabled
                    onclick="confirmSendDirect()">
                <i class="bi bi-send me-1"></i>Send to Selected
            </button>
        </div>
    </div>

    {{-- ── Records Table ──────────────────────────────────────── --}}
    <div class="hrms-card">
        @if($records->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
            No approved salary changes found.
            @if(array_filter($filters)) <br>Try removing filters. @endif
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;"></th>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Effective</th>
                        <th class="salary-cell">Previous</th>
                        <th class="salary-cell">New Salary</th>
                        <th class="salary-cell">Increment</th>
                        <th class="salary-cell text-end">%</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $sh)
                    @php
                        $empEmail = $sh->employee?->user?->email;
                        $prevSal  = (float)$sh->previous_salary;
                        $newSal   = (float)$sh->base_salary;
                        $delta    = $newSal - $prevSal;
                        $deltaPct = $prevSal > 0 ? ($delta / $prevSal) * 100 : null;
                        $sentLog  = $sentLogs[$sh->id] ?? null;
                        $noEmail  = ! $empEmail;
                    @endphp
                    <tr class="{{ $noEmail ? 'opacity-50' : '' }}">
                        <td>
                            <input type="checkbox"
                                   name="selected[]"
                                   value="{{ $sh->id }}"
                                   class="form-check-input row-check"
                                   onchange="updateCount()"
                                   {{ $noEmail ? 'disabled title="No email address on account"' : '' }}>
                        </td>
                        <td>
                            <div class="fw-semibold lh-sm">{{ $sh->employee?->full_name ?? '—' }}</div>
                            <small class="text-muted">{{ $empEmail ?? 'No email address' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $sh->getTypeBadge() }}-subtle text-{{ $sh->getTypeBadge() }}">
                                {{ $sh->getTypeLabel() }}
                            </span>
                        </td>
                        <td style="font-size:.85rem;">{{ $sh->effective_from->format('d M Y') }}</td>
                        <td class="salary-cell text-muted" style="font-size:.85rem;">
                            {{ $prevSal > 0 ? currency($prevSal) : '—' }}
                        </td>
                        <td class="salary-cell fw-semibold" style="font-size:.85rem;">
                            {{ currency($newSal) }}
                        </td>
                        <td class="salary-cell" style="font-size:.85rem;">
                            @if($prevSal > 0)
                                <span class="{{ $delta >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                    {{ $delta >= 0 ? '+' : '' }}{{ currency($delta) }}
                                </span>
                            @else — @endif
                        </td>
                        <td class="salary-cell text-end" style="font-size:.85rem;">
                            @if($deltaPct !== null)
                                <span class="{{ $deltaPct >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $deltaPct >= 0 ? '+' : '' }}{{ number_format(abs($deltaPct), 1) }}%
                                </span>
                            @else — @endif
                        </td>
                        <td>
                            @if($sentLog)
                                <span class="badge bg-success-subtle text-success sent-badge">
                                    <i class="bi bi-check-circle me-1"></i>Sent
                                </span>
                                <div class="text-muted" style="font-size:.7rem;">{{ $sentLog->sent_at?->format('M j, Y') }}</div>
                            @elseif($noEmail)
                                <span class="badge bg-light text-secondary sent-badge">No email</span>
                            @else
                                <span class="badge bg-light text-muted sent-badge">Not sent</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
            <div class="px-4 py-3">{{ $records->links() }}</div>
        @endif
        @endif
    </div>
</form>

{{-- Hidden send-direct form --}}
<form id="sendForm" method="POST" action="{{ route('increment-emails.send') }}">
    @csrf
    <input type="hidden" name="subject"           id="sd_subject"   value="{{ $template['subject'] }}">
    <input type="hidden" name="intro"             id="sd_intro"     value="{{ $template['intro'] }}">
    <input type="hidden" name="closing"           id="sd_closing"   value="{{ $template['closing'] }}">
    <input type="hidden" name="signature_name"    id="sd_sig_name"  value="{{ $template['signature_name'] }}">
    <input type="hidden" name="signature_title"   id="sd_sig_title" value="{{ $template['signature_title'] }}">
    <input type="hidden" name="signature_contact" id="sd_sig_contact" value="{{ $template['signature_contact'] }}">
    <div id="sendFormInputs"></div>
</form>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- ── Email Template Offcanvas Drawer ─────────────────────────── --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="templateDrawer"
     style="width:520px;" aria-labelledby="templateDrawerLabel">
    <div class="offcanvas-header border-bottom">
        <h6 class="offcanvas-title fw-bold mb-0" id="templateDrawerLabel">
            <i class="bi bi-pencil-square me-2 text-primary"></i>Email Template Defaults
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body p-0" style="overflow-y:auto;">
        <form method="POST" action="{{ route('increment-emails.template.save') }}" id="templateForm">
            @csrf

            {{-- ── Placeholder guide ────────────────────────── --}}
            <div class="px-4 pt-3 pb-2" style="background:#f8f9fa; border-bottom:1px solid #e9ecef;">
                <p class="fw-semibold mb-1" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;">
                    Available Placeholders
                </p>
                <div class="d-flex flex-wrap gap-1">
                    @foreach(['{employee_name}','{previous_salary}','{new_salary}','{effective_date}','{increment_amount}','{increment_percentage}','{company_name}'] as $ph)
                        <span class="ph-badge" onclick="copyPh(this)" title="Click to copy">{{ $ph }}</span>
                    @endforeach
                </div>
                <p class="text-muted mt-1 mb-0" style="font-size:.72rem;">Click any placeholder to copy it.</p>
            </div>

            <div class="p-4 d-flex flex-column gap-4">

                {{-- Subject --}}
                <div>
                    <label class="form-label fw-semibold">Email Subject <span class="text-danger">*</span></label>
                    <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                           value="{{ old('subject', $template['subject']) }}"
                           placeholder="Salary Increment Notification" required>
                    @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Opening/intro --}}
                <div>
                    <label class="form-label fw-semibold">Opening Message</label>
                    <textarea name="intro" class="form-control @error('intro') is-invalid @enderror"
                              rows="4" placeholder="Shown before the salary table…">{{ old('intro', $template['intro']) }}</textarea>
                    @error('intro') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Leave blank to use the built-in default.</small>
                </div>

                {{-- Closing --}}
                <div>
                    <label class="form-label fw-semibold">Closing Message</label>
                    <textarea name="closing" class="form-control @error('closing') is-invalid @enderror"
                              rows="3" placeholder="Shown after the salary table…">{{ old('closing', $template['closing']) }}</textarea>
                    @error('closing') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Leave blank to use the built-in default.</small>
                </div>

                {{-- Divider --}}
                <div class="border-top pt-3">
                    <p class="fw-semibold text-muted mb-3" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;">
                        Signature
                    </p>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Name</label>
                        <input type="text" name="signature_name" class="form-control form-control-sm"
                               value="{{ old('signature_name', $template['signature_name']) }}"
                               placeholder="HR Department">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Title / Designation</label>
                        <input type="text" name="signature_title" class="form-control form-control-sm"
                               value="{{ old('signature_title', $template['signature_title']) }}"
                               placeholder="Human Resources">
                    </div>
                    <div>
                        <label class="form-label form-label-sm fw-semibold">Contact <small class="fw-normal text-muted">(optional)</small></label>
                        <input type="text" name="signature_contact" class="form-control form-control-sm"
                               value="{{ old('signature_contact', $template['signature_contact']) }}"
                               placeholder="hr@company.com or +1 234 567 890">
                    </div>
                </div>

            </div>

            <div class="p-4 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-floppy me-1"></i>Save as Default Template
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleAll(master) {
    document.querySelectorAll('.row-check:not([disabled])').forEach(cb => cb.checked = master.checked);
    updateCount();
}

function updateCount() {
    const checked = document.querySelectorAll('.row-check:checked');
    const n       = checked.length;
    document.getElementById('selectedCount').textContent = n + ' selected';
    document.getElementById('previewBtn').disabled    = n === 0;
    document.getElementById('sendDirectBtn').disabled = n === 0;

    const all = document.querySelectorAll('.row-check:not([disabled])');
    const sa  = document.getElementById('selectAll');
    sa.indeterminate = n > 0 && n < all.length;
    sa.checked       = n === all.length && all.length > 0;
}

function confirmSendDirect() {
    const n = document.querySelectorAll('.row-check:checked').length;
    if (!confirm(`Send salary increment emails to ${n} employee(s) using the saved template?\n\nTip: Use "Preview & Send" to customise before sending.`)) return;
    const container = document.getElementById('sendFormInputs');
    container.innerHTML = '';
    document.querySelectorAll('.row-check:checked').forEach(cb => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'selected[]'; inp.value = cb.value;
        container.appendChild(inp);
    });
    document.getElementById('sendForm').submit();
}

function copyPh(el) {
    navigator.clipboard.writeText(el.textContent).then(() => {
        const orig = el.style.background;
        el.style.background = '#c3e6cb';
        setTimeout(() => el.style.background = orig, 700);
    }).catch(() => {});
}

// Re-open drawer if template validation errors occurred
@if($errors->any() && old('subject'))
document.addEventListener('DOMContentLoaded', () => {
    new bootstrap.Offcanvas(document.getElementById('templateDrawer')).show();
});
@endif
</script>
@endpush

</x-app-layout>
