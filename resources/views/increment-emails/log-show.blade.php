<x-app-layout>
<x-slot name="title">Email Log #{{ $log->id }}</x-slot>
<x-alert />

{{-- ── Header ──────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-envelope me-2"></i>Email Log #{{ $log->id }}</h5>
        <small class="text-muted">{{ $log->employee?->full_name }} — {{ $log->subject }}</small>
    </div>
    <a href="{{ route('increment-emails.logs') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Log
    </a>
</div>

{{-- ── Meta card ──────────────────────────────────────────── --}}
<div class="hrms-card p-4 mb-4">
    <div class="row g-3">
        <div class="col-sm-3">
            <small class="text-muted d-block">Employee</small>
            <span class="fw-semibold">{{ $log->employee?->full_name ?? '—' }}</span>
        </div>
        <div class="col-sm-3">
            <small class="text-muted d-block">To Email</small>
            <span class="fw-semibold">{{ $log->email }}</span>
        </div>
        <div class="col-sm-2">
            <small class="text-muted d-block">Status</small>
            <span class="badge bg-{{ $log->getStatusBadgeColor() }}-subtle text-{{ $log->getStatusBadgeColor() }} px-2 py-1">
                {{ ucfirst($log->status) }}
            </span>
        </div>
        <div class="col-sm-2">
            <small class="text-muted d-block">Sent By</small>
            <span class="fw-semibold">{{ $log->sentBy?->name ?? '—' }}</span>
        </div>
        <div class="col-sm-2">
            <small class="text-muted d-block">Sent At</small>
            <span class="fw-semibold">{{ $log->sent_at?->format('M j, Y H:i') ?? '—' }}</span>
        </div>
        <div class="col-12">
            <small class="text-muted d-block">Subject</small>
            <span class="fw-semibold">{{ $log->subject }}</span>
        </div>
        @if($log->hasFailed() && $log->error_message)
        <div class="col-12">
            <div class="alert alert-danger mb-0 py-2">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Error:</strong> {{ $log->error_message }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── Email Body Preview ─────────────────────────────────── --}}
@if($log->body)
<div class="hrms-card">
    <div class="card-header fw-semibold">
        <i class="bi bi-envelope-open me-2"></i>Email Body
    </div>
    <div class="p-0" style="background:#f4f6f9;border-radius:0 0 12px 12px;">
        <iframe id="bodyFrame" style="width:100%;min-height:400px;border:none;border-radius:0 0 12px 12px;"
                srcdoc=""></iframe>
    </div>
</div>
@endif

@push('scripts')
<script>
const body = @json($log->body);
const iframe = document.getElementById('bodyFrame');
if (iframe && body) {
    iframe.srcdoc = body;
    iframe.addEventListener('load', function() {
        try {
            iframe.style.height = (iframe.contentWindow.document.body.scrollHeight + 40) + 'px';
        } catch(e) {}
    });
}
</script>
@endpush

</x-app-layout>
