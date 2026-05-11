<x-app-layout>
<x-slot name="title">New Salary Increment</x-slot>
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
                <li class="breadcrumb-item active">New</li>
            </ol>
        </nav>
        <h5 class="fw-bold mb-0">New Salary Increment</h5>
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

<form method="POST" action="{{ route('salary-increments.store') }}" id="incrementForm">
@csrf

{{-- Employee --}}
<div class="mb-4">
    <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
    <select name="employee_id" id="employeeSelect" class="form-select @error('employee_id') is-invalid @enderror" required>
        <option value="">— Select Employee —</option>
        @foreach($employees as $emp)
        <option value="{{ $emp->id }}"
                data-salary="{{ $salarMap[$emp->id] ?? 0 }}"
                {{ old('employee_id', $selectedEmployeeId) == $emp->id ? 'selected' : '' }}>
            {{ $emp->full_name }} ({{ $emp->employee_code }})
        </option>
        @endforeach
    </select>
    @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

{{-- Current Salary Display --}}
<div id="currentSalaryDisplay" class="alert alert-info py-2 mb-4 d-none">
    <i class="bi bi-info-circle me-1"></i>
    Current salary: <strong id="currentSalaryValue">—</strong>
</div>

{{-- Type --}}
<div class="mb-4">
    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
    <div class="d-flex gap-3">
        @foreach(['increment' => ['success','graph-up-arrow','Increment'], 'decrement' => ['danger','graph-down-arrow','Decrement'], 'adjustment' => ['warning','sliders','Adjustment']] as $val => [$color, $icon, $label])
        <div class="form-check">
            <input class="form-check-input" type="radio" name="salary_type" id="type_{{ $val }}"
                   value="{{ $val }}" {{ old('salary_type', 'increment') === $val ? 'checked' : '' }}>
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
<div class="mb-4">
    <label class="form-label fw-semibold">Input Mode <span class="text-danger">*</span></label>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="mode-card {{ old('_mode','amount') === 'new_salary' ? 'active' : '' }}" data-mode="new_salary" onclick="setMode('new_salary')">
                <div class="fw-semibold small mb-1"><i class="bi bi-currency-dollar me-1"></i>New Salary</div>
                <div class="text-muted" style="font-size:.78rem;">Enter the new total salary</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mode-card {{ old('_mode','amount') === 'amount' ? 'active' : 'active' }}" data-mode="amount" onclick="setMode('amount')">
                <div class="fw-semibold small mb-1"><i class="bi bi-plus-slash-minus me-1"></i>Amount</div>
                <div class="text-muted" style="font-size:.78rem;">Enter increment / decrement amount</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mode-card {{ old('_mode','amount') === 'percentage' ? 'active' : '' }}" data-mode="percentage" onclick="setMode('percentage')">
                <div class="fw-semibold small mb-1"><i class="bi bi-percent me-1"></i>Percentage</div>
                <div class="text-muted" style="font-size:.78rem;">Enter as a percentage</div>
            </div>
        </div>
    </div>
    <input type="hidden" name="_mode" id="modeInput" value="{{ old('_mode','amount') }}">
</div>

{{-- Input fields --}}
<div class="row g-3 mb-4">
    <div class="col-md-6" id="field_new_salary" style="display:none;">
        <label class="form-label fw-semibold small">New Salary</label>
        <div class="input-group">
            <span class="input-group-text">{{ setting('currency_symbol','৳') }}</span>
            <input type="number" name="new_salary" id="new_salary" step="0.01" min="0"
                   class="form-control @error('new_salary') is-invalid @enderror"
                   placeholder="e.g. 55000" value="{{ old('new_salary') }}"
                   oninput="recalculate()">
            @error('new_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6" id="field_amount">
        <label class="form-label fw-semibold small">Increment / Decrement Amount</label>
        <div class="input-group">
            <span class="input-group-text">{{ setting('currency_symbol','৳') }}</span>
            <input type="number" name="increment_amount" id="increment_amount" step="0.01"
                   class="form-control @error('increment_amount') is-invalid @enderror"
                   placeholder="e.g. 5000 or -3000" value="{{ old('increment_amount') }}"
                   oninput="recalculate()">
            @error('increment_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6" id="field_percentage" style="display:none;">
        <label class="form-label fw-semibold small">Increment Percentage</label>
        <div class="input-group">
            <input type="number" name="increment_percentage" id="increment_percentage" step="0.01"
                   class="form-control @error('increment_percentage') is-invalid @enderror"
                   placeholder="e.g. 10 or -5" value="{{ old('increment_percentage') }}"
                   oninput="recalculate()">
            <span class="input-group-text">%</span>
            @error('increment_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- Live preview --}}
<div class="calc-preview mb-4" id="calcPreview" style="display:none;">
    <div class="row g-3 text-center">
        <div class="col-4">
            <div class="text-muted small">Previous Salary</div>
            <div class="fw-bold" id="previewPrev">—</div>
        </div>
        <div class="col-4">
            <div class="text-muted small">Change</div>
            <div class="fw-bold" id="previewDelta">—</div>
        </div>
        <div class="col-4">
            <div class="text-muted small">New Salary</div>
            <div class="fw-bold text-success fs-5" id="previewNew">—</div>
        </div>
    </div>
</div>

{{-- Effective Month --}}
<div class="mb-3">
    <label class="form-label fw-semibold">Effective Month <span class="text-danger">*</span></label>
    <input type="month" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror"
           value="{{ old('effective_from', date('Y-m')) }}" required>
    @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

{{-- Reason --}}
<div class="mb-3">
    <label class="form-label fw-semibold">Reason</label>
    <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror"
           maxlength="300" placeholder="e.g. Annual review, Performance bonus…"
           value="{{ old('reason') }}">
    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

{{-- Note --}}
<div class="mb-4">
    <label class="form-label fw-semibold">Internal Note</label>
    <textarea name="note" class="form-control @error('note') is-invalid @enderror"
              rows="2" maxlength="500" placeholder="Internal notes (not shown to employee)">{{ old('note') }}</textarea>
    @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        @if(auth()->user()->isAdmin())
        <i class="bi bi-check-circle me-1"></i> Save & Approve
        @else
        <i class="bi bi-send me-1"></i> Submit for Approval
        @endif
    </button>
    <a href="{{ route('salary-increments.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

</form>
</div>
</div>
</div>

@push('scripts')
<script>
const salaryMap = @json($salarMap);
let currentMode = '{{ old('_mode','amount') }}';
let currentSalary = 0;

document.addEventListener('DOMContentLoaded', function() {
    initMode();
    const sel = document.getElementById('employeeSelect');
    updateCurrentSalary(sel.value);
    sel.addEventListener('change', function() {
        updateCurrentSalary(this.value);
        recalculate();
    });
    // init mode cards display
    setMode(currentMode, false);
});

function updateCurrentSalary(empId) {
    const disp = document.getElementById('currentSalaryDisplay');
    const val = document.getElementById('currentSalaryValue');
    if (empId && salaryMap[empId] !== undefined) {
        currentSalary = parseFloat(salaryMap[empId]) || 0;
        val.textContent = formatCurrency(currentSalary);
        disp.classList.remove('d-none');
    } else {
        currentSalary = 0;
        disp.classList.add('d-none');
    }
    recalculate();
}

function setMode(mode, recalc = true) {
    currentMode = mode;
    document.getElementById('modeInput').value = mode;

    document.querySelectorAll('.mode-card').forEach(c => c.classList.remove('active'));
    document.querySelector(`.mode-card[data-mode="${mode}"]`)?.classList.add('active');

    document.getElementById('field_new_salary').style.display  = mode === 'new_salary'  ? '' : 'none';
    document.getElementById('field_amount').style.display      = mode === 'amount'       ? '' : 'none';
    document.getElementById('field_percentage').style.display  = mode === 'percentage'   ? '' : 'none';

    // clear unused fields so they don't interfere with validation
    if (mode !== 'new_salary')   { document.getElementById('new_salary').value = ''; }
    if (mode !== 'amount')       { document.getElementById('increment_amount').value = ''; }
    if (mode !== 'percentage')   { document.getElementById('increment_percentage').value = ''; }

    if (recalc) recalculate();
}

function initMode() {
    setMode(currentMode, false);
}

function recalculate() {
    const preview = document.getElementById('calcPreview');
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

    if (!isNaN(newSal) && prev > 0) {
        preview.style.display = '';
        document.getElementById('previewPrev').textContent = formatCurrency(prev);
        const dEl = document.getElementById('previewDelta');
        dEl.textContent = (delta >= 0 ? '+' : '') + formatCurrency(delta) + ' (' + (pct >= 0 ? '+' : '') + pct.toFixed(2) + '%)';
        dEl.className = 'fw-bold ' + (delta >= 0 ? 'text-success' : 'text-danger');
        document.getElementById('previewNew').textContent = formatCurrency(newSal);
    } else {
        preview.style.display = 'none';
    }
}

function formatCurrency(n) {
    return '{{ setting('currency_symbol','৳') }}' + parseFloat(n).toLocaleString('en-US', {minimumFractionDigits:2,maximumFractionDigits:2});
}
</script>
@endpush

</x-app-layout>
