<x-app-layout>
    <x-slot name="title">Apply Leave</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Apply for Leave</h5>
            <small class="text-muted">Submit a new leave application</small>
        </div>
        <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('leaves.store') }}" id="leaveForm">
                @csrf

                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Application Details
                    </h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Employee <span class="text-danger">*</span></label>
                            @if($isSelfService)
                                <input type="hidden" name="employee_id" id="employeeSelect" value="{{ $employee->id }}">
                                <div class="form-control bg-light">
                                    {{ $employee->full_name }} — {{ $employee->employee_code }}
                                </div>
                            @else
                            <select name="employee_id" id="employeeSelect"
                                    class="form-select @error('employee_id') is-invalid @enderror" required>
                                <option value="">— Select Employee —</option>
                                @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->first_name }} {{ $emp->last_name }}
                                </option>
                                @endforeach
                            </select>
                            @endif
                            @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                            <select name="leave_type_id" id="leaveTypeSelect"
                                    class="form-select @error('leave_type_id') is-invalid @enderror" required>
                                <option value="">— Select Type —</option>
                                @foreach ($leaveTypes as $type)
                                <option value="{{ $type->id }}"
                                        data-allowed="{{ $type->days_allowed }}"
                                        data-paid="{{ $type->is_paid ? 1 : 0 }}"
                                        {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                    @if ($type->days_allowed > 0)
                                        ({{ $type->days_allowed }} days/yr)
                                    @else
                                        (unlimited)
                                    @endif
                                </option>
                                @endforeach
                            </select>
                            @error('leave_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="startDate"
                                   class="form-control @error('start_date') is-invalid @enderror"
                                   value="{{ old('start_date') }}"
                                   min="{{ today()->toDateString() }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="endDate"
                                   class="form-control @error('end_date') is-invalid @enderror"
                                   value="{{ old('end_date') }}"
                                   min="{{ today()->toDateString() }}" required>
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Live day counter --}}
                        <div class="col-12" id="dayCounterWrap" style="display:none;">
                            <div class="alert alert-info py-2 mb-0" id="dayCounter"></div>
                        </div>

                        {{-- Unpaid warning --}}
                        <div class="col-12" id="unpaidWarning" style="display:none;">
                            <div class="alert alert-warning py-2 mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Limit exceeded.</strong> This leave will be treated as <strong>Unpaid Leave</strong>.
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" rows="3"
                                      class="form-control @error('reason') is-invalid @enderror"
                                      placeholder="Optional — briefly describe the reason for leave">{{ old('reason') }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="hrms-card p-4">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-send me-1"></i> Submit Application
                    </button>
                    <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                </div>
            </form>
        </div>

        {{-- Balance Panel --}}
        <div class="col-lg-4">
            <div class="hrms-card p-4" id="balancePanel" style="display:none;">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                    Leave Balance {{ now()->year }}
                </h6>
                <div id="balanceList"></div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
function countWorkingDays(start, end) {
    let count = 0, cur = new Date(start);
    const endD = new Date(end);
    while (cur <= endD) {
        const d = cur.getDay();
        if (d !== 0 && d !== 6) count++;
        cur.setDate(cur.getDate() + 1);
    }
    return Math.max(1, count);
}

function refreshDayCounter() {
    const s = document.getElementById('startDate').value;
    const e = document.getElementById('endDate').value;
    const wrap = document.getElementById('dayCounterWrap');
    const counter = document.getElementById('dayCounter');
    const unpaidWarn = document.getElementById('unpaidWarning');

    if (!s || !e || e < s) { wrap.style.display = 'none'; return; }

    const days = countWorkingDays(s, e);
    counter.textContent = `Working days: ${days}`;
    wrap.style.display = '';

    // Check limit
    const sel = document.getElementById('leaveTypeSelect');
    const opt = sel.options[sel.selectedIndex];
    const allowed = parseInt(opt?.dataset.allowed ?? 0);

    unpaidWarn.style.display = (allowed > 0 && days > allowed) ? '' : 'none';
}

document.getElementById('startDate').addEventListener('change', function () {
    const endEl = document.getElementById('endDate');
    if (!endEl.value || endEl.value < this.value) endEl.value = this.value;
    refreshDayCounter();
});
document.getElementById('endDate').addEventListener('change', refreshDayCounter);
document.getElementById('leaveTypeSelect').addEventListener('change', refreshDayCounter);

// Load balance when employee selected
function loadLeaveBalance(empId) {
    const panel = document.getElementById('balancePanel');
    if (!empId) { panel.style.display = 'none'; return; }

    fetch(`/leaves/balance?employee_id=${empId}`)
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('balanceList');
            list.innerHTML = data.balance.map(b => `
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <div>
                        <div class="fw-semibold" style="font-size:.85rem;">${b.type}</div>
                        <small class="text-muted">${b.is_paid ? 'Paid' : 'Unpaid'}</small>
                    </div>
                    <div class="text-end">
                        ${b.allowed > 0
                            ? `<span class="fw-bold ${b.remaining === 0 ? 'text-danger' : 'text-success'}">${b.remaining}</span>
                               <small class="text-muted"> / ${b.allowed}</small>`
                            : `<span class="text-muted">Unlimited</span>`
                        }
                    </div>
                </div>`).join('');
            panel.style.display = '';
        });
}

document.getElementById('employeeSelect').addEventListener('change', function () {
    loadLeaveBalance(this.value);
});

@if($isSelfService)
loadLeaveBalance({{ $employee->id }});
@endif
</script>
@endpush
