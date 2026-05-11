<x-app-layout>
    <x-slot name="title">Import Holidays</x-slot>
    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Holiday CSV Import</h5>
            <small class="text-muted">Upload company, branch, department, or employee-specific holidays</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('holidays.import.sample') }}" class="btn btn-outline-primary">
                <i class="bi bi-download me-1"></i> Download Sample CSV
            </a>
            <a href="{{ route('holidays.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="hrms-card p-4">
                <form method="POST" action="{{ route('holidays.import.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Holiday CSV File</label>
                        <input type="file" name="csv_file" class="form-control @error('csv_file') is-invalid @enderror" accept=".csv,text/csv,text/plain" required>
                        @error('csv_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="small text-muted mb-4">
                        Required columns: <code>title,reason,holiday_year,start_date,end_date,type,branch_id,department_id,employee_emails,notify_before_days,status</code>.
                        Dates must use <code>YYYY-MM-DD</code>. Employee-specific rows accept comma-separated emails.
                    </div>

                    <button class="btn btn-primary w-100">
                        <i class="bi bi-upload me-1"></i> Import Holidays
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="hrms-card p-4">
                <h6 class="fw-bold mb-3">Current Reference Data</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold mb-2">Branches</div>
                            <div class="table-responsive" style="max-height: 220px;">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>ID</th><th>Name</th><th>Code</th></tr></thead>
                                    <tbody>
                                        @forelse($branches as $branch)
                                            <tr><td>{{ $branch->id }}</td><td>{{ $branch->name }}</td><td>{{ $branch->code ?? '—' }}</td></tr>
                                        @empty
                                            <tr><td colspan="3" class="text-muted">No active branches.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold mb-2">Departments</div>
                            <div class="table-responsive" style="max-height: 220px;">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>ID</th><th>Name</th><th>Branch</th></tr></thead>
                                    <tbody>
                                        @forelse($departments as $department)
                                            <tr><td>{{ $department->id }}</td><td>{{ $department->name }}</td><td>{{ $department->branch?->name ?? '—' }}</td></tr>
                                        @empty
                                            <tr><td colspan="3" class="text-muted">No active departments.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="fw-semibold mb-2">Employee Emails</div>
                            <div class="table-responsive" style="max-height: 240px;">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>ID</th><th>Employee</th><th>Employee Code</th><th>Email</th></tr></thead>
                                    <tbody>
                                        @forelse($employees as $employee)
                                            <tr>
                                                <td>{{ $employee->id }}</td>
                                                <td>{{ $employee->full_name }}</td>
                                                <td>{{ $employee->organization_employee_code ?? $employee->employee_code }}</td>
                                                <td>{{ $employee->user?->email ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-muted">No active employees with emails.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-2">Showing up to 200 employees. The sample CSV includes currently available IDs and employee emails.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($report)
        <div class="hrms-card mt-4">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0">Import Report</h6>
                    <small class="text-muted">{{ $report['file_name'] ?? 'CSV file' }} · {{ $report['imported_at'] ?? '' }}</small>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-success-subtle text-success">{{ $report['created'] }} created</span>
                    <span class="badge bg-danger-subtle text-danger">{{ $report['failed'] }} failed</span>
                </div>
            </div>

            @if(! empty($report['failed_rows']))
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 90px;">Row</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Error Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['failed_rows'] as $row)
                                <tr>
                                    <td>{{ $row['row'] }}</td>
                                    <td>{{ $row['title'] ?: '—' }}</td>
                                    <td>{{ $row['type'] ?: '—' }}</td>
                                    <td>
                                        @foreach($row['errors'] as $error)
                                            <div class="text-danger">{{ $error }}</div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(! empty($report['created_rows']))
                <div class="p-4">
                    <div class="fw-semibold mb-2">Created Holidays</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Row</th><th>Title</th><th>Type</th><th>Dates</th></tr></thead>
                            <tbody>
                                @foreach($report['created_rows'] as $row)
                                    <tr>
                                        <td>{{ $row['row'] }}</td>
                                        <td>{{ $row['title'] }}</td>
                                        <td>{{ str_replace('_', ' ', $row['type']) }}</td>
                                        <td>{{ $row['dates'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-app-layout>
