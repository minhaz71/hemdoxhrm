<x-app-layout>
    <x-slot name="title">Gmail SMTP Setup Guide</x-slot>

    @push('styles')
    <style>
    .guide-step-num {
        width: 32px; height: 32px;
        background: linear-gradient(135deg, #4285f4, #34a853);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: .9rem;
        flex-shrink: 0;
    }
    .guide-step-connector {
        width: 2px; background: #e9ecef;
        margin: 4px auto; flex-shrink: 0;
        height: 24px;
    }
    .copy-btn { cursor: pointer; }
    .copy-btn:active { transform: scale(.95); }
    .issue-card { border-left: 4px solid; }
    .issue-card.error-535  { border-color: #dc3545; }
    .issue-card.error-534  { border-color: #fd7e14; }
    .issue-card.error-conn { border-color: #6c757d; }
    .issue-card.error-ssl  { border-color: #0dcaf0; }
    .issue-card.error-ws   { border-color: #6f42c1; }
    .breadcrumb { background: none; padding: 0; }
    .step-card { border: 1px solid #e9ecef; border-radius: 12px; overflow: hidden; }
    .step-card-header { background: linear-gradient(135deg,#f8f9ff,#eef2ff); padding: 16px 20px; border-bottom: 1px solid #e9ecef; }
    .value-pill {
        font-family: monospace; font-size: .85rem;
        background: #f0f4ff; border: 1px solid #c7d2fe;
        border-radius: 6px; padding: 3px 10px;
        color: #3730a3; display: inline-flex; align-items: center; gap: 6px;
    }
    .checklist-item { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; }
    .checklist-item input[type=checkbox] { width: 18px; height: 18px; margin-top: 2px; flex-shrink: 0; accent-color: #34a853; cursor: pointer; }
    </style>
    @endpush

    {{-- ── Breadcrumb ──────────────────────────────────────────────── --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('settings.index') }}#panel-smtp" class="text-decoration-none">
                    <i class="bi bi-gear me-1"></i>Settings
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('settings.index') }}#panel-smtp" class="text-decoration-none">Email / SMTP</a>
            </li>
            <li class="breadcrumb-item active">Gmail Setup Guide</li>
        </ol>
    </nav>

    {{-- ── Page header ─────────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            {{-- Google "G" logo colours --}}
            <div style="width:52px;height:52px;border-radius:14px;background:#fff;border:2px solid #e9ecef;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.08);">
                <svg width="28" height="28" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#4285F4" d="M44.5 20H24v8.5h11.8C34.7 33.9 30.1 37 24 37c-7.2 0-13-5.8-13-13s5.8-13 13-13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 11.8 2 2 11.8 2 24s9.8 22 22 22c11 0 21-8 21-22 0-1.3-.2-2.7-.5-4z"/>
                    <path fill="#34A853" d="M6.3 14.7l7 5.1C15.1 16.5 19.2 14 24 14c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 16.3 2 9.7 7.4 6.3 14.7z"/>
                    <path fill="#FBBC05" d="M24 46c5.8 0 10.7-1.9 14.3-5.1l-6.6-5.4C29.7 37.2 27 38 24 38c-6 0-11.1-4.1-12.9-9.6l-7 5.4C7.7 41.5 15.3 46 24 46z"/>
                    <path fill="#EA4335" d="M44.5 20H24v8.5h11.8c-.9 2.5-2.6 4.6-4.9 6L37.5 41C41.4 37.4 44 32 44 26c0-1.3-.2-2.7-.5-4z"/>
                </svg>
            </div>
            <div>
                <h5 class="fw-bold mb-0">Gmail SMTP Setup Guide</h5>
                <small class="text-muted">Connect your Gmail or Google Workspace account to send system emails</small>
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($isGmailConfig)
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                <i class="bi bi-check-circle-fill me-1"></i>Gmail already configured
            </span>
            @endif
            <a href="{{ route('settings.index') }}#panel-smtp" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Settings
            </a>
        </div>
    </div>

    <div class="row g-4">

        {{-- ══ Left: Main guide ════════════════════════════════════════ --}}
        <div class="col-lg-8">

            {{-- Important notice --}}
            <div class="alert alert-warning d-flex gap-3 align-items-start mb-4" style="border-radius:10px;border-left:4px solid #f59e0b;">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0 mt-1"></i>
                <div>
                    <div class="fw-semibold">App Password Required — Regular Gmail Password Won't Work</div>
                    <div class="small mt-1">
                        Google disabled "less secure app access" in 2022. You <strong>must</strong> use an
                        <strong>App Password</strong> (a 16-character code generated by Google) instead of your regular Gmail password.
                        This requires 2-Step Verification to be active on your Google account.
                    </div>
                </div>
            </div>

            {{-- ── Step 1: 2FA ─────────────────────────────────────── --}}
            <div class="step-card mb-4">
                <div class="step-card-header d-flex align-items-center gap-3">
                    <div class="guide-step-num">1</div>
                    <div>
                        <div class="fw-bold">Enable 2-Step Verification on your Google Account</div>
                        <small class="text-muted">Required before you can generate an App Password</small>
                    </div>
                    <span class="badge bg-danger-subtle text-danger ms-auto">Required first</span>
                </div>
                <div class="p-4">
                    <p class="text-muted small mb-3">Skip this step if 2FA is already enabled on your Google account.</p>

                    <ol class="ps-3" style="font-size:.9rem;line-height:1.9;">
                        <li>Go to your <a href="https://myaccount.google.com/security" target="_blank" rel="noopener" class="text-primary fw-semibold">Google Account Security page <i class="bi bi-box-arrow-up-right" style="font-size:.7rem;"></i></a></li>
                        <li>Under <strong>"How you sign in to Google"</strong>, click <strong>2-Step Verification</strong></li>
                        <li>Click <strong>Get started</strong> and follow the on-screen prompts</li>
                        <li>Choose your second factor: Google prompt, Authenticator app, or SMS</li>
                        <li>Click <strong>Turn On</strong> to confirm</li>
                    </ol>

                    <div class="mt-3 p-3 bg-success-subtle rounded border border-success-subtle">
                        <div class="fw-semibold text-success small"><i class="bi bi-check-circle-fill me-1"></i>How to verify 2FA is active</div>
                        <div class="small text-success-emphasis mt-1">
                            Visit <strong>myaccount.google.com/security</strong> — the "2-Step Verification" row should show <strong>"On"</strong> with a green checkmark.
                        </div>
                    </div>

                    <div class="mt-3 text-center">
                        <a href="https://myaccount.google.com/signinoptions/two-step-verification" target="_blank" rel="noopener"
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-shield-lock me-1"></i>Open Google 2-Step Verification
                            <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.7rem;"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── Step 2: App Password ─────────────────────────────── --}}
            <div class="step-card mb-4">
                <div class="step-card-header d-flex align-items-center gap-3">
                    <div class="guide-step-num">2</div>
                    <div>
                        <div class="fw-bold">Generate a Gmail App Password</div>
                        <small class="text-muted">A 16-character password used only for this application</small>
                    </div>
                    <span class="badge bg-warning-subtle text-warning ms-auto border border-warning-subtle">Key step</span>
                </div>
                <div class="p-4">
                    <ol class="ps-3" style="font-size:.9rem;line-height:1.9;">
                        <li>
                            Go to your <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener" class="text-primary fw-semibold">Google App Passwords page <i class="bi bi-box-arrow-up-right" style="font-size:.7rem;"></i></a>
                            <br><small class="text-muted">(You'll need to sign in and verify with 2FA)</small>
                        </li>
                        <li>
                            In the <strong>"App name"</strong> field, type a descriptive name<br>
                            <small class="text-muted">Example: <span class="value-pill">HRMS System</span></small>
                        </li>
                        <li>Click <strong>Create</strong></li>
                        <li>
                            Google shows a <strong>16-character password</strong> in a yellow box<br>
                            <small class="text-muted">Format: <span class="value-pill">xxxx xxxx xxxx xxxx</span> — copy it immediately, it won't be shown again</small>
                        </li>
                        <li>Store the password safely — paste it in the SMTP Password field below</li>
                    </ol>

                    <div class="mt-3 p-3 bg-warning-subtle rounded border border-warning-subtle">
                        <div class="fw-semibold small"><i class="bi bi-lightbulb-fill text-warning me-1"></i>Important tips</div>
                        <ul class="small mb-0 mt-1 ps-3" style="line-height:1.8;">
                            <li>Remove the spaces when entering the App Password — enter all 16 characters without spaces</li>
                            <li>Create one App Password per app/service — don't reuse passwords across systems</li>
                            <li>If you reset your Google password, all App Passwords are automatically revoked</li>
                            <li>The App Password page is only accessible when 2FA is enabled</li>
                        </ul>
                    </div>

                    <div class="mt-3 text-center">
                        <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener"
                           class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-key me-1"></i>Open Google App Passwords
                            <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.7rem;"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── Step 3: Configure ────────────────────────────────── --}}
            <div class="step-card mb-4">
                <div class="step-card-header d-flex align-items-center gap-3">
                    <div class="guide-step-num">3</div>
                    <div>
                        <div class="fw-bold">Enter Settings in the SMTP Panel</div>
                        <small class="text-muted">Copy these exact values into the Email / SMTP settings tab</small>
                    </div>
                    <span class="badge bg-info-subtle text-info ms-auto border border-info-subtle">Almost done</span>
                </div>
                <div class="p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" style="font-size:.875rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40%">Field</th>
                                    <th>Value to enter</th>
                                    <th style="width:80px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold">SMTP Host</td>
                                    <td><span class="value-pill" id="val-host">smtp.gmail.com</span></td>
                                    <td><button class="btn btn-outline-secondary btn-sm copy-btn" onclick="copyVal('smtp.gmail.com', this)"><i class="bi bi-clipboard"></i></button></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Port</td>
                                    <td>
                                        <span class="value-pill me-2">587</span><small class="text-muted">(TLS — recommended)</small><br>
                                        <span class="value-pill me-2 mt-1" style="display:inline-block;">465</span><small class="text-muted">(SSL — alternative)</small>
                                    </td>
                                    <td><button class="btn btn-outline-secondary btn-sm copy-btn" onclick="copyVal('587', this)"><i class="bi bi-clipboard"></i></button></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Encryption</td>
                                    <td><span class="value-pill">TLS</span> (for port 587) &nbsp;/&nbsp; <span class="value-pill">SSL</span> (for port 465)</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Username</td>
                                    <td><span class="value-pill">your.address@gmail.com</span> <small class="text-muted ms-1">— your full Gmail address</small></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Password</td>
                                    <td><span class="value-pill">16-character App Password</span> <small class="text-muted ms-1">— no spaces</small></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">From Address</td>
                                    <td><span class="value-pill">same as Username</span> <small class="text-muted ms-1">— must match your Gmail address</small></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            After saving, use the <strong>Send Test Email</strong> button to verify the connection.
                        </div>
                        <a href="{{ route('settings.index') }}#panel-smtp" id="btnAutoApplyGmail"
                           class="btn btn-primary btn-sm">
                            <i class="bi bi-google me-1"></i>Apply Gmail Preset in Settings
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── Step 4: Validation & Test ────────────────────────── --}}
            <div class="step-card mb-4">
                <div class="step-card-header d-flex align-items-center gap-3">
                    <div class="guide-step-num">4</div>
                    <div>
                        <div class="fw-bold">Verify Your Configuration</div>
                        <small class="text-muted">Use this checklist before sending a test email</small>
                    </div>
                </div>
                <div class="p-4">
                    <div class="mb-3 fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;">Pre-flight checklist</div>
                    <div id="gmailChecklist">
                        <div class="checklist-item">
                            <input type="checkbox" id="chk1">
                            <label for="chk1" style="font-size:.9rem;cursor:pointer;">
                                <strong>2-Step Verification is enabled</strong> on the Google account you're using
                            </label>
                        </div>
                        <div class="checklist-item">
                            <input type="checkbox" id="chk2">
                            <label for="chk2" style="font-size:.9rem;cursor:pointer;">
                                <strong>App Password has been generated</strong> — 16 characters, no spaces
                            </label>
                        </div>
                        <div class="checklist-item">
                            <input type="checkbox" id="chk3">
                            <label for="chk3" style="font-size:.9rem;cursor:pointer;">
                                <strong>SMTP Host</strong> is set to <code>smtp.gmail.com</code>
                            </label>
                        </div>
                        <div class="checklist-item">
                            <input type="checkbox" id="chk4">
                            <label for="chk4" style="font-size:.9rem;cursor:pointer;">
                                <strong>Port</strong> is <code>587</code> (TLS) or <code>465</code> (SSL)
                            </label>
                        </div>
                        <div class="checklist-item">
                            <input type="checkbox" id="chk5">
                            <label for="chk5" style="font-size:.9rem;cursor:pointer;">
                                <strong>Username</strong> is your full Gmail address (e.g. <code>name@gmail.com</code>)
                            </label>
                        </div>
                        <div class="checklist-item">
                            <input type="checkbox" id="chk6">
                            <label for="chk6" style="font-size:.9rem;cursor:pointer;">
                                <strong>Password</strong> is the App Password, NOT your regular Gmail password
                            </label>
                        </div>
                        <div class="checklist-item">
                            <input type="checkbox" id="chk7">
                            <label for="chk7" style="font-size:.9rem;cursor:pointer;">
                                <strong>From Address</strong> matches the Gmail username exactly
                            </label>
                        </div>
                    </div>

                    <div id="checklistAllDone" class="mt-3 p-3 bg-success-subtle rounded border border-success-subtle d-none">
                        <div class="fw-semibold text-success"><i class="bi bi-check-circle-fill me-2"></i>All checks passed! You're ready to test.</div>
                        <div class="mt-2">
                            <a href="{{ route('settings.index') }}#panel-smtp" class="btn btn-success btn-sm">
                                <i class="bi bi-send-check me-1"></i>Go to Settings & Send Test Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Troubleshooting ──────────────────────────────────── --}}
            <div class="hrms-card p-4 mb-4">
                <h6 class="fw-bold mb-4"><i class="bi bi-wrench-adjustable me-2 text-danger"></i>SMTP Troubleshooting Guide</h6>

                {{-- Error 535 --}}
                <div class="issue-card error-535 bg-danger-subtle rounded p-3 mb-3">
                    <div class="d-flex align-items-start gap-3">
                        <div>
                            <div class="fw-bold text-danger small">
                                <i class="bi bi-x-circle-fill me-1"></i>Error 535 — Authentication Failed / Credentials Rejected
                            </div>
                            <div class="small mt-2 mb-2"><strong>Symptoms:</strong> "535 5.7.8 Username and Password not accepted" or similar.</div>
                            <div class="small fw-semibold mb-1">Fixes to try:</div>
                            <ul class="small mb-0 ps-3" style="line-height:1.85;">
                                <li>You are likely using your <strong>regular Gmail password</strong> — it won't work. Generate an <strong>App Password</strong> in Step 2 above.</li>
                                <li>Double-check the App Password was copied correctly (16 characters, no spaces).</li>
                                <li>Make sure the App Password was generated for the same account as the Username field.</li>
                                <li>If you recently changed your Google account password, all App Passwords are revoked — generate a new one.</li>
                                <li>Confirm 2-Step Verification is still active on the account (it can be accidentally disabled).</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Error 534 --}}
                <div class="issue-card error-534 bg-warning-subtle rounded p-3 mb-3">
                    <div class="fw-bold text-warning small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Error 534 — Application Not Allowed (Less Secure Apps)
                    </div>
                    <div class="small mt-2 mb-2"><strong>Symptoms:</strong> "534 5.7.9 Application-specific password required".</div>
                    <div class="small fw-semibold mb-1">Fixes:</div>
                    <ul class="small mb-0 ps-3" style="line-height:1.85;">
                        <li>This error confirms you need an <strong>App Password</strong> — follow Step 2 above.</li>
                        <li>Google permanently disabled "Less Secure Apps" access in May 2022. App Passwords are the only method for personal Gmail accounts.</li>
                    </ul>
                </div>

                {{-- Connection refused --}}
                <div class="issue-card error-conn bg-secondary-subtle rounded p-3 mb-3">
                    <div class="fw-bold small">
                        <i class="bi bi-plug me-1"></i>Connection Refused / Timeout / Host Not Found
                    </div>
                    <div class="small mt-2 mb-2"><strong>Symptoms:</strong> "Connection refused", "getaddrinfo failed", or "Connection timed out".</div>
                    <div class="small fw-semibold mb-1">Fixes:</div>
                    <ul class="small mb-0 ps-3" style="line-height:1.85;">
                        <li>Verify the SMTP host is exactly <code>smtp.gmail.com</code> (not <code>mail.gmail.com</code> or <code>imap.gmail.com</code>).</li>
                        <li>Port <strong>587</strong> is blocked by some hosting providers — try <strong>465</strong> (SSL) instead.</li>
                        <li>Many shared hosting servers block outbound port 465/587 by default — contact your host to whitelist them, or use a transactional email service (SendGrid, Mailgun) as an alternative.</li>
                        <li>Check that your server's firewall allows outbound connections to <code>smtp.gmail.com</code>.</li>
                    </ul>
                </div>

                {{-- SSL error --}}
                <div class="issue-card error-ssl bg-info-subtle rounded p-3 mb-3">
                    <div class="fw-bold text-info-emphasis small">
                        <i class="bi bi-shield-x me-1"></i>SSL / Encryption Errors
                    </div>
                    <div class="small mt-2 mb-2"><strong>Symptoms:</strong> "SSL handshake failed", "stream_socket_enable_crypto", or "TLS negotiation failed".</div>
                    <div class="small fw-semibold mb-1">Fixes:</div>
                    <ul class="small mb-0 ps-3" style="line-height:1.85;">
                        <li>If using port 587: set encryption to <strong>TLS</strong> (not SSL).</li>
                        <li>If using port 465: set encryption to <strong>SSL</strong> (not TLS).</li>
                        <li>Never set encryption to <strong>None</strong> with Gmail — all Gmail connections require encryption.</li>
                        <li>Your server's PHP OpenSSL extension may be outdated — contact your host to update it.</li>
                        <li>Try switching between TLS and STARTTLS if TLS fails.</li>
                    </ul>
                </div>

                {{-- Google Workspace --}}
                <div class="issue-card error-ws rounded p-3 mb-3" style="background:rgba(111,66,193,.06);">
                    <div class="fw-bold small" style="color:#6f42c1;">
                        <i class="bi bi-buildings me-1"></i>Google Workspace (formerly G Suite) Accounts
                    </div>
                    <div class="small mt-2 mb-2"><strong>Symptoms:</strong> App Passwords page not accessible, or "This setting is not available for your account".</div>
                    <div class="small fw-semibold mb-1">Workspace-specific notes:</div>
                    <ul class="small mb-0 ps-3" style="line-height:1.85;">
                        <li>Workspace admins must <strong>enable App Passwords</strong> in the Google Admin Console: <em>Security → Advanced security settings → Allow users to manage their access to less secure apps</em>.</li>
                        <li>Alternatively, enable <strong>SMTP relay</strong> in Google Admin Console for org-wide SMTP without App Passwords.</li>
                        <li>Your username must be the full Workspace email, e.g. <code>admin@yourcompany.com</code>.</li>
                        <li>The SMTP host remains <code>smtp.gmail.com</code> for Workspace accounts.</li>
                    </ul>
                </div>

                {{-- Daily limits --}}
                <div class="p-3 bg-light rounded border">
                    <div class="fw-semibold small"><i class="bi bi-bar-chart me-1 text-primary"></i>Gmail SMTP Sending Limits</div>
                    <div class="small mt-2 text-muted" style="line-height:1.85;">
                        Gmail imposes daily sending limits:
                        <ul class="mb-0 ps-3 mt-1">
                            <li><strong>Free Gmail:</strong> 500 emails / day</li>
                            <li><strong>Google Workspace:</strong> 2,000 emails / day</li>
                            <li>Exceeding limits causes "Daily sending quota exceeded" errors — consider transactional email services (SendGrid, Mailgun, Amazon SES) for high-volume systems.</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>

        {{-- ══ Right sidebar ═════════════════════════════════════════════ --}}
        <div class="col-lg-4">

            {{-- Quick reference card --}}
            <div class="hrms-card p-4 mb-3" style="position:sticky;top:76px;">
                <div class="fw-bold mb-3"><i class="bi bi-card-list me-2 text-primary"></i>Quick Reference</div>

                <div class="mb-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.04em;">SMTP Settings</div>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                            <span class="small text-muted">Host</span>
                            <div class="d-flex align-items-center gap-1">
                                <code class="small">smtp.gmail.com</code>
                                <button class="btn btn-link btn-sm p-0 ms-1 copy-btn" onclick="copyVal('smtp.gmail.com', this)" title="Copy">
                                    <i class="bi bi-clipboard text-muted" style="font-size:.75rem;"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                            <span class="small text-muted">Port (TLS)</span>
                            <div class="d-flex align-items-center gap-1">
                                <code class="small">587</code>
                                <button class="btn btn-link btn-sm p-0 ms-1 copy-btn" onclick="copyVal('587', this)" title="Copy">
                                    <i class="bi bi-clipboard text-muted" style="font-size:.75rem;"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                            <span class="small text-muted">Port (SSL)</span>
                            <div class="d-flex align-items-center gap-1">
                                <code class="small">465</code>
                                <button class="btn btn-link btn-sm p-0 ms-1 copy-btn" onclick="copyVal('465', this)" title="Copy">
                                    <i class="bi bi-clipboard text-muted" style="font-size:.75rem;"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                            <span class="small text-muted">Encryption</span>
                            <code class="small">TLS or SSL</code>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                            <span class="small text-muted">Username</span>
                            <code class="small">your@gmail.com</code>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                            <span class="small text-muted">Password</span>
                            <code class="small">App Password (16 chars)</code>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.04em;">Google Pages</div>
                    <div class="d-flex flex-column gap-1">
                        <a href="https://myaccount.google.com/security" target="_blank" rel="noopener"
                           class="btn btn-outline-secondary btn-sm text-start d-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock text-primary"></i>
                            Account Security
                            <i class="bi bi-box-arrow-up-right ms-auto" style="font-size:.65rem;opacity:.5;"></i>
                        </a>
                        <a href="https://myaccount.google.com/signinoptions/two-step-verification" target="_blank" rel="noopener"
                           class="btn btn-outline-secondary btn-sm text-start d-flex align-items-center gap-2">
                            <i class="bi bi-phone text-success"></i>
                            2-Step Verification
                            <i class="bi bi-box-arrow-up-right ms-auto" style="font-size:.65rem;opacity:.5;"></i>
                        </a>
                        <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener"
                           class="btn btn-outline-secondary btn-sm text-start d-flex align-items-center gap-2">
                            <i class="bi bi-key text-warning"></i>
                            App Passwords
                            <i class="bi bi-box-arrow-up-right ms-auto" style="font-size:.65rem;opacity:.5;"></i>
                        </a>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.04em;">Sending Limits</div>
                    <div class="d-flex flex-column gap-1" style="font-size:.82rem;">
                        <div class="d-flex justify-content-between p-2 bg-light rounded">
                            <span class="text-muted">Free Gmail</span>
                            <strong>500 / day</strong>
                        </div>
                        <div class="d-flex justify-content-between p-2 bg-light rounded">
                            <span class="text-muted">Google Workspace</span>
                            <strong>2,000 / day</strong>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                <div class="d-grid gap-2">
                    <a href="{{ route('settings.index') }}#panel-smtp" class="btn btn-primary btn-sm" id="btnGoApply">
                        <i class="bi bi-google me-1"></i>Apply Gmail Preset
                    </a>
                    <a href="{{ route('settings.index') }}#panel-smtp" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back to Settings
                    </a>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
    // ── Copy helper ────────────────────────────────────────────────
    function copyVal(text, btn) {
        navigator.clipboard?.writeText(text).then(() => {
            const icon = btn.querySelector('i') || btn;
            const orig = icon.className;
            icon.className = icon.className.replace('bi-clipboard','bi-check2').replace('text-muted','text-success');
            setTimeout(() => { icon.className = orig; }, 1500);
        });
    }

    // ── Checklist: show "all done" when every box is ticked ────────
    const checkboxes = document.querySelectorAll('#gmailChecklist input[type=checkbox]');
    function updateChecklist() {
        const allDone = [...checkboxes].every(cb => cb.checked);
        document.getElementById('checklistAllDone').classList.toggle('d-none', !allDone);
    }
    checkboxes.forEach(cb => cb.addEventListener('change', updateChecklist));

    // ── "Apply Gmail Preset" — store intent then redirect ──────────
    // When user lands back on settings page we detect the flag and auto-click the Gmail preset
    ['btnAutoApplyGmail','btnGoApply'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', () => {
            sessionStorage.setItem('smtpAutoPreset', 'gmail');
        });
    });
    </script>
    @endpush

</x-app-layout>
