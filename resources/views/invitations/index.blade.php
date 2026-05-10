<x-app-layout>
    <x-slot name="title">Invitations</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Registration Invitations</h5>
            <small class="text-muted">Generate secure links to invite employees to register</small>
        </div>
        <button class="btn btn-primary btn-sm" id="btnNew"
                data-bs-toggle="modal" data-bs-target="#invModal">
            <i class="bi bi-plus-lg me-1"></i> New Invitation
        </button>
    </div>

    <div class="hrms-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:.875rem;">
                <thead class="table-light">
                    <tr>
                        <th>Created By</th>
                        <th>Note / Email</th>
                        <th class="text-center">Uses</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Submissions</th>
                        <th>Created</th>
                        <th class="text-end" style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invitations as $inv)
                    @php
                        $isExpired  = $inv->status === 'active' && $inv->expires_at->isPast();
                        $isExhausted= $inv->status === 'active' && $inv->max_uses !== null && $inv->uses_count >= $inv->max_uses;
                        $effStatus  = $isExpired ? 'expired' : ($isExhausted ? 'exhausted' : $inv->status);
                        $statusMap  = [
                            'active'    => ['bg-success-subtle text-success',   'Active'],
                            'exhausted' => ['bg-info-subtle text-info',         'Exhausted'],
                            'revoked'   => ['bg-danger-subtle text-danger',     'Revoked'],
                            'expired'   => ['bg-warning-subtle text-warning',   'Expired'],
                        ];
                        [$sc, $sl] = $statusMap[$effStatus] ?? ['bg-secondary-subtle text-secondary', ucfirst($effStatus)];

                        $regs     = $inv->pendingRegistrations;
                        $pending  = $regs->where('status', 'pending')->count();
                        $approved = $regs->where('status', 'approved')->count();
                        $rejected = $regs->where('status', 'rejected')->count();
                        $total    = $regs->count();
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold" style="font-size:.85rem;">{{ $inv->creator?->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:.75rem;">{{ $inv->created_at->format('d M Y') }}</div>
                        </td>
                        <td>
                            @if($inv->note)
                            <div style="font-size:.82rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                 title="{{ $inv->note }}">{{ $inv->note }}</div>
                            @endif
                            @if($inv->email)
                            <div class="text-muted" style="font-size:.75rem;">{{ $inv->email }}</div>
                            @endif
                            @if(!$inv->note && !$inv->email)
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php
                                $maxLabel = $inv->max_uses === null ? '∞' : $inv->max_uses;
                                $pct = $inv->max_uses ? min(100, round($inv->uses_count / $inv->max_uses * 100)) : 0;
                                $barClass = $pct >= 100 ? 'bg-danger' : ($pct >= 75 ? 'bg-warning' : 'bg-success');
                            @endphp
                            <div class="fw-semibold" style="font-size:.82rem;">{{ $inv->uses_count }} / {{ $maxLabel }}</div>
                            @if($inv->max_uses !== null)
                            <div class="progress mt-1" style="height:4px;width:64px;margin:0 auto;">
                                <div class="progress-bar {{ $barClass }}" style="width:{{ $pct }}%"></div>
                            </div>
                            @endif
                        </td>
                        <td style="white-space:nowrap;font-size:.82rem;">
                            <span title="{{ $inv->expires_at->format('d M Y H:i') }}">
                                {{ $inv->expires_at->diffForHumans() }}
                            </span>
                        </td>
                        <td><span class="badge {{ $sc }}">{{ $sl }}</span></td>
                        <td>
                            @if($total === 0)
                                <span class="text-muted" style="font-size:.8rem;">No submissions</span>
                            @else
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($pending)
                                    <a href="{{ route('registrations.index', ['invitation_id' => $inv->id, 'status' => 'pending']) }}"
                                       class="badge bg-warning-subtle text-warning text-decoration-none">
                                        {{ $pending }} pending
                                    </a>
                                    @endif
                                    @if($approved)
                                    <span class="badge bg-success-subtle text-success">{{ $approved }} approved</span>
                                    @endif
                                    @if($rejected)
                                    <span class="badge bg-danger-subtle text-danger">{{ $rejected }} rejected</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td style="white-space:nowrap;font-size:.8rem;">{{ $inv->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                @if($effStatus === 'active')
                                <button class="btn btn-sm btn-outline-secondary btn-copy"
                                        data-url="{{ $inv->registration_url }}"
                                        title="Copy registration link">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-revoke"
                                        data-id="{{ $inv->id }}"
                                        title="Revoke invitation">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                @elseif($effStatus === 'exhausted')
                                <span class="text-muted" style="font-size:.75rem;">Full</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-envelope-x fs-2 d-block mb-2 opacity-50"></i>
                            No invitations yet. Click <strong>New Invitation</strong> to create one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invitations->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $invitations->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>

    {{-- ── New Invitation Modal ──────────────────────────────────────── --}}
    <div class="modal fade" id="invModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">New Registration Invitation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="invErrors" class="alert alert-danger d-none" style="font-size:.875rem;"></div>

                    {{-- Expiry --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Link Expiry <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            @foreach([1 => '1 Hour', 6 => '6 Hours', 12 => '12 Hours', 24 => '24 Hours'] as $h => $label)
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="radio" name="expiry_preset"
                                       id="exp{{ $h }}" value="{{ $h }}" {{ $h === 24 ? 'checked' : '' }}>
                                <label class="form-check-label" for="exp{{ $h }}">{{ $label }}</label>
                            </div>
                            @endforeach
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="radio" name="expiry_preset"
                                       id="expCustom" value="custom">
                                <label class="form-check-label" for="expCustom">Custom</label>
                            </div>
                        </div>
                        <div id="customHoursWrap" class="d-none">
                            <div class="input-group" style="max-width:200px;">
                                <input type="number" id="customHours" class="form-control form-control-sm"
                                       placeholder="Hours" min="1" max="720" value="48">
                                <span class="input-group-text">hours</span>
                            </div>
                            <div class="form-text">Max 720 hours (30 days)</div>
                        </div>
                    </div>

                    {{-- Max uses --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            How many people can register with this link?
                        </label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            @foreach([1 => '1 person', 5 => '5 people', 10 => '10 people', 25 => '25 people', 50 => '50 people'] as $n => $label)
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="radio" name="max_uses_preset"
                                       id="mu{{ $n }}" value="{{ $n }}" {{ $n === 1 ? 'checked' : '' }}>
                                <label class="form-check-label" for="mu{{ $n }}">{{ $label }}</label>
                            </div>
                            @endforeach
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="radio" name="max_uses_preset"
                                       id="muCustom" value="custom">
                                <label class="form-check-label" for="muCustom">Custom</label>
                            </div>
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="radio" name="max_uses_preset"
                                       id="muUnlimited" value="unlimited">
                                <label class="form-check-label" for="muUnlimited">
                                    <i class="bi bi-infinity me-1"></i>Unlimited
                                </label>
                            </div>
                        </div>
                        <div id="customUsesWrap" class="d-none">
                            <div class="input-group" style="max-width:200px;">
                                <input type="number" id="customUses" class="form-control form-control-sm"
                                       placeholder="Number of people" min="1" max="10000" value="100">
                                <span class="input-group-text">people</span>
                            </div>
                            <div class="form-text">Max 10,000</div>
                        </div>
                        <div id="unlimitedHint" class="d-none">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                This link can be used by any number of people until it expires or is revoked.
                            </small>
                        </div>
                    </div>

                    {{-- Optional email pre-fill --}}
                    <div class="mb-3">
                        <label class="form-label">Pre-fill Email
                            <span class="text-muted fw-normal">(optional — only useful for 1-person links)</span>
                        </label>
                        <input type="email" id="invEmail" class="form-control form-control-sm"
                               placeholder="employee@example.com">
                    </div>

                    {{-- Internal note --}}
                    <div class="mb-1">
                        <label class="form-label">Internal Note <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" id="invNote" class="form-control form-control-sm"
                               placeholder="e.g. Batch hire — Engineering team Q2" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnCreate">
                        <span id="createText">Generate Link</span>
                        <span id="createSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Link Generated Modal ──────────────────────────────────────── --}}
    <div class="modal fade" id="linkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold text-success">
                        <i class="bi bi-check-circle me-2"></i>Invitation Created
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="linkCapacity" class="alert alert-info py-2" style="font-size:.82rem;"></div>
                    <p class="mb-2" style="font-size:.875rem;">Share this link with the employee(s). It expires as shown.</p>
                    <div class="input-group mb-2">
                        <input type="text" id="generatedUrl" class="form-control" readonly style="font-size:.8rem;">
                        <button class="btn btn-outline-primary" type="button" id="btnCopyGenerated">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <small class="text-muted" id="generatedExpiry"></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Revoke Confirm Modal ──────────────────────────────────────── --}}
    <div class="modal fade" id="revokeModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-semibold text-danger">Revoke Invitation?</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2" style="font-size:.875rem;">
                    The link will stop working immediately for all remaining slots. This cannot be undone.
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-danger" id="btnConfirmRevoke">
                        Revoke
                        <span id="revokeSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {

    // ── Helpers ────────────────────────────────────────────────────
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const CSRF     = csrfMeta ? csrfMeta.content : '';

    /** Get-or-create a Bootstrap modal safely (no crash if Bootstrap missing). */
    function modal(id) {
        const el = document.getElementById(id);
        if (!el) return { show(){}, hide(){} };
        if (typeof bootstrap === 'undefined') return { show(){}, hide(){} };
        return bootstrap.Modal.getOrCreateInstance(el);
    }

    let revokingId = null;

    function api(url, method, body = null) {
        return fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: body ? JSON.stringify(body) : null,
        }).then(r => r.json().catch(() => ({ success: false, message: 'Server error.' })));
    }

    function showToast(msg, ok = true) {
        const el = document.createElement('div');
        el.className = `toast align-items-center text-bg-${ok ? 'success' : 'danger'} border-0 show`;
        el.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;min-width:280px;';
        el.innerHTML = `<div class="d-flex"><div class="toast-body">${msg}</div>
            <button class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button></div>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    function showErr(html, isHtml = false) {
        const box = document.getElementById('invErrors');
        if (!box) return;
        box.innerHTML = isHtml ? html : `<div>${html}</div>`;
        box.classList.remove('d-none');
    }

    /** Copy text with modern API + execCommand fallback. */
    function copyToClipboard(text) {
        if (!text) { showToast('No link to copy.', false); return; }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text)
                .then(() => showToast('Link copied to clipboard!'))
                .catch(() => legacyCopy(text));
        } else {
            legacyCopy(text);
        }
    }

    function legacyCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0;';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try {
            const ok = document.execCommand('copy');
            showToast(ok ? 'Link copied to clipboard!' : 'Could not copy — please copy manually.', ok);
        } catch {
            showToast('Copy not supported — please copy manually.', false);
        }
        document.body.removeChild(ta);
    }

    // ── Expiry preset toggle ───────────────────────────────────────
    document.querySelectorAll('input[name="expiry_preset"]').forEach(r => {
        r.addEventListener('change', function () {
            document.getElementById('customHoursWrap')?.classList.toggle('d-none', this.value !== 'custom');
        });
    });

    // ── Max uses preset toggle ─────────────────────────────────────
    document.querySelectorAll('input[name="max_uses_preset"]').forEach(r => {
        r.addEventListener('change', function () {
            document.getElementById('customUsesWrap')?.classList.toggle('d-none', this.value !== 'custom');
            document.getElementById('unlimitedHint')?.classList.toggle('d-none', this.value !== 'unlimited');
        });
    });

    // ── Reset form each time the New Invitation modal opens ───────
    // The button now uses data-bs-toggle/data-bs-target (no JS needed to open it).
    // We just listen for Bootstrap's show.bs.modal event to reset fields.
    document.getElementById('invModal')?.addEventListener('show.bs.modal', () => {
        document.getElementById('invErrors')?.classList.add('d-none');
        const emailEl = document.getElementById('invEmail');
        const noteEl  = document.getElementById('invNote');
        if (emailEl) emailEl.value = '';
        if (noteEl)  noteEl.value  = '';
        const exp24 = document.querySelector('input[name="expiry_preset"][value="24"]');
        const uses1 = document.querySelector('input[name="max_uses_preset"][value="1"]');
        if (exp24) exp24.checked = true;
        if (uses1) uses1.checked = true;
        document.getElementById('customHoursWrap')?.classList.add('d-none');
        document.getElementById('customUsesWrap')?.classList.add('d-none');
        document.getElementById('unlimitedHint')?.classList.add('d-none');
    });

    // ── Create invitation ──────────────────────────────────────────
    document.getElementById('btnCreate')?.addEventListener('click', async () => {
        document.getElementById('invErrors')?.classList.add('d-none');

        const expiryPreset = document.querySelector('input[name="expiry_preset"]:checked')?.value;
        let hours = parseInt(expiryPreset);
        if (expiryPreset === 'custom') {
            hours = parseInt(document.getElementById('customHours')?.value);
            if (!hours || hours < 1 || hours > 720) {
                showErr('Please enter a valid expiry between 1 and 720 hours.');
                return;
            }
        }
        if (!expiryPreset) { showErr('Please select an expiry duration.'); return; }

        const usesPreset = document.querySelector('input[name="max_uses_preset"]:checked')?.value;
        let maxUses = null;
        if (usesPreset === 'custom') {
            maxUses = parseInt(document.getElementById('customUses')?.value);
            if (!maxUses || maxUses < 1 || maxUses > 10000) {
                showErr('Please enter a valid number of uses between 1 and 10,000.');
                return;
            }
        } else if (usesPreset && usesPreset !== 'unlimited') {
            maxUses = parseInt(usesPreset);
        }

        const btn = document.getElementById('btnCreate');
        btn.disabled = true;
        document.getElementById('createSpinner')?.classList.remove('d-none');

        try {
            const res = await api('/invitations', 'POST', {
                hours,
                max_uses: maxUses,
                email: document.getElementById('invEmail')?.value.trim() || null,
                note:  document.getElementById('invNote')?.value.trim()  || null,
            });

            btn.disabled = false;
            document.getElementById('createSpinner')?.classList.add('d-none');

            if (res.success) {
                modal('invModal').hide();

                const urlEl = document.getElementById('generatedUrl');
                if (urlEl) urlEl.value = res.invitation.url;

                const expiryEl = document.getElementById('generatedExpiry');
                if (expiryEl) expiryEl.textContent =
                    `Expires: ${res.invitation.expires_at} (${res.invitation.expires_in})`;

                const capLabel = res.invitation.max_uses === null
                    ? 'Unlimited uses — anyone with this link can register until it expires.'
                    : `Up to <strong>${res.invitation.max_uses}</strong> people can register using this link.`;
                const capEl = document.getElementById('linkCapacity');
                if (capEl) capEl.innerHTML = `<i class="bi bi-people me-2"></i>${capLabel}`;

                modal('linkModal').show();
                setTimeout(() => location.reload(), 5000);
            } else {
                const errs = res.errors
                    ? Object.values(res.errors).flat().map(e => `<div>${e}</div>`).join('')
                    : `<div>${res.message || 'Failed to create invitation.'}</div>`;
                showErr(errs, true);
            }
        } catch (e) {
            btn.disabled = false;
            document.getElementById('createSpinner')?.classList.add('d-none');
            showErr('Unexpected error: ' + (e.message || 'Please try again.'));
        }
    });

    // ── Copy generated link (inside linkModal) ─────────────────────
    document.getElementById('btnCopyGenerated')?.addEventListener('click', () => {
        copyToClipboard(document.getElementById('generatedUrl')?.value ?? '');
    });

    // ── Copy link from table row ───────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-copy');
        if (btn) copyToClipboard(btn.dataset.url ?? '');
    });

    // ── Open revoke modal ──────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-revoke');
        if (btn) {
            revokingId = btn.dataset.id;
            modal('revokeModal').show();
        }
    });

    // ── Confirm revoke ─────────────────────────────────────────────
    document.getElementById('btnConfirmRevoke')?.addEventListener('click', async () => {
        const btn = document.getElementById('btnConfirmRevoke');
        btn.disabled = true;
        document.getElementById('revokeSpinner')?.classList.remove('d-none');

        try {
            const res = await api(`/invitations/${revokingId}`, 'DELETE');
            btn.disabled = false;
            document.getElementById('revokeSpinner')?.classList.add('d-none');

            modal('revokeModal').hide();
            if (res.success) {
                showToast(res.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(res.message || 'Could not revoke.', false);
            }
        } catch (e) {
            btn.disabled = false;
            document.getElementById('revokeSpinner')?.classList.add('d-none');
            showToast('Unexpected error.', false);
        }
    });

    }); // end DOMContentLoaded
    </script>
    @endpush

</x-app-layout>
