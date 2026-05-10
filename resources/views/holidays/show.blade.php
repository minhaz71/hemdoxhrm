<x-app-layout>
    <x-slot name="title">Holiday Details</x-slot>
    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">{{ $holiday->title }}</h5>
            <small class="text-muted">{{ $holiday->start_date->format('M j, Y') }} - {{ $holiday->end_date->format('M j, Y') }}</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            {{-- Send / Retry email actions --}}
            @if ($holiday->status === 'active')
                @php $hasFailed = $holiday->emailLogs->where('status', 'failed')->count(); @endphp

                @if ($hasFailed)
                    <form method="POST" action="{{ route('holidays.retry-emails', $holiday) }}">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="bi bi-arrow-repeat me-1"></i>Retry Failed ({{ $hasFailed }})
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('holidays.send-emails', $holiday) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-send me-1"></i>
                        {{ $holiday->email_sent_at ? 'Re-send Emails' : 'Send Emails Now' }}
                    </button>
                </form>
            @endif

            <a href="{{ route('holidays.edit', $holiday) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
            <a href="{{ route('holidays.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="hrms-card p-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">Details</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Year</dt><dd class="col-sm-8">{{ $holiday->holiday_year }}</dd>
                    <dt class="col-sm-4">Type</dt><dd class="col-sm-8">{{ str_replace('_', ' ', ucfirst($holiday->type)) }}</dd>
                    <dt class="col-sm-4">Branch</dt><dd class="col-sm-8">{{ $holiday->branch?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Department</dt><dd class="col-sm-8">{{ $holiday->department?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Reason</dt><dd class="col-sm-8">{{ $holiday->reason ?? '—' }}</dd>
                    <dt class="col-sm-4">Notify Before</dt><dd class="col-sm-8">{{ $holiday->notify_before_days }} day(s)</dd>
                    <dt class="col-sm-4">Email Sent</dt><dd class="col-sm-8">{{ $holiday->email_sent_at?->format('M j, Y g:i A') ?? 'Not sent' }}</dd>
                </dl>
            </div>

            @if($holiday->type === 'employee_specific')
            <div class="hrms-card p-4 mt-4">
                <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">Employees</h6>
                <div class="d-flex flex-wrap gap-2">
                    @forelse($holiday->employees as $employee)
                        <span class="badge bg-light text-dark border">{{ $employee->full_name }}</span>
                    @empty
                        <span class="text-muted">No employees selected.</span>
                    @endforelse
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="hrms-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0 text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">
                        Email Logs
                    </h6>
                    @if($holiday->emailLogs->count())
                        <span class="badge bg-light text-dark border">
                            {{ $holiday->emailLogs->where('status', 'sent')->count() }}
                            / {{ $holiday->emailLogs->count() }} sent
                        </span>
                    @endif
                </div>

                @forelse($holiday->emailLogs->sortByDesc('created_at') as $log)
                    <div class="border-bottom py-2">
                        {{-- Employee name + email --}}
                        <div class="fw-semibold" style="font-size:.85rem;">
                            {{ $log->employee?->full_name ?? '—' }}
                        </div>
                        <div class="text-muted" style="font-size:.78rem;">{{ $log->email }}</div>

                        {{-- Status badge --}}
                        <div class="mt-1 d-flex align-items-center gap-2">
                            @if ($log->status === 'sent')
                                <span class="badge bg-success-subtle text-success">✅ Sent</span>
                                @if ($log->sent_at)
                                    <span class="text-muted" style="font-size:.75rem;">
                                        {{ $log->sent_at->format('M j, g:i A') }}
                                    </span>
                                @endif
                            @elseif ($log->status === 'failed')
                                <span class="badge bg-danger-subtle text-danger">❌ Failed</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">⏳ Pending</span>
                            @endif
                        </div>

                        {{-- Error message --}}
                        @if ($log->error_message)
                            <div class="text-danger mt-1" style="font-size:.78rem;word-break:break-word;">
                                {{ $log->error_message }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-muted" style="font-size:.875rem;">No email logs yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
