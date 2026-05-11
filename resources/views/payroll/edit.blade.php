<x-app-layout>
    <x-slot name="title">Adjust Payroll</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Adjust Payroll</h5>
            <small class="text-muted">{{ $payroll->employee->full_name }} — {{ $payroll->month_label }}</small>
        </div>
        <a href="{{ route('payroll.show', $payroll) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">

            {{-- Read-only deductions info --}}
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle me-2"></i>
                HR/Admin can adjust management working days, late penalty, and leave penalty before payment.
                @if($overtimeEnabled)
                    Overtime is available because it is enabled in Payroll Settings.
                @endif
            </div>

            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                    Current Deductions (read-only)
                </h6>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">
                        Late ({{ $payroll->late_days }} days{{ $payroll->late_penalty_enabled ? ' × '.currency($payroll->late_penalty_amount) : ' · penalty off' }})
                    </span>
                    <span class="text-danger">{{ currency(-$payroll->late_deduction) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Absent ({{ $payroll->absent_days }} days)</span>
                    <span class="text-danger">{{ currency(-$payroll->absent_deduction) }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Unpaid Leave ({{ $payroll->unpaid_leave_days }} days)</span>
                    <span class="text-danger">{{ currency(-$payroll->leave_deduction) }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('payroll.update', $payroll) }}">
                @csrf @method('PATCH')

                <div class="hrms-card p-4 mb-4">
                    <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Earnings Adjustments
                    </h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Management Working Days</label>
                            <input type="number" name="management_working_days" min="0" max="31"
                                   class="form-control @error('management_working_days') is-invalid @enderror"
                                   value="{{ old('management_working_days', $payroll->management_working_days ?? $payroll->working_days) }}">
                            <div class="form-text">Calendar working days: {{ $payroll->calendar_working_days ?? $payroll->working_days }}.</div>
                            @error('management_working_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bonus</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="bonus" step="0.01" min="0"
                                       class="form-control @error('bonus') is-invalid @enderror"
                                       value="{{ old('bonus', $payroll->bonus) }}">
                                @error('bonus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Incentive</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="incentive" step="0.01" min="0"
                                       class="form-control @error('incentive') is-invalid @enderror"
                                       value="{{ old('incentive', $payroll->incentive) }}">
                                @error('incentive')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        @if($overtimeEnabled)
                        <div class="col-12">
                            <label class="form-label">Overtime</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="overtime_amount" step="0.01" min="0"
                                       class="form-control @error('overtime_amount') is-invalid @enderror"
                                       value="{{ old('overtime_amount', $payroll->overtime_amount) }}">
                                @error('overtime_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        @endif
                        <div class="col-12">
                            <label class="form-label">Late Penalty</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="late_deduction" step="0.01" min="0"
                                       class="form-control @error('late_deduction') is-invalid @enderror"
                                       value="{{ old('late_deduction', $payroll->late_deduction) }}">
                                @error('late_deduction')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Leave Penalty</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="leave_deduction" step="0.01" min="0"
                                       class="form-control @error('leave_deduction') is-invalid @enderror"
                                       value="{{ old('leave_deduction', $payroll->leave_deduction) }}">
                                @error('leave_deduction')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Note</label>
                            <input type="text" name="note"
                                   class="form-control @error('note') is-invalid @enderror"
                                   value="{{ old('note', $payroll->note) }}"
                                   placeholder="Reason for adjustment">
                            @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="hrms-card p-4">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-check2 me-1"></i> Save Adjustments
                    </button>
                    <a href="{{ route('payroll.show', $payroll) }}" class="btn btn-outline-secondary w-100">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
