<x-emails.layout subject="Your Account Has Been Activated">

  <p style="margin:0 0 8px;font-size:0.9rem;color:#6c757d;">
    Hi <strong>{{ $registration->full_name }}</strong>,
  </p>

  <p style="margin:0 0 24px;font-size:1rem;color:#1a1f2e;">
    🎉 Great news! Your registration has been <strong style="color:#28a745;">approved</strong>
    and your employee account is now <strong>active</strong>. You can log in immediately using
    the credentials you set during registration.
  </p>

  {{-- Login Details --}}
  <table width="100%" cellpadding="0" cellspacing="0"
         style="background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;margin-bottom:24px;">
    <tr>
      <td style="padding:16px 20px;border-bottom:1px solid #bbf7d0;">
        <span style="font-size:0.78rem;color:#6c757d;display:block;text-transform:uppercase;letter-spacing:.04em;">Your User ID</span>
        <strong style="font-size:1.05rem;color:#1a1f2e;font-family:monospace;">{{ $registration->login_id }}</strong>
      </td>
    </tr>
    <tr>
      <td style="padding:16px 20px;border-bottom:1px solid #bbf7d0;">
        <span style="font-size:0.78rem;color:#6c757d;display:block;text-transform:uppercase;letter-spacing:.04em;">Email</span>
        <strong style="font-size:0.95rem;color:#1a1f2e;">{{ $registration->email }}</strong>
      </td>
    </tr>
    <tr>
      <td style="padding:16px 20px;">
        <span style="font-size:0.78rem;color:#6c757d;display:block;text-transform:uppercase;letter-spacing:.04em;">Employee Code</span>
        <strong style="font-size:0.95rem;color:#1a1f2e;">{{ $employee->employee_code }}</strong>
      </td>
    </tr>
  </table>

  {{-- Employment Details --}}
  <table width="100%" cellpadding="0" cellspacing="0"
         style="background:#f8f9fa;border-radius:8px;border:1px solid #e9ecef;margin-bottom:28px;">
    <tr>
      <td style="padding:12px 16px;border-bottom:1px solid #e9ecef;">
        <span style="font-size:0.8rem;color:#6c757d;display:block;">Designation</span>
        <strong style="font-size:0.95rem;color:#1a1f2e;">{{ $employee->designation ?? '—' }}</strong>
      </td>
    </tr>
    <tr>
      <td style="padding:12px 16px;border-bottom:1px solid #e9ecef;">
        <span style="font-size:0.8rem;color:#6c757d;display:block;">Department</span>
        <strong style="font-size:0.95rem;color:#1a1f2e;">{{ $employee->department ?? '—' }}</strong>
      </td>
    </tr>
    <tr>
      <td style="padding:12px 16px;border-bottom:1px solid #e9ecef;">
        <span style="font-size:0.8rem;color:#6c757d;display:block;">Employment Type</span>
        <strong style="font-size:0.95rem;color:#1a1f2e;">{{ str_replace('_', ' ', ucfirst($employee->employment_type)) }}</strong>
      </td>
    </tr>
    <tr>
      <td style="padding:12px 16px;">
        <span style="font-size:0.8rem;color:#6c757d;display:block;">Join Date</span>
        <strong style="font-size:0.95rem;color:#1a1f2e;">{{ $employee->join_date->format('d M Y') }}</strong>
      </td>
    </tr>
  </table>

  {{-- Login CTA --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
    <tr>
      <td align="center">
        <a href="{{ url('/login') }}"
           style="display:inline-block;background:#0d6efd;color:#fff;padding:12px 36px;border-radius:8px;font-size:0.95rem;font-weight:600;text-decoration:none;letter-spacing:.02em;">
          Login to Your Account
        </a>
      </td>
    </tr>
  </table>

  <p style="margin:0 0 6px;font-size:0.82rem;color:#6c757d;">
    Use your <strong>User ID</strong> (or email) and the password you set during registration to log in.
    If you have any questions, please contact HR.
  </p>

  <p style="margin:24px 0 0;font-size:0.9rem;color:#1a1f2e;">
    Welcome aboard! 👋<br>
    <strong>HR Team</strong>
  </p>

</x-emails.layout>
