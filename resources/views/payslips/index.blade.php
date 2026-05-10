<x-app-layout>
    <x-slot name="title">Payslips</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Payslips</h5>
            <small class="text-muted">
                {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
            </small>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('payslips.index') }}" class="d-flex gap-2">
                <select name="month" class="form-select form-select-sm">
                    @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}
                    </option>
                    @endforeach
                </select>
                <select name="year" class="form-select form-select-sm">
                    @foreach (range(now()->year, now()->year - 3, -1) as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-primary">Go</button>
            </form>

            {{-- Bulk Generate --}}
            <form method="POST" action="{{ route('payslips.bulk-generate') }}"
                  onsubmit="return confirm('Generate payslips for all paid employees this period?')">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year"  value="{{ $year }}">
                <button class="btn btn-sm btn-primary">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Generate All
                </button>
            </form>
        </div>
    </div>

    <div class="hrms-card">
        <div class="card-header"><i class="bi bi-receipt me-2 text-info"></i>Payslip Records</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Period</th>
                        <th>File</th>
                        <th>Size</th>
                        <th>Generated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $payslip)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $payslip->employee->full_name }}</div>
                            <small class="text-muted">{{ $payslip->employee->employee_code }}</small>
                        </td>
                        <td>{{ $payslip->month_label }}</td>
                        <td>
                            <span class="badge bg-danger-subtle text-danger">
                                <i class="bi bi-file-earmark-pdf me-1"></i>{{ $payslip->file_name }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $payslip->file_size_human }}</td>
                        <td>
                            <div>{{ $payslip->generated_at->format('M j, Y') }}</div>
                            <small class="text-muted">by {{ $payslip->generatedBy?->name ?? 'System' }}</small>
                        </td>
                        <td>
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('payslips.view', $payslip) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-secondary" title="Preview">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('payslips.download', $payslip) }}"
                                   class="btn btn-sm btn-outline-primary" title="Download">
                                    <i class="bi bi-download"></i>
                                </a>
                                {{-- Re-generate --}}
                                <form method="POST" action="{{ route('payslips.generate') }}">
                                    @csrf
                                    <input type="hidden" name="payroll_id" value="{{ $payslip->payroll_id }}">
                                    <button class="btn btn-sm btn-outline-warning" title="Regenerate">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-pdf fs-2 d-block mb-2 opacity-50"></i>
                            No payslips generated for this period.
                            <br>Make sure payrolls are marked as paid before generating payslips.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
        <div class="p-3 border-top">{{ $records->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</x-app-layout>
