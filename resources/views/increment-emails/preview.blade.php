<x-app-layout>
<x-slot name="title">Preview &amp; Send — Increment Emails</x-slot>
<x-alert />

@push('styles')
<style>
.recipient-item { cursor:pointer; border-radius:8px; padding:10px 14px; transition:.12s; border:1px solid transparent; }
.recipient-item:hover { background:#f8f9fa; }
.recipient-item.active { background:#eff6ff; border-color:#bfdbfe; }
.email-iframe  { border:none; width:100%; border-radius:10px; min-height:500px; display:block; }
.iframe-wrap   { background:#f0f2f5; border-radius:12px; border:1px solid #e2e8f0; padding:24px; }
.field-group   { background:#fff; border:1px solid #e9ecef; border-radius:10px; padding:16px 18px; }
.ph-badge      { font-size:.68rem; background:#e8f0fe; color:#1a73e8; border-radius:4px;
                 padding:1px 5px; cursor:pointer; font-family:monospace; user-select:all; }
.update-spinner { display:none; }
</style>
@endpush

{{-- ── Header ────────────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-eye me-2 text-primary"></i>Preview &amp; Send</h5>
        <small class="text-muted">{{ count($previews) }} recipient(s) selected — customise the template then send.</small>
    </div>
    <a href="{{ route('increment-emails.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<div class="row g-4">

    {{-- ══════════════════════════════════════ --}}
    {{-- LEFT PANEL: Recipients + Template Edit --}}
    {{-- ══════════════════════════════════════ --}}
    <div class="col-lg-4">

        {{-- Recipient list --}}
        <div class="hrms-card p-3 mb-3">
            <p class="fw-semibold text-uppercase text-muted mb-2" style="font-size:.72rem;letter-spacing:.05em;">
                Recipients ({{ count($previews) }})
            </p>
            <div id="recipientNav">
                @foreach($previews as $i => $p)
                @php $sh = $p['salaryHistory']; @endphp
                <div class="recipient-item {{ $i === 0 ? 'active' : '' }} d-flex align-items-start gap-2 mb-1"
                     onclick="switchPane({{ $i }})">
                    <div class="flex-shrink-0 mt-1">
                        <span class="badge rounded-circle bg-primary-subtle text-primary" style="width:28px;height:28px;font-size:.75rem;display:flex;align-items:center;justify-content:center;">
                            {{ $i + 1 }}
                        </span>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-semibold text-truncate" style="font-size:.88rem;max-width:200px;">
                            {{ $sh->employee?->full_name ?? '—' }}
                        </div>
                        <small class="text-muted">{{ $p['email'] ?? 'No email' }}</small>
                        <div>
                            <span class="badge bg-{{ $sh->getTypeBadge() }}-subtle text-{{ $sh->getTypeBadge() }}"
                                  style="font-size:.65rem;">
                                {{ $sh->getTypeLabel() }} — {{ $sh->effective_from->format('M Y') }}
                            </span>
                        </div>
                    </div>
                    @if(! $p['email'])
                    <span class="badge bg-danger-subtle text-danger ms-auto" style="font-size:.62rem;">No Email</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── Editable Template Fields ─────────────────────── --}}
        <div class="hrms-card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="fw-semibold text-uppercase text-muted mb-0" style="font-size:.72rem;letter-spacing:.05em;">
                    Customise Email
                </p>
                <small class="text-muted" style="font-size:.72rem;">
                    Applies to all recipients
                </small>
            </div>

            {{-- Placeholder pills --}}
            <div class="mb-3 d-flex flex-wrap gap-1">
                @foreach(['{employee_name}','{previous_salary}','{new_salary}','{effective_date}','{increment_amount}','{increment_percentage}'] as $ph)
                    <span class="ph-badge" onclick="copyPh(this)" title="Click to copy">{{ $ph }}</span>
                @endforeach
            </div>

            <div class="d-flex flex-column gap-3">

                {{-- Subject --}}
                <div>
                    <label class="form-label form-label-sm fw-semibold mb-1">Subject</label>
                    <input type="text" id="tpl_subject" class="form-control form-control-sm"
                           value="{{ $template['subject'] }}"
                           oninput="schedulePreviewUpdate()">
                </div>

                {{-- Intro --}}
                <div>
                    <label class="form-label form-label-sm fw-semibold mb-1">Opening Message</label>
                    <textarea id="tpl_intro" class="form-control form-control-sm" rows="3"
                              oninput="schedulePreviewUpdate()">{{ $template['intro'] }}</textarea>
                    <small class="text-muted">Leave blank for built-in default.</small>
                </div>

                {{-- Closing --}}
                <div>
                    <label class="form-label form-label-sm fw-semibold mb-1">Closing Message</label>
                    <textarea id="tpl_closing" class="form-control form-control-sm" rows="2"
                              oninput="schedulePreviewUpdate()">{{ $template['closing'] }}</textarea>
                </div>

                {{-- Signature --}}
                <div class="border-top pt-3">
                    <label class="form-label form-label-sm fw-semibold mb-2 text-uppercase text-muted"
                           style="font-size:.7rem;letter-spacing:.04em;">Signature</label>
                    <div class="d-flex flex-column gap-2">
                        <input type="text" id="tpl_sig_name" class="form-control form-control-sm"
                               placeholder="Name (e.g. HR Department)"
                               value="{{ $template['signature_name'] }}"
                               oninput="schedulePreviewUpdate()">
                        <input type="text" id="tpl_sig_title" class="form-control form-control-sm"
                               placeholder="Title / Designation"
                               value="{{ $template['signature_title'] }}"
                               oninput="schedulePreviewUpdate()">
                        <input type="text" id="tpl_sig_contact" class="form-control form-control-sm"
                               placeholder="Contact (optional)"
                               value="{{ $template['signature_contact'] }}"
                               oninput="schedulePreviewUpdate()">
                    </div>
                </div>

                <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="refreshPreview()">
                    <span class="update-spinner spinner-border spinner-border-sm me-1"></span>
                    <i class="bi bi-arrow-clockwise me-1 refresh-icon"></i>Update Preview
                </button>
            </div>
        </div>

        {{-- ── Send Button ──────────────────────────────────── --}}
        <form method="POST" action="{{ route('increment-emails.send') }}" id="sendForm"
              onsubmit="return injectTemplateAndConfirm()">
            @csrf
            @foreach($ids as $id)
                <input type="hidden" name="selected[]" value="{{ $id }}">
            @endforeach
            {{-- Template values injected by JS before submit --}}
            <input type="hidden" name="subject"           id="fs_subject">
            <input type="hidden" name="intro"             id="fs_intro">
            <input type="hidden" name="closing"           id="fs_closing">
            <input type="hidden" name="signature_name"    id="fs_sig_name">
            <input type="hidden" name="signature_title"   id="fs_sig_title">
            <input type="hidden" name="signature_contact" id="fs_sig_contact">

            <button type="submit" class="btn btn-success w-100">
                <i class="bi bi-send me-2"></i>Send to All {{ count($previews) }} Recipient(s)
            </button>
            <p class="text-muted text-center mt-2 mb-0" style="font-size:.75rem;">
                <i class="bi bi-shield-lock me-1"></i>Emails are sent manually only — no auto-send.
            </p>
        </form>
    </div>

    {{-- ══════════════════════════════════════ --}}
    {{-- RIGHT PANEL: Email Preview Iframe      --}}
    {{-- ══════════════════════════════════════ --}}
    <div class="col-lg-8">

        {{-- Meta strip --}}
        <div class="hrms-card p-3 mb-3 d-flex align-items-center gap-3 flex-wrap">
            <div>
                <small class="text-muted d-block" style="font-size:.72rem;">To</small>
                <span class="fw-semibold" id="previewEmail" style="font-size:.88rem;">—</span>
            </div>
            <div class="vr mx-1 d-none d-sm-block"></div>
            <div>
                <small class="text-muted d-block" style="font-size:.72rem;">Subject</small>
                <span class="fw-semibold" id="previewSubjectStrip" style="font-size:.88rem;">—</span>
            </div>
            <div class="ms-auto d-flex gap-1">
                <button class="btn btn-sm btn-outline-secondary" onclick="switchPane(currentPane - 1)" id="btnPrev">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <span class="btn btn-sm btn-light disabled" id="paneLabel">1 / {{ count($previews) }}</span>
                <button class="btn btn-sm btn-outline-secondary" onclick="switchPane(currentPane + 1)" id="btnNext">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

        {{-- Email iframe --}}
        <div class="iframe-wrap">
            <iframe class="email-iframe" id="previewFrame"
                    onload="resizeIframe(this)"></iframe>
        </div>
    </div>
</div>

@php
    $previewPayload = $previews->map(function ($p) {
        return [
            'id' => $p['salaryHistory']->id,
            'email' => $p['email'],
            'body' => $p['body'],
            'name' => $p['salaryHistory']->employee?->full_name,
        ];
    })->values()->toArray();
@endphp

@push('scripts')
<script>
// ── Data ─────────────────────────────────────────────────────────────
const PREVIEWS = @json($previewPayload);

const RENDER_URL = @json(route('increment-emails.render'));
const CSRF       = @json(csrf_token());

let currentPane  = 0;
let debounceTimer = null;
let iframeLoaded  = {};

// ── Navigation ───────────────────────────────────────────────────────
function switchPane(idx) {
    const total = PREVIEWS.length;
    idx = Math.max(0, Math.min(total - 1, idx));
    currentPane = idx;

    document.querySelectorAll('.recipient-item').forEach((el, i) =>
        el.classList.toggle('active', i === idx));

    const p = PREVIEWS[idx];
    document.getElementById('previewEmail').textContent  = p.email ?? 'No email';
    document.getElementById('previewSubjectStrip').textContent = getField('subject');
    document.getElementById('paneLabel').textContent = `${idx + 1} / ${total}`;
    document.getElementById('btnPrev').disabled = idx === 0;
    document.getElementById('btnNext').disabled = idx === total - 1;

    if (!iframeLoaded[idx]) {
        loadIframe(p.body);
        iframeLoaded[idx] = true;
    } else {
        // Re-render with current template
        doRefreshPreview(idx);
    }
}

function loadIframe(html) {
    const iframe = document.getElementById('previewFrame');
    iframe.srcdoc = html || '<p style="padding:20px;color:#888">No preview available.</p>';
}

function resizeIframe(el) {
    try { el.style.height = (el.contentWindow.document.body.scrollHeight + 40) + 'px'; } catch(e) {}
}

// ── Template helpers ─────────────────────────────────────────────────
function getField(id) {
    return (document.getElementById('tpl_' + id)?.value ?? '').trim();
}

function schedulePreviewUpdate() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => doRefreshPreview(currentPane), 900);
    document.getElementById('previewSubjectStrip').textContent = getField('subject');
}

function refreshPreview() { doRefreshPreview(currentPane); }

function doRefreshPreview(idx) {
    const p = PREVIEWS[idx];
    if (!p) return;

    // Show spinner
    document.querySelector('.update-spinner').style.display = 'inline-block';
    document.querySelector('.refresh-icon').style.display   = 'none';

    fetch(RENDER_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept':       'application/json',
        },
        body: JSON.stringify({
            salary_history_id: p.id,
            subject:           getField('subject'),
            intro:             getField('intro'),
            closing:           getField('closing'),
            signature_name:    getField('sig_name'),
            signature_title:   getField('sig_title'),
            signature_contact: getField('sig_contact'),
        }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.html) {
            PREVIEWS[idx].body = data.html;
            iframeLoaded[idx]  = true;
            loadIframe(data.html);
        }
    })
    .catch(console.error)
    .finally(() => {
        document.querySelector('.update-spinner').style.display = 'none';
        document.querySelector('.refresh-icon').style.display   = '';
    });
}

// ── Send form: inject template values ───────────────────────────────
function injectTemplateAndConfirm() {
    const total = {{ count($previews) }};
    if (!confirm(`Send salary increment notification emails to ${total} employee(s)?\n\nThis action cannot be undone.`)) return false;
    document.getElementById('fs_subject').value     = getField('subject');
    document.getElementById('fs_intro').value       = getField('intro');
    document.getElementById('fs_closing').value     = getField('closing');
    document.getElementById('fs_sig_name').value    = getField('sig_name');
    document.getElementById('fs_sig_title').value   = getField('sig_title');
    document.getElementById('fs_sig_contact').value = getField('sig_contact');
    return true;
}

// ── Placeholder copy ─────────────────────────────────────────────────
function copyPh(el) {
    navigator.clipboard.writeText(el.textContent).then(() => {
        const orig = el.style.background;
        el.style.background = '#c3e6cb';
        setTimeout(() => el.style.background = orig, 600);
    }).catch(() => {});
}

// ── Boot ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => switchPane(0));
</script>
@endpush

</x-app-layout>
