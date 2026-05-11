<x-app-layout>
    <x-slot name="title">Payroll — {{ $payroll->month_label }}</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Payroll Detail</h5>
            <small class="text-muted">
                {{ $payroll->employee->full_name }} — {{ $payroll->month_label }}
            </small>
        </div>
        <div class="d-flex gap-2">
            @can('update', $payroll)
            <a href="{{ route('payroll.edit', $payroll) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Adjust
            </a>
            @endcan
            @if (! $payroll->isLocked() && (auth()->user()->isAdmin() || auth()->user()->isHR()))
            <form method="POST" action="{{ route('payroll.regenerate', $payroll) }}"
                  onsubmit="return confirm('Regenerate this payroll? This will delete the current draft and recalculate using the correct salary and latest attendance/leave data.')">
                @csrf
                <button class="btn btn-outline-warning">
                    <i class="bi bi-arrow-clockwise me-1"></i> Regenerate
                </button>
            </form>
            <form method="POST" action="{{ route('payroll.pay', $payroll) }}"
                  onsubmit="return confirm('Mark as PAID? This will permanently lock the record.')">
                @csrf @method('PATCH')
                <button class="btn btn-success">
                    <i class="bi bi-lock me-1"></i> Mark Paid & Lock
                </button>
            </form>
            @endif
            @if((auth()->user()->isAdmin() || auth()->user()->isHR()) && ! $payroll->email_sent_at)
            <form method="POST" action="{{ route('payroll.send-message', $payroll) }}"
                  onsubmit="return confirm('Send payroll message to this employee now?')">
                @csrf
                <button class="btn btn-outline-info">
                    <i class="bi bi-send me-1"></i> Send Message
                </button>
            </form>
            @endif
            <a href="{{ auth()->user()->isAdmin() || auth()->user()->isHR() ? route('payroll.index') : route('payroll.my') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    @if ($payroll->isLocked())
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-lock-fill"></i>
        <span>
            Paid on {{ $payroll->paid_at->format('M j, Y H:i') }}
            by {{ $payroll->paidBy?->name ?? 'System' }}.
            <strong>This record is locked.</strong>
        </span>
    </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">

            {{-- Salary Resolution Info --}}
            @if($payroll->salary_resolution_mode)
            <div class="hrms-card mb-4 border-start border-4 {{ $payroll->salary_had_mid_change ? 'border-warning' : 'border-secondary' }}">
                <div class="p-3 d-flex align-items-start gap-2" style="font-size:.85rem;">
                    <i class="bi bi-info-circle-fill {{ $payroll->salary_had_mid_change ? 'text-warning' : 'text-secondary' }} mt-1"></i>
                    <div>
                        <span class="fw-semibold">Salary Calculation Mode:</span>
                        @php
                            $modeLabels = [
                                'month_start' => 'Month Start — salary on 1st of month',
                                'month_end'   => 'Month End — salary on last day of month',
                                'prorated'    => 'Prorated — weighted by days at each rate',
                            ];
                        @endphp
                        {{ $modeLabels[$payroll->salary_resolution_mode] ?? $payroll->salary_resolution_mode }}
                        @if($payroll->salary_had_mid_change)
                            <span class="badge bg-warning text-dark ms-2">Mid-month salary change detected</span>
                        @endif

                        {{-- Prorated breakdown --}}
                        @if($payroll->salary_segments && count($payroll->salary_segments) > 1)
                        <div class="mt-2">
                            <table class="table table-sm table-bordered mb-0" style="font-size:.8rem;max-width:520px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period</th>
                                        <th>Salary Rate</th>
                                        <th>Working Days</th>
                                        <th>Daily Rate</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payroll->salary_segments as $seg)
                                    <tr>
                                        <td class="text-muted">
                                            {{ \Carbon\Carbon::parse($seg['from'])->format('d M') }}
                                            – {{ \Carbon\Carbon::parse($seg['to'])->format('d M Y') }}
                                        </td>
                                        <td>{{ currency($seg['salary']) }}</td>
                                        <td class="text-center">{{ $seg['working_days'] }}</td>
                                        <td>{{ currency($seg['daily_rate']) }}</td>
                                        <td class="fw-semibold">{{ currency($seg['amount']) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="2" class="fw-semibold">Prorated Base Salary</td>
                                        <td class="text-center fw-semibold">{{ $payroll->working_days }}</td>
                                        <td></td>
                                        <td class="fw-bold text-success">{{ currency($payroll->base_salary) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Earnings --}}
            <div class="hrms-card mb-4">
                <div class="card-header text-success">
                    <i class="bi bi-plus-circle me-2"></i>Earnings
                </div>
                <div class="p-4">
                    @php
                    $earnings = [
                        'Base Salary'   => $payroll->base_salary,
                        'Bonus'         => $payroll->bonus,
                        'Incentive'     => $payroll->incentive,
                    ];
                    if ($overtimeEnabled) {
                        $earnings['Overtime'] = $payroll->overtime_amount;
                    }
                    @endphp
                    @foreach ($earnings as $label => $amount)
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">{{ $label }}</span>
                        <span class="fw-semibold">{{ currency($amount) }}</span>
                    </div>
                    @endforeach
                    <div class="d-flex justify-content-between py-2 mt-1">
                        <span class="fw-bold">Gross Salary</span>
                        <span class="fw-bold text-success fs-5">{{ currency($payroll->gross_salary) }}</span>
                    </div>
                </div>
            </div>

            {{-- Deductions --}}
            <div class="hrms-card mb-4">
                <div class="card-header text-danger">
                    <i class="bi bi-dash-circle me-2"></i>Deductions
                </div>
                <div class="p-4">
                    @php
                    $deductions = [
                        'Late Penalty ('.$payroll->late_days.' day(s)'.($payroll->late_penalty_enabled ? ' × '.currency($payroll->late_penalty_amount) : ' · off').')' => $payroll->late_deduction,
                        'Absent Deduction ('.$payroll->absent_days.' day(s))'     => $payroll->absent_deduction,
                        'Unpaid Leave ('.$payroll->unpaid_leave_days.' day(s)'.($payroll->leave_penalty_enabled ? ' × '.$payroll->leave_penalty_rate.' daily rate' : ' · off').')'   => $payroll->leave_deduction,
                    ];
                    @endphp
                    @foreach ($deductions as $label => $amount)
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">{{ $label }}</span>
                        <span class="fw-semibold text-danger">{{ currency(-$amount) }}</span>
                    </div>
                    @endforeach
                    <div class="d-flex justify-content-between py-2 mt-1">
                        <span class="fw-bold">Total Deductions</span>
                        <span class="fw-bold text-danger fs-5">{{ currency(-$payroll->total_deductions) }}</span>
                    </div>
                </div>
            </div>

            {{-- Net --}}
            <div class="hrms-card">
                <div class="p-4 d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5">Net Salary</span>
                    <span class="fw-bold fs-3 text-primary">{{ currency($payroll->net_salary) }}</span>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                    Attendance Snapshot
                </h6>
                @php
                $attRows = [
                    ['Working Days',  $payroll->working_days,           'secondary', $payroll->management_working_days !== null ? 'set by management' : null],
                    ['Present',       $payroll->present_days,           'success',   null],
                    ['Late',          $payroll->late_days,              'warning',   null],
                    ['Absent',        $payroll->absent_days,            'danger',    '← deducted'],
                    ['Unpaid Leave',  $payroll->unpaid_leave_days,      'info',      '← deducted'],
                    ['Paid Leave',    ($payroll->paid_leave_days  ?? 0),'primary',   'no deduction'],
                    ['Holiday',       ($payroll->holiday_days     ?? 0),'warning',   'no deduction'],
                    ['Weekly Off',    ($payroll->weekly_off_days  ?? 0),'secondary', 'no deduction'],
                ];
                @endphp
                @foreach ($attRows as [$label, $val, $color, $note])
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted" style="font-size:.85rem;">
                        {{ $label }}
                        @if($note)
                            <small class="text-muted" style="font-size:.72rem;">{{ $note }}</small>
                        @endif
                    </span>
                    <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ $val }}</span>
                </div>
                @endforeach
            </div>

            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                    Status
                </h6>
                @if ($payroll->status === 'paid')
                    <span class="badge bg-success px-3 py-2 fs-6">
                        <i class="bi bi-lock me-1"></i>Paid & Locked
                    </span>
                @elseif ($payroll->status === 'processed')
                    <span class="badge bg-info px-3 py-2 fs-6">Processed</span>
                @else
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6">Draft</span>
                @endif
            </div>

            @if ($payroll->note)
            <div class="hrms-card p-4">
                <h6 class="fw-semibold mb-2 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">Note</h6>
                <p class="mb-0 text-muted">{{ $payroll->note }}</p>
            </div>
            @endif

            <div class="hrms-card p-4 mt-4">
                <h6 class="fw-semibold mb-2 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">Payroll Message</h6>
                @if($payroll->email_sent_at)
                    <div class="text-success fw-semibold">Sent</div>
                    <small class="text-muted">
                        {{ $payroll->email_sent_at->format('M j, Y H:i') }}
                        @if($payroll->emailSentBy)
                            by {{ $payroll->emailSentBy->name }}
                        @endif
                    </small>
                @else
                    <div class="text-muted">Not sent yet</div>
                    <small class="text-muted">Admin/HR can send after checking this payroll.</small>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
