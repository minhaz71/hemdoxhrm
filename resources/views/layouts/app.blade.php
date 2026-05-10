<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --hrms-primary:  {{ setting('theme_primary_color', '#4e9af1') }};
            --hrms-sidebar:  {{ setting('theme_sidebar_color', '#1a1f2e') }};
        }

        body { font-family: 'Inter', sans-serif; background: #f4f6f9; }

        /* Sidebar */
        #sidebar {
            width: 250px;
            min-height: 100vh;
            background: var(--hrms-sidebar);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: width 0.25s ease;
            overflow-x: hidden;
        }
        #sidebar .brand {
            padding: 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        #sidebar .brand h5 {
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            margin: 0;
            white-space: nowrap;
        }
        #sidebar .brand span { color: #4e9af1; }

        #sidebar .nav-label {
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: .08em;
            color: rgba(255,255,255,0.35);
            padding: 18px 16px 6px;
            text-transform: uppercase;
        }
        #sidebar .nav-link {
            color: rgba(255,255,255,0.65);
            padding: 9px 16px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 6px;
            margin: 1px 8px;
            transition: all 0.15s;
            white-space: nowrap;
        }
        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            background: color-mix(in srgb, var(--hrms-primary) 15%, transparent);
            color: var(--hrms-primary);
        }
        #sidebar .nav-link i { font-size: 1rem; min-width: 18px; }

        /* Main content */
        #main-content {
            margin-left: 250px;
            min-height: 100vh;
            transition: margin-left 0.25s ease;
        }

        /* Topbar */
        #topbar {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        #topbar .page-title {
            font-weight: 600;
            font-size: 1rem;
            color: #1a1f2e;
            margin: 0;
        }
        .topbar-user img {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: #4e9af1;
            object-fit: cover;
        }
        .topbar-user .avatar-placeholder {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: #4e9af1;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }

        /* Page body */
        .page-body { padding: 24px; }

        /* Stat cards */
        .stat-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e9ecef;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: box-shadow 0.15s;
        }
        .stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.07); }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }
        .stat-card .label { font-size: 0.8rem; color: #6c757d; font-weight: 500; }
        .stat-card .value { font-size: 1.6rem; font-weight: 700; color: #1a1f2e; line-height: 1.2; }

        /* Card generic */
        .hrms-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        .hrms-card .card-header {
            background: transparent;
            border-bottom: 1px solid #e9ecef;
            padding: 14px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #1a1f2e;
        }

        /* Responsive collapse */
        @media (max-width: 768px) {
            #sidebar { width: 0; }
            #main-content { margin-left: 0; }
            #sidebar.open { width: 250px; }
        }
    </style>

    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<nav id="sidebar">
    <div class="brand">
        @php $logoUrl = company_logo_url(); @endphp
        @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ company_name() }}" style="max-height:36px;max-width:160px;object-fit:contain;margin-bottom:6px;display:block;">
        @else
        <h5>
            <span style="color:var(--hrms-primary);">{{ setting('theme_logo_text', 'Hemdox') }}</span> HRMS
        </h5>
        <div style="color:rgba(255,255,255,.35);font-size:.72rem;margin-top:2px;">{{ company_tagline() ?: company_name() }}</div>
        @endif
    </div>

    <div class="mt-2">
        @include('partials.sidebar')
    </div>
</nav>

{{-- Main --}}
<div id="main-content">

    {{-- Topbar --}}
    <div id="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light d-md-none" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <p class="page-title">{{ $title ?? 'Dashboard' }}</p>
        </div>

        <div class="topbar-user d-flex align-items-center gap-2">
            <div class="dropdown">
                <button class="btn d-flex align-items-center gap-2 p-0 border-0 bg-transparent"
                        data-bs-toggle="dropdown">
                    <div class="avatar-placeholder">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <span style="font-size:.85rem;font-weight:500;color:#1a1f2e;">
                        {{ Auth::user()->name }}
                    </span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;color:#6c757d;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <span class="dropdown-item-text text-muted" style="font-size:.78rem;">
                            {{ Auth::user()->email }}
                        </span>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="bi bi-person me-2"></i>Profile
                        </a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Page Content --}}
    <div class="page-body">
        @if(session('impersonator_id'))
        <div class="alert alert-warning d-flex justify-content-between align-items-center mb-3">
            <div>
                <i class="bi bi-person-badge me-2"></i>
                You are impersonating <strong>{{ Auth::user()->name }}</strong>.
            </div>
            <form method="POST" action="{{ route('impersonation.stop') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning">
                    <i class="bi bi-box-arrow-left me-1"></i>End Impersonation
                </button>
            </form>
        </div>
        @endif

        {{ $slot }}
    </div>

</div>

@vite('resources/js/app.js')

{{--
    Bootstrap JS is served locally (no CDN dependency) as a plain <script> tag.
    It MUST NOT be loaded via @vite — @vite emits type="module" which is deferred
    by the browser until after DOMContentLoaded, meaning window.bootstrap would
    be undefined when synchronous @push('scripts') blocks run, breaking ALL
    modals, toasts, tabs and tooltips site-wide.
--}}
<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('open');
    });
</script>

@stack('scripts')
</body>
</html>
