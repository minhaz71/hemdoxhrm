<x-emails.layout subject="Password Reset Required — Action Needed">

<p style="margin:0 0 16px;font-size:1rem;font-weight:700;color:#1a1f2e;">Hello {{ $user->name }},</p>

<p style="margin:0 0 16px;font-size:.9rem;color:#374151;line-height:1.7;">
    Your account administrator has reset your password in <strong>Hemdox HRMS</strong>.
    You will be required to set a new password the next time you log in.
</p>

{{-- Alert box --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
  <tr>
    <td style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:16px 20px;">
      <p style="margin:0;font-size:.875rem;color:#856404;">
        <strong>⚠ Action Required:</strong> Your password has been changed by an administrator.
        Please log in and set a new, secure password immediately.
      </p>
    </td>
  </tr>
</table>

{{-- CTA Button --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
  <tr>
    <td align="center">
      <a href="{{ url('/login') }}"
         style="display:inline-block;background:#0d6efd;color:#ffffff;text-decoration:none;
                font-weight:600;font-size:.9rem;padding:12px 32px;border-radius:8px;">
        Log In &amp; Change Password →
      </a>
    </td>
  </tr>
</table>

<p style="margin:0 0 8px;font-size:.8rem;color:#6c757d;line-height:1.6;">
    If you did not expect this change or believe it was made in error, please contact your system administrator immediately.
</p>

</x-emails.layout>
