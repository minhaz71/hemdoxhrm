<x-app-layout>
    <x-slot name="title">Registrations</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Pending Registrations</h5>
            <small class="text-muted">Review and approve employee registration submissions</small>
        </div>
        <a href="{{ route('invitations.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-envelope-plus me-1"></i> Manage Invitations
        </a>
    </div>

    {{-- Filter bar --}}
    <form method="GET" class="hrms-card p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Name or email…" value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending"             {{ ($filters['status'] ?? '') === 'pending'             ? 'selected' : '' }}>Pending</option>
                    <option value="correction_required"{{ ($filters['status'] ?? '') === 'correction_required'? 'selected' : '' }}>Correction Required</option>
                    <option value="approved"           {{ ($filters['status'] ?? '') === 'approved'           ? 'selected' : '' }}>Approved</option>
                    <option value="rejected"           {{ ($filters['status'] ?? '') === 'rejected'           ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(array_filter($filters))
                <a href="{{ route('registrations.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
                @endif
            </div>
        </div>
    </form>

    <div class="hrms-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.875rem;">
                <thead class="table-light">
                    <tr>
                        <th>Applicant</th>
                        <th>Designation / Type</th>
                        <th>Invited By</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Reviewed By</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registrations as $reg)
                    @php
                        $sc = match($reg->status) {
                            'pending'              => 'bg-warning-subtle text-warning',
                            'approved'             => 'bg-success-subtle text-success',
                            'rejected'             => 'bg-danger-subtle text-danger',
                            'correction_required'  => 'bg-orange-subtle text-warning border border-warning',
                            default                => 'bg-secondary-subtle text-secondary',
                        };
                        $sl = match($reg->status) {
                            'correction_required' => 'Correction Required',
                            default => ucfirst($reg->status),
                        };
                        $inviter = $reg->invitation?->creator;
                    @endphp
                    <tr>
                        <td>
                            {{-- Photo thumbnail --}}
                            <div class="d-flex align-items-center gap-2">
                                @if($reg->photo)
                                <img src="{{ asset('storage/'.$reg->photo) }}" alt="{{ $reg->full_name }}"
                                     style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                                @else
                                <div style="width:32px;height:32px;border-radius:50%;background:#e9ecef;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.7rem;font-weight:700;color:#6c757d;">
                                    {{ strtoupper(substr($reg->first_name,0,1).substr($reg->last_name,0,1)) }}
                                </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $reg->full_name }}</div>
                                    <div class="text-muted" style="font-size:.78rem;">{{ $reg->email }}</div>
                                    @if($reg->login_id)
                                    <div style="font-size:.72rem;color:#6c757d;"><i class="bi bi-at"></i>{{ $reg->login_id }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:.82rem;">{{ $reg->designation?->name ?? '—' }}</div>
                            <span class="badge bg-info-subtle text-info" style="font-size:.68rem;">
                                {{ str_replace('_', ' ', ucfirst($reg->employment_type)) }}
                            </span>
                        </td>
                        <td>
                            @if($inviter)
                            <div class="d-flex align-items-center gap-1" style="font-size:.82rem;">
                                <i class="bi bi-person-badge text-primary" style="font-size:.75rem;"></i>
                                <span class="fw-semibold">{{ $inviter->name }}</span>
                            </div>
                            @if($reg->invitation?->note)
                            <div class="text-muted" style="font-size:.72rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                 title="{{ $reg->invitation->note }}">
                                {{ $reg->invitation->note }}
                            </div>
                            @endif
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $sc }}">{{ $sl }}</span></td>
                        <td style="white-space:nowrap;font-size:.8rem;">
                            {{ $reg->created_at->format('d M Y') }}
                        </td>
                        <td class="text-muted" style="font-size:.8rem;">
                            {{ $reg->reviewer?->name ?? '—' }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('registrations.show', $reg) }}"
                               class="btn btn-sm {{ $reg->status === 'pending' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <i class="bi bi-{{ $reg->status === 'pending' ? 'pencil-square' : 'eye' }} me-1"></i>
                                {{ $reg->status === 'pending' ? 'Review' : 'View' }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                            @if(array_filter($filters))
                                No registrations match your filters.
                                <a href="{{ route('registrations.index') }}">Clear filters</a>
                            @else
                                No registrations yet.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($registrations->hasPages())
        <div class="p-3 border-top">
            {{ $registrations->appends($filters)->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>

</x-app-layout>
