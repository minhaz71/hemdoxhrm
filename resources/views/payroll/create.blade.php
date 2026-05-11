<x-app-layout>
    <x-slot name="title">Generate Payroll</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Generate Payroll</h5>
            <small class="text-muted">Leave employee blank to generate for all active employees</small>
        </div>
        <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('payroll.store') }}">
                @csrf

                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Pay Period
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Month <span class="text-danger">*</span></label>
                            <select name="month" class="form-select @error('month') is-invalid @enderror" required>
                                @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}" {{ (old('month', now()->month) == $m) ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}
                                </option>
                                @endforeach
                            </select>
                            @error('month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year <span class="text-danger">*</span></label>
                            <select name="year" class="form-select @error('year') is-invalid @enderror" required>
                                @foreach (range(now()->year, now()->year - 2, -1) as $y)
                                <option value="{{ $y }}" {{ old('year', now()->year) == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                                @endforeach
                            </select>
                            @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror"
                                    id="employeeSelect">
                                <option value="">— All Active Employees (Bulk) —</option>
                                @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}"
                                        data-salary="{{ $emp->base_salary }}"
                                        {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->first_name }} {{ $emp->last_name }}
                                    ({{ currency($emp->base_salary) }})
                                </option>
                                @endforeach
                            </select>
                            @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Leave blank to generate payroll for all active employees.</small>
                        </div>
                    </div>
                </div>

                <div class="hrms-card p-4 mb-4" id="adjustPanel">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Earnings Adjustments <span class="badge bg-secondary fw-normal ms-1">Optional</span>
                    </h6>
                    <small class="text-muted d-block mb-3">
                        HR/Admin may set management working days and adjust penalties before generation. If bulk, the working-day value applies to all generated employees.
                    </small>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Management Working Days</label>
                            <input type="number" name="management_working_days" min="0" max="31"
                                   class="form-control @error('management_working_days') is-invalid @enderror"
                                   value="{{ old('management_working_days') }}" placeholder="Auto">
                            <div class="form-text">Overrides calendar working days for this payroll run.</div>
                            @error('management_working_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bonus</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="bonus" step="0.01" min="0"
                                       class="form-control @error('bonus') is-invalid @enderror"
                                       value="{{ old('bonus', '0.00') }}">
                            </div>
                            @error('bonus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Incentive</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="incentive" step="0.01" min="0"
                                       class="form-control @error('incentive') is-invalid @enderror"
                                       value="{{ old('incentive', '0.00') }}">
                            </div>
                            @error('incentive')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @if($overtimeEnabled)
                        <div class="col-md-4">
                            <label class="form-label">Overtime</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="overtime_amount" step="0.01" min="0"
                                       class="form-control @error('overtime_amount') is-invalid @enderror"
                                       value="{{ old('overtime_amount', '0.00') }}">
                            </div>
                            @error('overtime_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">Late Penalty Override</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="late_deduction" step="0.01" min="0"
                                       class="form-control @error('late_deduction') is-invalid @enderror"
                                       value="{{ old('late_deduction') }}" placeholder="Auto">
                            </div>
                            @error('late_deduction')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Leave Penalty Override</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="leave_deduction" step="0.01" min="0"
                                       class="form-control @error('leave_deduction') is-invalid @enderror"
                                       value="{{ old('leave_deduction') }}" placeholder="Auto">
                            </div>
                            @error('leave_deduction')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Note</label>
                            <input type="text" name="note"
                                   class="form-control @error('note') is-invalid @enderror"
                                   value="{{ old('note') }}" placeholder="Optional note">
                            @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="hrms-card p-4">
                    <button type="submit" class="btn btn-primary w-100 mb-2" id="submitBtn">
                        <i class="bi bi-gear me-1"></i> Generate Payroll
                    </button>
                    <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
document.getElementById('employeeSelect').addEventListener('change', function () {
    const btn = document.getElementById('submitBtn');
    const isBulk = !this.value;
    btn.innerHTML = isBulk
        ? '<i class="bi bi-gear me-1"></i> Generate Bulk Payroll (All Employees)'
        : '<i class="bi bi-gear me-1"></i> Generate Payroll';
});
</script>
@endpush
