<x-app-layout>
    <x-slot name="title">Edit Attendance</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Edit Attendance Record</h5>
            <small class="text-muted">
                {{ $attendance->employee->full_name }} — {{ $attendance->date->format('M j, Y') }}
            </small>
        </div>
        <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="hrms-card p-4">
                <form method="POST" action="{{ route('attendance.update', $attendance) }}">
                    @csrf @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" class="form-control" value="{{ $attendance->employee->full_name }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" value="{{ $attendance->date->format('M j, Y') }}" disabled>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Check In <span class="text-danger">*</span></label>
                            <input type="time" name="check_in"
                                   class="form-control @error('check_in') is-invalid @enderror"
                                   value="{{ old('check_in', $attendance->check_in ? substr($attendance->check_in, 0, 5) : '') }}">
                            @error('check_in')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Check Out</label>
                            <input type="time" name="check_out"
                                   class="form-control @error('check_out') is-invalid @enderror"
                                   value="{{ old('check_out', substr($attendance->check_out ?? '', 0, 5)) }}">
                            @error('check_out')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <optgroup label="Worked">
                                <option value="present"    {{ old('status', $attendance->status) === 'present'    ? 'selected' : '' }}>Present</option>
                                <option value="late"       {{ old('status', $attendance->status) === 'late'       ? 'selected' : '' }}>Late</option>
                                <option value="underworked"{{ old('status', $attendance->status) === 'underworked' ? 'selected' : '' }}>Underworked</option>
                            </optgroup>
                            <optgroup label="Not Worked">
                                <option value="absent"     {{ old('status', $attendance->status) === 'absent'     ? 'selected' : '' }}>Absent</option>
                                <option value="holiday"    {{ old('status', $attendance->status) === 'holiday'    ? 'selected' : '' }}>Holiday</option>
                                <option value="weekly_off" {{ old('status', $attendance->status) === 'weekly_off' ? 'selected' : '' }}>Weekly Off</option>
                            </optgroup>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Note</label>
                        <input type="text" name="note"
                               class="form-control @error('note') is-invalid @enderror"
                               value="{{ old('note', $attendance->note) }}"
                               placeholder="Optional note">
                        @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check2 me-1"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
