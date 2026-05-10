@csrf
@if(isset($weeklyOff))
    @method('PATCH')
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="hrms-card p-4">
            <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">Weekly Off Details</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                        <option value="">Select employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('employee_id', $weeklyOff->employee_id ?? null) == $employee->id)>
                                {{ $employee->full_name }} - {{ $employee->employee_code }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Day of Week <span class="text-danger">*</span></label>
                    <select name="day_of_week" class="form-select @error('day_of_week') is-invalid @enderror" required>
                        @foreach($days as $day)
                            <option value="{{ $day }}" @selected(old('day_of_week', $weeklyOff->day_of_week ?? '') === $day)>{{ ucfirst($day) }}</option>
                        @endforeach
                    </select>
                    @error('day_of_week')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Effective From <span class="text-danger">*</span></label>
                    <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', isset($weeklyOff) ? $weeklyOff->effective_from->format('Y-m-d') : now()->toDateString()) }}" required>
                    @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Effective To</label>
                    <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to', isset($weeklyOff) ? $weeklyOff->effective_to?->format('Y-m-d') : '') }}">
                    @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   @selected(old('status', $weeklyOff->status ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $weeklyOff->status ?? 'active') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Note <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea name="note"
                              rows="2"
                              class="form-control @error('note') is-invalid @enderror"
                              placeholder="e.g. Approved per employee request, Friday religious observance…"
                              maxlength="500">{{ old('note', $weeklyOff->note ?? '') }}</textarea>
                    @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hrms-card p-4">
            <button class="btn btn-primary w-100 mb-2">{{ isset($weeklyOff) ? 'Save Changes' : 'Assign Weekly Off' }}</button>
            <a href="{{ route('weekly-offs.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
        </div>
    </div>
</div>
