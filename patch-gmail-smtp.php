<?php
/**
 * Gmail SMTP UI Patch — run once with: php patch-gmail-smtp.php
 * Then delete this file.
 */

$file = __DIR__ . '/resources/views/settings/index.blade.php';

if (!file_exists($file)) {
    die("❌ File not found: $file\n");
}

$content = file_get_contents($file);
if ($content === false) {
    die("❌ Cannot read file (check permissions): $file\n");
}

// ── Patch 1: Replace Gmail preset button + add helper section + validation div ──

$oldPresets = <<<'OLD'
                    {{-- ── Quick Presets ──────────────────────────────────── --}}
                    <div class="hrms-card p-4 mb-3">
                        <div class="fw-semibold mb-2" style="font-size:.85rem;">Quick Presets</div>
                        <div class="d-flex flex-wrap gap-2" id="smtpPresets">
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="smtp.gmail.com" data-port="587" data-enc="tls">
                                <i class="bi bi-google me-1"></i>Gmail
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="smtp.office365.com" data-port="587" data-enc="tls">
                                <i class="bi bi-microsoft me-1"></i>Outlook / Microsoft 365
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="smtp.sendgrid.net" data-port="587" data-enc="tls">
                                <i class="bi bi-lightning-charge me-1"></i>SendGrid
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="smtp.mailgun.org" data-port="587" data-enc="tls">
                                <i class="bi bi-envelope-paper me-1"></i>Mailgun
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="sandbox.smtp.mailtrap.io" data-port="2525" data-enc="tls">
                                <i class="bi bi-bug me-1"></i>Mailtrap (dev)
                            </button>
                        </div>
                        <div class="form-text mt-1">Clicking a preset fills Host, Port, and Encryption — you still need to enter credentials.</div>
                    </div>
OLD;

$newPresets = <<<'NEW'
                    {{-- ── Quick Presets ──────────────────────────────────── --}}
                    <div class="hrms-card p-4 mb-3">
                        <div class="fw-semibold mb-2" style="font-size:.85rem;">Quick Presets</div>
                        <div class="d-flex flex-wrap gap-2" id="smtpPresets">
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="smtp.gmail.com" data-port="587" data-enc="tls" data-provider="gmail">
                                <svg width="13" height="13" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" class="me-1" style="vertical-align:-.15em;"><path fill="#4285F4" d="M44.5 20H24v8.5h11.8C34.7 33.9 30.1 37 24 37c-7.2 0-13-5.8-13-13s5.8-13 13-13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 11.8 2 2 11.8 2 24s9.8 22 22 22c11 0 21-8 21-22 0-1.3-.2-2.7-.5-4z"/><path fill="#34A853" d="M6.3 14.7l7 5.1C15.1 16.5 19.2 14 24 14c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 16.3 2 9.7 7.4 6.3 14.7z"/><path fill="#FBBC05" d="M24 46c5.8 0 10.7-1.9 14.3-5.1l-6.6-5.4C29.7 37.2 27 38 24 38c-6 0-11.1-4.1-12.9-9.6l-7 5.4C7.7 41.5 15.3 46 24 46z"/><path fill="#EA4335" d="M44.5 20H24v8.5h11.8c-.9 2.5-2.6 4.6-4.9 6L37.5 41C41.4 37.4 44 32 44 26c0-1.3-.2-2.7-.5-4z"/></svg>Gmail
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="smtp.office365.com" data-port="587" data-enc="tls" data-provider="outlook">
                                <i class="bi bi-microsoft me-1"></i>Outlook / M365
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="smtp.sendgrid.net" data-port="587" data-enc="tls" data-provider="sendgrid">
                                <i class="bi bi-lightning-charge me-1"></i>SendGrid
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="smtp.mailgun.org" data-port="587" data-enc="tls" data-provider="mailgun">
                                <i class="bi bi-envelope-paper me-1"></i>Mailgun
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="sandbox.smtp.mailtrap.io" data-port="2525" data-enc="tls" data-provider="mailtrap">
                                <i class="bi bi-bug me-1"></i>Mailtrap (dev)
                            </button>
                        </div>
                        <div class="form-text mt-1">Clicking a preset fills Host, Port, and Encryption — you still need to enter credentials.</div>
                    </div>

                    {{-- ── Gmail contextual helper ──────────────────────────── --}}
                    <div id="gmailHelper" class="mb-3 d-none">
                        <div class="rounded-3 p-0 overflow-hidden" style="border:1.5px solid #4285f4;">
                            <div class="d-flex align-items-center justify-content-between px-4 py-3"
                                 style="background:linear-gradient(90deg,#e8f0fe,#f0f9f0);">
                                <div class="d-flex align-items-center gap-2">
                                    <svg width="20" height="20" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><path fill="#4285F4" d="M44.5 20H24v8.5h11.8C34.7 33.9 30.1 37 24 37c-7.2 0-13-5.8-13-13s5.8-13 13-13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 11.8 2 2 11.8 2 24s9.8 22 22 22c11 0 21-8 21-22 0-1.3-.2-2.7-.5-4z"/><path fill="#34A853" d="M6.3 14.7l7 5.1C15.1 16.5 19.2 14 24 14c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 16.3 2 9.7 7.4 6.3 14.7z"/><path fill="#FBBC05" d="M24 46c5.8 0 10.7-1.9 14.3-5.1l-6.6-5.4C29.7 37.2 27 38 24 38c-6 0-11.1-4.1-12.9-9.6l-7 5.4C7.7 41.5 15.3 46 24 46z"/><path fill="#EA4335" d="M44.5 20H24v8.5h11.8c-.9 2.5-2.6 4.6-4.9 6L37.5 41C41.4 37.4 44 32 44 26c0-1.3-.2-2.7-.5-4z"/></svg>
                                    <span class="fw-bold" style="font-size:.9rem;color:#1a237e;">Gmail Selected — App Password Required</span>
                                </div>
                                <a href="{{ route('settings.smtp.gmail-guide') }}" class="btn btn-sm" style="background:#4285f4;color:#fff;border:none;font-size:.78rem;">
                                    <i class="bi bi-book me-1"></i>Full Setup Guide
                                </a>
                            </div>
                            <div class="px-4 py-3" style="background:#fff;">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="d-flex gap-2 align-items-start p-3 rounded" style="background:#fff3e0;border:1px solid #ffb74d;">
                                            <i class="bi bi-exclamation-triangle-fill text-warning mt-1 flex-shrink-0"></i>
                                            <div class="small">
                                                <strong>Do NOT use your regular Gmail password.</strong>
                                                Google requires an <strong>App Password</strong> — a 16-character code you generate in your Google account. Regular passwords will fail with error 535.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="small fw-semibold mb-2">Quick setup steps:</div>
                                        <div class="d-flex flex-column gap-2" style="font-size:.82rem;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width:22px;height:22px;background:#4285f4;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.7rem;flex-shrink:0;">1</div>
                                                <span>Enable <strong>2-Step Verification</strong> on your Google account</span>
                                                <a href="https://myaccount.google.com/signinoptions/two-step-verification" target="_blank" rel="noopener" class="ms-auto text-primary text-nowrap" style="font-size:.75rem;">Open <i class="bi bi-box-arrow-up-right"></i></a>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width:22px;height:22px;background:#34a853;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.7rem;flex-shrink:0;">2</div>
                                                <span>Generate an <strong>App Password</strong> for "HRMS System"</span>
                                                <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener" class="ms-auto text-primary text-nowrap" style="font-size:.75rem;">Open <i class="bi bi-box-arrow-up-right"></i></a>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width:22px;height:22px;background:#fbbc05;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.7rem;flex-shrink:0;">3</div>
                                                <span>Paste the 16-character code into <strong>Password</strong> below (no spaces)</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="small fw-semibold mb-2">Settings to use:</div>
                                        <div class="d-flex flex-column gap-1" style="font-size:.78rem;">
                                            <div class="d-flex justify-content-between p-2 rounded" style="background:#f8f9fa;"><span class="text-muted">Host</span><code>smtp.gmail.com</code></div>
                                            <div class="d-flex justify-content-between p-2 rounded" style="background:#f8f9fa;"><span class="text-muted">Port / Enc</span><span><code>587</code>/TLS or <code>465</code>/SSL</span></div>
                                            <div class="d-flex justify-content-between p-2 rounded" style="background:#f8f9fa;"><span class="text-muted">Username</span><code>you@gmail.com</code></div>
                                            <div class="d-flex justify-content-between p-2 rounded" style="background:#fff9e6;border:1px solid #ffecb3;"><span class="text-muted">Password</span><span class="text-warning fw-semibold">App Password only</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Validation warnings (populated by JS) ───────────── --}}
                    <div id="smtpValidationWarnings" class="mb-3"></div>
NEW;

// ── Patch 2: Replace the SMTP presets JS block with enhanced version ──

$oldJs = <<<'OLDJS'
    // ── SMTP presets ───────────────────────────────────────────────
    document.getElementById('smtpPresets')?.addEventListener('click', e => {
        const btn = e.target.closest('.preset-btn');
        if (!btn) return;
        document.getElementById('smtp_host').value       = btn.dataset.host;
        document.getElementById('smtp_port').value       = btn.dataset.port;
        document.getElementById('smtp_encryption').value = btn.dataset.enc;
        // Highlight selected preset
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('btn-primary','border-primary'));
        btn.classList.add('btn-primary');
        btn.classList.remove('btn-outline-secondary');
        setTimeout(() => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-secondary');
        }, 1200);
        showToast(`Preset applied: ${btn.dataset.host}`, true);
    });

    // ── SMTP encryption → auto-fill port ──────────────────────────
    document.getElementById('smtp_encryption')?.addEventListener('change', function() {
        const portMap = { tls: 587, ssl: 465, starttls: 587, '': 25 };
        const port = portMap[this.value];
        if (port !== undefined) document.getElementById('smtp_port').value = port;
    });
OLDJS;

$newJs = <<<'NEWJS'
    // ── SMTP presets ───────────────────────────────────────────────
    let activeProvider = '{{ str_contains($smtp["host"] ?? "", "gmail.com") ? "gmail" : "" }}';

    function showGmailHelper(show) {
        const el = document.getElementById('gmailHelper');
        if (el) el.classList.toggle('d-none', !show);
    }

    function runSmtpValidation() {
        const host = document.getElementById('smtp_host')?.value.trim() || '';
        const user = document.getElementById('smtp_username')?.value.trim() || '';
        const pass = document.getElementById('smtp_password')?.value || '';
        const enc  = document.getElementById('smtp_encryption')?.value || '';
        const port = parseInt(document.getElementById('smtp_port')?.value || '587');
        const container = document.getElementById('smtpValidationWarnings');
        if (!container) return;

        const warnings = [];

        // Gmail-specific checks
        if (host.includes('gmail.com')) {
            if (user && !user.includes('@gmail.com') && !user.includes('@googlemail.com')) {
                warnings.push({ type: 'warning', icon: 'bi-exclamation-triangle-fill', text: '<strong>Username looks wrong for Gmail.</strong> It should be your full Gmail address, e.g. <code>name@gmail.com</code>.' });
            }
            if (pass && pass !== '••••••••' && (pass.replace(/\s/g,'').length !== 16 || /[^a-z]/i.test(pass.replace(/\s/g,'')))) {
                warnings.push({ type: 'warning', icon: 'bi-key-fill', text: '<strong>Password might not be a Gmail App Password.</strong> App Passwords are exactly 16 lowercase letters (spaces allowed). Make sure you\'re not using your regular Gmail password.' });
            }
            if (enc === '') {
                warnings.push({ type: 'danger', icon: 'bi-shield-x', text: '<strong>Encryption is set to None — Gmail requires TLS or SSL.</strong> Set Port to 587 + TLS, or 465 + SSL.' });
            }
            if ((enc === 'tls' || enc === 'starttls') && port === 465) {
                warnings.push({ type: 'warning', icon: 'bi-info-circle-fill', text: 'Port 465 is for SSL, not TLS. Either switch Port to <strong>587</strong> or change Encryption to <strong>SSL</strong>.' });
            }
            if (enc === 'ssl' && port === 587) {
                warnings.push({ type: 'warning', icon: 'bi-info-circle-fill', text: 'Port 587 is for TLS, not SSL. Either switch Port to <strong>465</strong> or change Encryption to <strong>TLS</strong>.' });
            }
        }

        container.innerHTML = warnings.map(w =>
            `<div class="alert alert-${w.type} d-flex gap-2 align-items-start py-2 px-3 mb-2" style="font-size:.84rem;border-radius:8px;">
                <i class="bi ${w.icon} flex-shrink-0 mt-1"></i><div>${w.text}</div>
             </div>`
        ).join('');
    }

    // Auto-show Gmail helper on page load if Gmail is already configured
    if (activeProvider === 'gmail') showGmailHelper(true);

    // Auto-apply Gmail preset if flag set from guide page
    if (sessionStorage.getItem('smtpAutoPreset') === 'gmail') {
        sessionStorage.removeItem('smtpAutoPreset');
        setTimeout(() => {
            document.getElementById('smtp_host').value       = 'smtp.gmail.com';
            document.getElementById('smtp_port').value       = '587';
            document.getElementById('smtp_encryption').value = 'tls';
            activeProvider = 'gmail';
            showGmailHelper(true);
            // Scroll to SMTP panel
            document.querySelector('#panel-smtp')?.scrollIntoView({ behavior:'smooth', block:'start' });
            showToast('Gmail preset applied. Enter your credentials below.', true);
        }, 400);
    }

    document.getElementById('smtpPresets')?.addEventListener('click', e => {
        const btn = e.target.closest('.preset-btn');
        if (!btn) return;
        document.getElementById('smtp_host').value       = btn.dataset.host;
        document.getElementById('smtp_port').value       = btn.dataset.port;
        document.getElementById('smtp_encryption').value = btn.dataset.enc;
        activeProvider = btn.dataset.provider || '';

        // Show/hide Gmail helper
        showGmailHelper(activeProvider === 'gmail');

        // Brief highlight
        document.querySelectorAll('.preset-btn').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        btn.classList.add('btn-primary');
        btn.classList.remove('btn-outline-secondary');
        setTimeout(() => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-secondary');
        }, 1400);

        runSmtpValidation();
        showToast(`Preset applied: ${btn.dataset.host}`, true);
    });

    // ── SMTP encryption → auto-fill port ──────────────────────────
    document.getElementById('smtp_encryption')?.addEventListener('change', function() {
        const portMap = { tls: 587, ssl: 465, starttls: 587, '': 25 };
        const port = portMap[this.value];
        if (port !== undefined) document.getElementById('smtp_port').value = port;
        runSmtpValidation();
    });

    // Run validation on field change
    ['smtp_host','smtp_username','smtp_password','smtp_port'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', runSmtpValidation);
        document.getElementById(id)?.addEventListener('change', runSmtpValidation);
    });

    // Initial validation run
    runSmtpValidation();
NEWJS;

// Apply patches
$patched = $content;

if (str_contains($content, trim(explode("\n", $oldPresets)[2]))) {
    $patched = str_replace($oldPresets, $newPresets, $patched);
    echo "✅ Patch 1 (presets + Gmail helper) applied.\n";
} else {
    echo "⚠️  Patch 1 could not find the target — already patched or content changed.\n";
}

if (str_contains($content, '// ── SMTP presets ───────────────────────────────────────────────')) {
    $patched = str_replace($oldJs, $newJs, $patched);
    echo "✅ Patch 2 (JavaScript) applied.\n";
} else {
    echo "⚠️  Patch 2 could not find the JS target — already patched or content changed.\n";
}

if ($patched === $content) {
    echo "ℹ️  No changes made (file may already be patched).\n";
    exit(0);
}

// Write back
if (file_put_contents($file, $patched) !== false) {
    echo "✅ File saved successfully.\n";
    echo "🗑️  You can now delete this script: " . __FILE__ . "\n";
} else {
    echo "❌ Failed to write file — check permissions on: $file\n";
}
