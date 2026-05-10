<x-app-layout>
    <x-slot name="title">Change Password Required</x-slot>

    <x-alert />

    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="hrms-card p-4">
                <div class="text-center mb-4">
                    <div class="mb-3" style="width:64px;height:64px;background:#fff3cd;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                        <i class="bi bi-shield-lock-fill text-warning fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Password Change Required</h5>
                    <p class="text-muted small mb-0">
                        Your account requires a password change before you can continue.<br>
                        Please set a new, strong password.
                    </p>
                </div>

                <form method="POST" action="{{ route('auth.force-password-change.submit') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.875rem;">Current Password</label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="currentPwd"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   required autocomplete="current-password">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('currentPwd', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                            @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.875rem;">New Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="newPwd"
                                   class="form-control @error('password') is-invalid @enderror"
                                   required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('newPwd', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted">At least 8 characters, mixed case and numbers.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:.875rem;">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" id="confirmPwd"
                                   class="form-control"
                                   required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('confirmPwd', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check2-circle me-1"></i> Set New Password & Continue
                    </button>
                </form>

                <div class="text-center mt-3">
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm text-muted p-0">
                            Sign out instead
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function togglePwd(id, btn) {
        const input = document.getElementById(id);
        const icon  = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }
    </script>
    @endpush
</x-app-layout>
