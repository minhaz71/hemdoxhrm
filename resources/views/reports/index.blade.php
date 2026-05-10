<x-app-layout>
    <x-slot name="title">Reports</x-slot>

    <div class="mb-4">
        <h5 class="fw-bold mb-0">Reports</h5>
        <small class="text-muted">Select a report type to generate</small>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <a href="{{ route('reports.attendance') }}" class="text-decoration-none">
                <div class="hrms-card p-4 h-100 report-card">
                    <div class="report-icon mb-3" style="background:#e8f4fd;">
                        <i class="bi bi-clock-history" style="color:#1a8fe3;"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Attendance Report</h6>
                    <p class="text-muted mb-0" style="font-size:.875rem;">
                        Monthly attendance summary — present, absent, late days per employee with attendance rate.
                    </p>
                    <div class="mt-3">
                        <span class="badge bg-primary-subtle text-primary">By Month</span>
                        <span class="badge bg-secondary-subtle text-secondary ms-1">By Employee</span>
                        <span class="badge bg-secondary-subtle text-secondary ms-1">By Department</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ route('reports.salary') }}" class="text-decoration-none">
                <div class="hrms-card p-4 h-100 report-card">
                    <div class="report-icon mb-3" style="background:#eaf7ee;">
                        <i class="bi bi-cash-stack" style="color:#28a745;"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Salary Report</h6>
                    <p class="text-muted mb-0" style="font-size:.875rem;">
                        Monthly payroll breakdown — gross, deductions, net salary with totals.
                    </p>
                    <div class="mt-3">
                        <span class="badge bg-success-subtle text-success">By Period</span>
                        <span class="badge bg-secondary-subtle text-secondary ms-1">By Status</span>
                        <span class="badge bg-secondary-subtle text-secondary ms-1">Department</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ route('reports.leave') }}" class="text-decoration-none">
                <div class="hrms-card p-4 h-100 report-card">
                    <div class="report-icon mb-3" style="background:#fff8e6;">
                        <i class="bi bi-calendar-check" style="color:#ffc107;"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Leave Report</h6>
                    <p class="text-muted mb-0" style="font-size:.875rem;">
                        Leave applications by type, status, and employee — with approval breakdown.
                    </p>
                    <div class="mt-3">
                        <span class="badge bg-warning-subtle text-warning">By Type</span>
                        <span class="badge bg-secondary-subtle text-secondary ms-1">By Status</span>
                        <span class="badge bg-secondary-subtle text-secondary ms-1">Annual</span>
                    </div>
                </div>
            </a>
        </div>
    </div>
</x-app-layout>

<style>
.report-card { transition: box-shadow .15s, transform .15s; cursor: pointer; }
.report-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.08); transform: translateY(-2px); }
.report-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
}
</style>
