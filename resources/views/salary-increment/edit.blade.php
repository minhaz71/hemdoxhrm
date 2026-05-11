<x-app-layout>
<x-slot name="title">Edit Salary Increment</x-slot>
<x-alert />

@push('styles')
<style>
.calc-preview { background:#f8f9fa; border:1px solid #dee2e6; border-radius:8px; padding:14px 16px; }
.mode-card { border:2px solid #dee2e6; border-radius:8px; padding:14px; cursor:pointer; transition:border-color .15s; }
.mode-card.active { border-color: var(--hrms-primary, #4e9af1); background:#f0f7ff; }
</style>
@endpush

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <nav aria-label="breadcrumb" style="font-size:.82rem;">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('salary-increments.index') }}">Salary Increments</a></li>
                <li class="breadcrumb-item"><a href="{{ route('salary-increments.show', $salaryIncrement) }}">#{{ $salaryIncrement->id }}</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
        <h5 class="fw-bold mb-0">Edit Salary Increment</h5>
        <small class="text-muted">
            {{ $salaryIncrement->employee?->full_name }} &middot;
            Submitted {{ $salaryIncrement->created_at->format('d M Y H:i') }}
            by {{ $salaryIncrement->changedBy?->name }}
        </small>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="hrms-card p-4">

@if($errors->any())
<div class="alert alert-danger mb-4">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Employee (read-only) --}}
<div class="mb-4">
    <label class="form-label fw-semibold">Employee</label>
    <div class="form-control bg-light text-muted">
        {{ $salaryIncrement->employee?->full_name }} ({{ $salaryIncrement->employee?->employee_code }})
    </div>
    <div class="mt-1 text-info small">
        <i class="bi bi-info-circle me-1"></i>
        Current salary on record: <strong>{{ currency($currentSalary) }}</strong>
    </div>
</div>

<form method="POST" action="{{ route('salary-increments.update', $salaryIncrement) }}" id="incrementForm">
@csrf
@method('PUT')

{{-- Type --}}
<div class="mb-4">
    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
    <div class="d-flex gap-3">
        @foreach(['increment' => ['success','graph-up-arrow','Increment'], 'decrement' => ['danger','graph-down-arrow','Decrement'], 'adjustment' => ['warning','sliders','Adjustment']] as $val => [$color, $icon, $label])
        <div class="form-check">
            <input class="form-check-input" type="radio" name="salary_type" id="type_{{ $val }}"
                   value="{{ $val }}" {{ old('salary_type', $salaryIncrement->salary_type) === $val ? 'checked' : '' }}>
            <label class="form-check-label" for="type_{{ $val }}">
                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} px-2">
                    <i class="bi bi-{{ $icon }} me-1"></i>{{ $label }}
                </span>
            </label>
        </div>
        @endforeach
    </div>
    @error('salary_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

{{-- Input Mode --}}
@php
$initialMode = old('_mode', $salaryIncrement->increment_amount !== null ? 'amount' : ($salaryIncrement->increment_percentage !== null ? 'percentage' : 'new_salary'));
@endphp
<div class="mb-4">
    <label class="form-label fw-semibold">Input Mode</label>
    <div class="row g-3">
        @foreach(['new_salary' => 'New Salary', 'amount' => 'Amount', 'percentage' => 'Percentage'] as $mVal => $mLabel)
        <div class="col-md-4">
            <div class="mode-card {{ $initialMode === $mVal ? 'active' : '' }}" data-mode="{{ $mVal }}" onclick="setMode('{{ $mVal }}')">
                <div class="fw-semibold small">{{ $mLabel }}</div>
            </div>
        </div>
        @endforeach
    </div>
    <input type="hidden" name="_mode" id="modeInput" value="{{ $initialMode }}">
</div>

{{-- Input fields --}}
<div class="row g-3 mb-4">
    <div class="col-md-6" id="field_new_salary" style="display:none;">
        <label class="form-label fw-semibold small">New Salary</label>
        <div class="input-group">
            <span class="input-group-text">{{ setting('currency_symbol','৳') }}</span>
            <input type="number" name="new_salary" id="new_salary" step="0.01" min="0"
                   class="form-control @error('new_salary') is-invalid @enderror"
                   value="{{ old('new_salary', $salaryIncrement->base_salary) }}"
                   oninput="recalculate()">
        </div>
    </div>
    <div class="col-md-6" id="field_amount">
        <label class="form-label fw-semibold small">Increment / Decrement Amount</label>
        <div class="input-group">
            <span class="input-group-text">{{ setting('currency_symbol','৳') }}</span>
            <input type="number" name="increment_amount" id="increment_amount" step="0.01"
                   class="form-control @error('increment_amount') is-invalid @enderror"
                   value="{{ old('increment_amount', $salaryIncrement->increment_amount) }}"
                   oninput="recalculate()">
        </div>
    </div>
    <div class="col-md-6" id="field_percentage" style="display:none;">
        <label class="form-label fw-semibold small">Increment Percentage</label>
        <div class="input-group">
            <input type="number" name="increment_percentage" id="increment_percentage" step="0.01"
                   class="form-control @error('increment_percentage') is-invalid @enderror"
                   value="{{ old('increment_percentage', $salaryIncrement->increment_percentage) }}"
                   oninput="recalculate()">
            <span class="input-group-text">%</span>
        </div>
    </div>
</div>

{{-- Live preview --}}
<div class="calc-preview mb-4" id="calcPreview">
    <div class="row g-3 text-center">
        <div class="col-4">
            <div class="text-muted small">Previous Salary</div>
            <div class="fw-bold" id="previewPrev">{{ currency($currentSalary) }}</div>
        </div>
        <div class="col-4">
            <div class="text-muted small">Change</div>
            <div class="fw-bold" id="previewDelta">—</div>
        </div>
        <div class="col-4">
            <div class="text-muted small">New Salary</div>
            <div class="fw-bold text-success fs-5" id="previewNew">{{ currency($salaryIncrement->base_salary) }}</div>
        </div>
    </div>
</div>

{{-- Effective Month --}}
<div class="mb-3">
    <label class="form-label fw-semibold">Effective Month <span class="text-danger">*</span></label>
    <input type="month" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror"
           value="{{ old('effective_from', $salaryIncrement->effective_from->format('Y-m')) }}" required>
    @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

{{-- Reason --}}
<div class="mb-3">
    <label class="form-label fw-semibold">Reason</label>
    <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror"
           maxlength="300" value="{{ old('reason', $salaryIncrement->reason) }}">
    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

{{-- Note --}}
<div class="mb-4">
    <label class="form-label fw-semibold">Internal Note</label>
    <textarea name="note" class="form-control @error('note') is-invalid @enderror"
              rows="2" maxlength="500">{{ old('note', $salaryIncrement->note) }}</textarea>
    @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i> Update
    </button>
    <a href="{{ route('salary-increments.show', $salaryIncrement) }}" class="btn btn-outline-secondary">Cancel</a>
</div>

</form>
</div>
</div>
</div>

@push('scripts')
<script>
let currentSalary = {{ (float) $currentSalary }};
let currentMode = '{{ $initialMode ?? 'amount' }}';

document.addEventListener('DOMContentLoaded', function() {
    setMode(currentMode, false);
    recalculate();
});

function setMode(mode, recalc = true) {
    currentMode = mode;
    document.getElementById('modeInput').value = mode;
    document.querySelectorAll('.mode-card').forEach(c => c.classList.remove('active'));
    document.querySelector(`.mode-card[data-mode="${mode}"]`)?.classList.add('active');
    document.getElementById('field_new_salary').style.display  = mode === 'new_salary'  ? '' : 'none';
    document.getElementById('field_amount').style.display      = mode === 'amount'       ? '' : 'none';
    document.getElementById('field_percentage').style.display  = mode === 'percentage'   ? '' : 'none';
    if (mode !== 'new_salary')   { document.getElementById('new_salary').value = ''; }
    if (mode !== 'amount')       { document.getElementById('increment_amount').value = ''; }
    if (mode !== 'percentage')   { document.getElementById('increment_percentage').value = ''; }
    if (recalc) recalculate();
}

function recalculate() {
    const prev = currentSalary;
    let newSal = NaN, delta = NaN, pct = NaN;
    if (currentMode === 'new_salary') {
        const v = parseFloat(document.getElementById('new_salary').value);
        if (!isNaN(v)) { newSal = v; delta = v - prev; pct = prev > 0 ? (delta/prev)*100 : 0; }
    } else if (currentMode === 'amount') {
        const v = parseFloat(document.getElementById('increment_amount').value);
        if (!isNaN(v)) { delta = v; newSal = prev + v; pct = prev > 0 ? (v/prev)*100 : 0; }
    } else if (currentMode === 'percentage') {
        const v = parseFloat(document.getElementById('increment_percentage').value);
        if (!isNaN(v)) { pct = v; delta = prev * (v/100); newSal = prev + delta; }
    }
    if (!isNaN(newSal)) {
        const dEl = document.getElementById('previewDelta');
        dEl.textContent = (delta >= 0 ? '+' : '') + formatCurrency(delta) + (pct !== NaN ? ' (' + (pct >= 0 ? '+' : '') + pct.toFixed(2) + '%)' : '');
        dEl.className = 'fw-bold ' + (delta >= 0 ? 'text-success' : 'text-danger');
        document.getElementById('previewNew').textContent = formatCurrency(newSal);
    }
}

function formatCurrency(n) {
    return '{{ setting('currency_symbol','৳') }}' + parseFloat(n).toLocaleString('en-US', {minimumFractionDigits:2,maximumFractionDigits:2});
}
</script>
@endpush

</x-app-layout>
