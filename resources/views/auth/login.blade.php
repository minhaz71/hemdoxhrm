<x-guest-layout>
    <x-slot name="title">Login - {{ config('app.name', 'Hemdox HRMS') }}</x-slot>

    <div class="guest-card-header">
        <h2>Sign in</h2>
        <p>Use your email address or company user ID to access Hemdox HRMS.</p>
    </div>

    @if (session('status'))
        <div class="auth-status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <label for="email" class="auth-label">Email or User ID</label>
            <input id="email" class="auth-input" type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="admin@company.com or employee_id">
            @foreach($errors->get('email') as $message)
                <div class="auth-error">{{ $message }}</div>
            @endforeach
        </div>

        <div style="margin-top:18px;">
            <label for="password" class="auth-label">Password</label>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
            @foreach($errors->get('password') as $message)
                <div class="auth-error">{{ $message }}</div>
            @endforeach
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;margin-top:16px;flex-wrap:wrap;">
            <label for="remember_me" style="display:inline-flex;align-items:center;gap:8px;color:#4b5563;font-size:.86rem;">
                <input id="remember_me" type="checkbox" class="auth-checkbox" name="remember">
                <span>Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="auth-help-link" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        <div style="margin-top:24px;">
            <button type="submit" class="auth-submit" style="width:100%;">
                Log in
            </button>
        </div>
    </form>
</x-guest-layout>
