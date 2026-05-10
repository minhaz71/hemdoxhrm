<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    {{-- Stat Cards Row 1 --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f4fd;">
                    <i class="bi bi-people" style="color:#1a8fe3;"></i>
                </div>
                <div>
                    <div class="label">Total Employees</div>
                    <div class="value">0</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eaf7ee;">
                    <i class="bi bi-clock-history" style="color:#28a745;"></i>
                </div>
                <div>
                    <div class="label">Present Today</div>
                    <div class="value">0</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e6;">
                    <i class="bi bi-calendar-x" style="color:#ffc107;"></i>
                </div>
                <div>
                    <div class="label">On Leave Today</div>
                    <div class="value">0</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fdecea;">
                    <i class="bi bi-person-x" style="color:#dc3545;"></i>
                </div>
                <div>
                    <div class="label">Absent Today</div>
                    <div class="value">0</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat Cards Row 2 --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0ecff;">
                    <i class="bi bi-cash-stack" style="color:#6f42c1;"></i>
                </div>
                <div>
                    <div class="label">Payroll This Month</div>
                    <div class="value">{{ currency(0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e9f9f5;">
                    <i class="bi bi-receipt" style="color:#20c997;"></i>
                </div>
                <div>
                    <div class="label">Payslips Issued</div>
                    <div class="value">0</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff3e8;">
                    <i class="bi bi-hourglass-split" style="color:#fd7e14;"></i>
                </div>
                <div>
                    <div class="label">Pending Leaves</div>
                    <div class="value">0</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f4fd;">
                    <i class="bi bi-person-check" style="color:#1a8fe3;"></i>
                </div>
                <div>
                    <div class="label">New This Month</div>
                    <div class="value">0</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Panels --}}
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="hrms-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-2 text-primary"></i>Recent Attendance</span>
                    <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="p-4 text-center text-muted" style="font-size:.875rem;">
                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                    No attendance records yet.
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="hrms-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-check me-2 text-success"></i>Pending Leave Requests</span>
                    <a href="#" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="p-4 text-center text-muted" style="font-size:.875rem;">
                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                    No pending leave requests.
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="hrms-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people me-2 text-info"></i>Recent Employees</span>
                    <a href="#" class="btn btn-sm btn-outline-info">View All</a>
                </div>
                <div class="p-4 text-center text-muted" style="font-size:.875rem;">
                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                    No employees added yet.
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
