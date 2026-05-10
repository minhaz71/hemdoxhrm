<x-app-layout>
    <x-slot name="title">Time Doctor Imports</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Time Doctor Imports</h5>
            <small class="text-muted">Sync attendance and performance records from Time Doctor CSV exports.</small>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="hrms-card">
                <div class="card-header">
                    <i class="bi bi-cloud-upload me-2 text-primary"></i>Upload CSV
                </div>
                <form method="POST" action="{{ route('time-doctor.imports.store') }}" enctype="multipart/form-data" class="p-3">
                    @csrf

                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Time Doctor export</label>
                        <input type="file" name="csv_file" id="csv_file" class="form-control @error('csv_file') is-invalid @enderror" accept=".csv,text/csv" required>
                        @error('csv_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="small text-muted mb-3">
                        Required columns: Name, Email, Employee ID, User groups, Date, Time tracked, Idle minutes, Idle minutes %, Start time, End time.
                    </div>

                    <button class="btn btn-primary w-100">
                        <i class="bi bi-arrow-repeat me-1"></i> Import CSV
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="hrms-card">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2 text-success"></i>Import History
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>File</th>
                                <th>Imported By</th>
                                <th>Rows</th>
                                <th>Synced</th>
                                <th>Imported</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($imports as $import)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $import->original_filename }}</div>
                                        <small class="text-muted">{{ $import->file_hash }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $import->importer?->name ?? 'System' }}</div>
                                        <small class="text-muted">{{ $import->importer?->email }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">{{ $import->processed_rows }} processed</span>
                                        @if ($import->skipped_rows > 0)
                                            <span class="badge bg-warning-subtle text-warning ms-1">{{ $import->skipped_rows }} skipped</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small">Created: <strong>{{ $import->created_records }}</strong></div>
                                        <div class="small text-muted">Updated: {{ $import->updated_records }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $import->imported_at?->format('M j, Y') }}</div>
                                        <small class="text-muted">{{ $import->imported_at?->format('g:i A') }}</small>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('time-doctor.imports.show', $import) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                        No Time Doctor imports yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($imports->hasPages())
                    <div class="p-3 border-top">
                        {{ $imports->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
