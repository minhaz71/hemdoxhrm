<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 11px;
        color: #1a1f2e;
        background: #fff;
    }

    /* ── Header ── */
    .header {
        background: #1a1f2e;
        color: #fff;
        padding: 22px 30px;
        margin-bottom: 0;
    }
    .header-inner {
        display: table;
        width: 100%;
    }
    .header-left  { display: table-cell; vertical-align: middle; }
    .header-right { display: table-cell; vertical-align: middle; text-align: right; }
    .company-name { font-size: 20px; font-weight: bold; letter-spacing: 0.5px; }
    .company-sub  { font-size: 10px; color: #94a3b8; margin-top: 2px; }
    .payslip-title { font-size: 13px; font-weight: bold; color: #4e9af1; }
    .payslip-period { font-size: 10px; color: #94a3b8; margin-top: 3px; }

    /* ── Employee info band ── */
    .emp-band {
        background: #f4f6f9;
        border-bottom: 2px solid #e9ecef;
        padding: 14px 30px;
        display: table;
        width: 100%;
    }
    .emp-col { display: table-cell; width: 33.3%; }
    .emp-label { font-size: 9px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.05em; }
    .emp-value { font-size: 11px; font-weight: bold; color: #1a1f2e; margin-top: 2px; }

    /* ── Body ── */
    .body { padding: 20px 30px; }

    /* ── Section title ── */
    .section-title {
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6c757d;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 5px;
        margin-bottom: 8px;
        margin-top: 16px;
    }

    /* ── Two-column layout ── */
    .cols { display: table; width: 100%; }
    .col  { display: table-cell; width: 50%; vertical-align: top; padding-right: 20px; }
    .col:last-child { padding-right: 0; padding-left: 20px; border-left: 1px solid #e9ecef; }

    /* ── Salary rows ── */
    .row {
        display: table;
        width: 100%;
        padding: 5px 0;
        border-bottom: 1px dashed #f0f0f0;
    }
    .row-label { display: table-cell; color: #6c757d; }
    .row-value { display: table-cell; text-align: right; font-weight: 600; }
    .row-value.green { color: #28a745; }
    .row-value.red   { color: #dc3545; }

    /* ── Totals ── */
    .total-row {
        display: table;
        width: 100%;
        padding: 7px 0;
        border-top: 2px solid #1a1f2e;
        margin-top: 4px;
    }
    .total-label { display: table-cell; font-weight: bold; font-size: 12px; }
    .total-value { display: table-cell; text-align: right; font-weight: bold; font-size: 14px; color: #1a1f2e; }

    /* ── Attendance summary ── */
    .att-grid { display: table; width: 100%; margin-top: 8px; }
    .att-cell {
        display: table-cell;
        text-align: center;
        padding: 8px 4px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
    }
    .att-num   { font-size: 16px; font-weight: bold; }
    .att-label { font-size: 9px; color: #6c757d; margin-top: 2px; }

    /* ── Net salary block ── */
    .net-block {
        background: #1a1f2e;
        color: #fff;
        padding: 16px 30px;
        margin-top: 20px;
        display: table;
        width: 100%;
    }
    .net-left  { display: table-cell; vertical-align: middle; }
    .net-right { display: table-cell; vertical-align: middle; text-align: right; }
    .net-label { font-size: 11px; color: #94a3b8; }
    .net-value { font-size: 22px; font-weight: bold; color: #4e9af1; }

    /* ── Footer ── */
    .footer {
        padding: 12px 30px;
        border-top: 1px solid #e9ecef;
        display: table;
        width: 100%;
        color: #6c757d;
        font-size: 9px;
    }
    .footer-left  { display: table-cell; }
    .footer-right { display: table-cell; text-align: right; }

    .status-badge {
        display: inline-block;
        background: #d4edda;
        color: #155724;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
    }
</style>
</head>
<body>

{{-- ── Header ── --}}
<div class="header">
    <div class="header-inner">
        <div class="header-left">
            <div class="company-name">Hemdox HRMS</div>
            <div class="company-sub">by Hemdox Digital · hemdox.com</div>
        </div>
        <div class="header-right">
            <div class="payslip-title">PAY SLIP</div>
            <div class="payslip-period">{{ $payroll->month_label }}</div>
            <div style="margin-top:6px;">
                <span class="status-badge">{{ strtoupper($payroll->status) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Employee band ── --}}
<div class="emp-band">
    <div class="emp-col">
        <div class="emp-label">Employee Name</div>
        <div class="emp-value">{{ $payroll->employee->full_name }}</div>
    </div>
    <div class="emp-col">
        <div class="emp-label">Employee Code</div>
        <div class="emp-value">{{ $payroll->employee->employee_code }}</div>
    </div>
    <div class="emp-col">
        <div class="emp-label">Department</div>
        <div class="emp-value">{{ $payroll->employee->department }}</div>
    </div>
    <div class="emp-col" style="display:table-cell; padding-top:8px;">
        <div class="emp-label">Designation</div>
        <div class="emp-value">{{ $payroll->employee->designation }}</div>
    </div>
    <div class="emp-col" style="display:table-cell; padding-top:8px;">
        <div class="emp-label">Payment Date</div>
        <div class="emp-value">{{ $payroll->paid_at?->format('M j, Y') ?? '—' }}</div>
    </div>
    <div class="emp-col" style="display:table-cell; padding-top:8px;">
        <div class="emp-label">Pay Period</div>
        <div class="emp-value">{{ $payroll->month_label }}</div>
    </div>
</div>

{{-- ── Body ── --}}
<div class="body">
    <div class="cols">

        {{-- Earnings column --}}
        <div class="col">
            <div class="section-title">Earnings</div>

            <div class="row">
                <div class="row-label">Base Salary</div>
                <div class="row-value green">{{ currency($payroll->base_salary) }}</div>
            </div>
            <div class="row">
                <div class="row-label">Bonus</div>
                <div class="row-value green">{{ currency($payroll->bonus) }}</div>
            </div>
            <div class="row">
                <div class="row-label">Incentive</div>
                <div class="row-value green">{{ currency($payroll->incentive) }}</div>
            </div>
            <div class="row">
                <div class="row-label">Overtime</div>
                <div class="row-value green">{{ currency($payroll->overtime_amount) }}</div>
            </div>

            <div class="total-row">
                <div class="total-label">Gross Salary</div>
                <div class="total-value">{{ currency($payroll->gross_salary) }}</div>
            </div>
        </div>

        {{-- Deductions column --}}
        <div class="col">
            <div class="section-title">Deductions</div>

            <div class="row">
                <div class="row-label">Late Penalty ({{ $payroll->late_days }} day(s) × {{ currency_symbol() }}10)</div>
                <div class="row-value red">{{ currency(-$payroll->late_deduction) }}</div>
            </div>
            <div class="row">
                <div class="row-label">Absent Deduction ({{ $payroll->absent_days }} day(s))</div>
                <div class="row-value red">{{ currency(-$payroll->absent_deduction) }}</div>
            </div>
            <div class="row">
                <div class="row-label">Unpaid Leave ({{ $payroll->unpaid_leave_days }} day(s))</div>
                <div class="row-value red">{{ currency(-$payroll->leave_deduction) }}</div>
            </div>

            <div class="total-row">
                <div class="total-label">Total Deductions</div>
                <div class="total-value" style="color:#dc3545;">{{ currency(-$payroll->total_deductions) }}</div>
            </div>
        </div>
    </div>

    {{-- Attendance summary ── --}}
    <div class="section-title" style="margin-top:20px;">Attendance Summary</div>
    <div class="att-grid">
        <div class="att-cell">
            <div class="att-num">{{ $payroll->working_days }}</div>
            <div class="att-label">Working Days</div>
        </div>
        <div class="att-cell">
            <div class="att-num" style="color:#28a745;">{{ $payroll->present_days }}</div>
            <div class="att-label">Present</div>
        </div>
        <div class="att-cell">
            <div class="att-num" style="color:#dc3545;">{{ $payroll->absent_days }}</div>
            <div class="att-label">Absent</div>
        </div>
        <div class="att-cell">
            <div class="att-num" style="color:#ffc107;">{{ $payroll->late_days }}</div>
            <div class="att-label">Late</div>
        </div>
        <div class="att-cell">
            <div class="att-num" style="color:#6c757d;">{{ $payroll->unpaid_leave_days }}</div>
            <div class="att-label">Unpaid Leave</div>
        </div>
    </div>
</div>

{{-- ── Net Salary Block ── --}}
<div class="net-block">
    <div class="net-left">
        <div class="net-label">Net Salary Payable</div>
        <div style="font-size:9px; color:#94a3b8; margin-top:3px;">
            Gross {{ currency($payroll->gross_salary) }}
            − Deductions {{ currency($payroll->total_deductions) }}
        </div>
    </div>
    <div class="net-right">
        <div class="net-value">{{ currency($payroll->net_salary) }}</div>
    </div>
</div>

{{-- ── Footer ── --}}
<div class="footer">
    <div class="footer-left">
        Generated on {{ now()->format('M j, Y H:i') }} · Hemdox HRMS
        @if ($payroll->note)
            · Note: {{ $payroll->note }}
        @endif
    </div>
    <div class="footer-right">
        This is a system-generated payslip and requires no signature.
    </div>
</div>

</body>
</html>
