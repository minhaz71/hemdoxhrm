@csrf
@if(isset($holiday))
    @method('PATCH')
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="hrms-card p-4">
            <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">Holiday Details</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Holiday Year <span class="text-danger">*</span></label>
                    <input type="number" name="holiday_year" min="2000" max="2100" class="form-control @error('holiday_year') is-invalid @enderror" value="{{ old('holiday_year', $holiday->holiday_year ?? now()->year) }}" required>
                    @error('holiday_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $holiday->title ?? '') }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', isset($holiday) ? $holiday->start_date->format('Y-m-d') : '') }}" required>
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">End Date <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', isset($holiday) ? $holiday->end_date->format('Y-m-d') : '') }}" required>
                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3">{{ old('reason', $holiday->reason ?? '') }}</textarea>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="hrms-card p-4 mt-4">
            <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">Applicability</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Holiday Type <span class="text-danger">*</span></label>
                    @php $type = old('type', $holiday->type ?? 'global'); @endphp
                    <select name="type" id="holidayType" class="form-select" onchange="toggleHolidayTargets()">
                        <option value="global" @selected($type === 'global')>Global company holiday</option>
                        <option value="branch" @selected($type === 'branch')>Branch holiday</option>
                        <option value="department" @selected($type === 'department')>Department holiday</option>
                        <option value="employee_specific" @selected($type === 'employee_specific')>Employee-specific holiday</option>
                    </select>
                </div>
                <div class="col-md-6 target target-branch">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">Select branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id', $holiday->branch_id ?? null) == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 target target-department">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">Select department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id', $holiday->department_id ?? null) == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 target target-employee_specific">
                    <label class="form-label">Employees</label>
                    @php $selectedEmployees = collect(old('employee_ids', isset($holiday) ? $holiday->employees->pluck('id')->all() : []))->map(fn($id)=>(int)$id)->all(); @endphp
                    <select name="employee_ids[]" class="form-select" multiple size="8">
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(in_array($employee->id, $selectedEmployees, true))>{{ $employee->full_name }} - {{ $employee->employee_code }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Hold Cmd/Ctrl to select multiple employees.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="hrms-card p-4">
            <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">Notifications</h6>
            <label class="form-label">Notify Before Days</label>
            <input type="number" name="notify_before_days" min="0" max="30" class="form-control mb-3" value="{{ old('notify_before_days', $holiday->notify_before_days ?? 1) }}">
            <label class="form-label">Status</label>
            <select name="status" class="form-select mb-4">
                <option value="active" @selected(old('status', $holiday->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $holiday->status ?? 'active') === 'inactive')>Inactive</option>
            </select>
            <button class="btn btn-primary w-100 mb-2">{{ isset($holiday) ? 'Save Changes' : 'Create Holiday' }}</button>
            <a href="{{ route('holidays.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
        </div>
    </div>
</div>

<script>
function toggleHolidayTargets() {
    const type = document.getElementById('holidayType').value;
    document.querySelectorAll('.target').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.target-' + type).forEach(el => el.style.display = '');
}
toggleHolidayTargets();
</script>
