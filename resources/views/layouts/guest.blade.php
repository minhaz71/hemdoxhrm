<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Hemdox HRMS') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --hrms-primary: {{ setting('theme_primary_color', '#4e9af1') }};
                --hrms-sidebar: {{ setting('theme_sidebar_color', '#1a1f2e') }};
            }

            * { box-sizing: border-box; }
            body {
                margin: 0;
                font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                color: #1f2937;
                background: #f4f6f9;
            }
            .guest-page {
                min-height: 100vh;
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(380px, 480px);
                background:
                    linear-gradient(135deg, rgba(26,31,46,.96), rgba(26,31,46,.82)),
                    var(--hrms-sidebar);
            }
            .guest-brand-panel {
                min-height: 100vh;
                padding: 56px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                color: #fff;
                overflow: hidden;
            }
            .guest-brand-mark {
                display: inline-flex;
                align-items: center;
                gap: 12px;
                color: #fff;
                text-decoration: none;
                font-weight: 700;
                font-size: 1.1rem;
            }
            .guest-brand-mark img {
                max-width: 170px;
                max-height: 48px;
                object-fit: contain;
            }
            .guest-brand-icon {
                width: 42px;
                height: 42px;
                border-radius: 10px;
                display: grid;
                place-items: center;
                background: var(--hrms-primary);
                color: #fff;
                font-weight: 800;
            }
            .guest-brand-copy {
                max-width: 620px;
                padding-bottom: 6vh;
            }
            .guest-brand-copy h1 {
                margin: 0 0 18px;
                font-size: clamp(2.1rem, 4.2vw, 4.4rem);
                line-height: 1;
                letter-spacing: 0;
            }
            .guest-brand-copy p {
                margin: 0;
                max-width: 560px;
                color: rgba(255,255,255,.74);
                font-size: 1.02rem;
                line-height: 1.7;
            }
            .guest-brand-meta {
                display: flex;
                gap: 18px;
                flex-wrap: wrap;
                color: rgba(255,255,255,.58);
                font-size: .86rem;
            }
            .guest-form-panel {
                min-height: 100vh;
                background: #f7f9fc;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 32px;
            }
            .guest-card {
                width: min(100%, 420px);
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                box-shadow: 0 24px 70px rgba(15,23,42,.14);
                padding: 32px;
            }
            .guest-card-header {
                margin-bottom: 24px;
            }
            .guest-card-header h2 {
                margin: 0 0 8px;
                color: #111827;
                font-size: 1.45rem;
                font-weight: 700;
                letter-spacing: 0;
            }
            .guest-card-header p {
                margin: 0;
                color: #6b7280;
                font-size: .92rem;
                line-height: 1.6;
            }
            .auth-label {
                display: block;
                margin-bottom: 7px;
                color: #374151;
                font-size: .84rem;
                font-weight: 600;
            }
            .auth-input {
                width: 100%;
                height: 44px;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                padding: 0 13px;
                color: #111827;
                background: #fff;
                font-size: .94rem;
                outline: none;
                transition: border-color .15s, box-shadow .15s;
            }
            .auth-input:focus {
                border-color: var(--hrms-primary);
                box-shadow: 0 0 0 4px color-mix(in srgb, var(--hrms-primary) 18%, transparent);
            }
            .auth-error {
                margin-top: 7px;
                color: #dc2626;
                font-size: .8rem;
            }
            .auth-help-link {
                color: #4b5563;
                font-size: .84rem;
                text-decoration: none;
            }
            .auth-help-link:hover {
                color: var(--hrms-primary);
                text-decoration: underline;
            }
            .auth-checkbox {
                width: 16px;
                height: 16px;
                accent-color: var(--hrms-primary);
            }
            .auth-submit {
                border: 0;
                border-radius: 8px;
                background: var(--hrms-primary);
                color: #fff;
                min-height: 44px;
                padding: 0 18px;
                font-weight: 700;
                cursor: pointer;
                transition: filter .15s, transform .15s;
            }
            .auth-submit:hover {
                filter: brightness(.96);
                transform: translateY(-1px);
            }
            .auth-status {
                border-radius: 8px;
                background: #ecfdf3;
                color: #027a48;
                padding: 10px 12px;
                margin-bottom: 18px;
                font-size: .86rem;
                border: 1px solid #abefc6;
            }

            @media (max-width: 900px) {
                .guest-page { grid-template-columns: 1fr; }
                .guest-brand-panel {
                    min-height: auto;
                    padding: 28px 24px 18px;
                }
                .guest-brand-copy { display: none; }
                .guest-brand-meta { display: none; }
                .guest-form-panel {
                    min-height: calc(100vh - 88px);
                    padding: 24px 16px 32px;
                    align-items: flex-start;
                }
                .guest-card {
                    padding: 24px;
                    border-radius: 10px;
                }
            }
        </style>

        @stack('styles')
    </head>
    <body>
        <main class="guest-page">
            <section class="guest-brand-panel">
                <a href="/" class="guest-brand-mark">
                    @php $logoUrl = function_exists('company_logo_url') ? company_logo_url() : null; @endphp
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ company_name() }}">
                    @else
                        <span class="guest-brand-icon">{{ strtoupper(substr(setting('theme_logo_text', 'H'), 0, 1)) }}</span>
                        <span>{{ setting('theme_logo_text', 'Hemdox') }} HRMS</span>
                    @endif
                </a>

                <div class="guest-brand-copy">
                    <h1>{{ company_name() ?: 'Hemdox HRMS' }}</h1>
                    <p>Secure employee management, attendance, payroll, approvals, and enterprise HR operations in one focused workspace.</p>
                </div>

                <div class="guest-brand-meta">
                    <span>Hemdox Digital</span>
                    <span>Secure Access Portal</span>
                    <span>{{ now()->format('Y') }}</span>
                </div>
            </section>

            <section class="guest-form-panel">
                <div class="guest-card">
                {{ $slot }}
                </div>
            </section>
        </main>

        @stack('scripts')
    </body>
</html>
