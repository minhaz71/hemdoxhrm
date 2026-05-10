# Hemdox HRMS Security Recommendations

## Authentication

- Keep `SESSION_ENCRYPT=true`, `SESSION_EXPIRE_ON_CLOSE=true`, and `SESSION_SAME_SITE=strict` in production.
- Set `SESSION_SECURE_COOKIE=true` when the app is served over HTTPS.
- Keep the enterprise password rule at 13+ characters with mixed case, numbers, symbols, and uncompromised checks.
- Review failed-login and blocked-login events in `security_audit_logs` regularly.

## Invitations

- Share only the generated signed invitation URL, not raw `/invite/{token}` paths.
- Keep invitation expiry windows as short as operationally possible.
- Use `max_uses=1` for named employee onboarding unless a bulk event explicitly requires more.
- Treat repeated `invite.rejected_invalid_submit` and HTTP 403 signed URL failures as possible abuse.

## RBAC

- Reserve `admin`, `roles.manage`, `settings.edit`, `users.manage`, and `invitations.manage` for trusted administrators only.
- Review all `rbac.*` audit events before payroll cycles or compliance exports.
- Avoid assigning direct permission overrides unless a documented exception exists.
- Do not allow users to modify their own privileged role membership.

## SMTP

- Prefer Gmail App Passwords or provider-specific SMTP tokens over account passwords.
- Rotate SMTP credentials after administrator turnover.
- Keep mail disabled until a test email succeeds from the admin settings page.

## Operations

- Run `php artisan schedule:run` under a locked-down system user.
- Use HTTPS with HSTS at the edge.
- Back up `security_audit_logs`, `login_activities`, and registration audit tables.
- Add alerting for spikes in failed login, invite submit throttling, and RBAC mutation events.
