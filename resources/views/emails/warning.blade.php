<x-emails.layout :subject="$subject">

  <p style="margin:0 0 8px;font-size:0.9rem;color:#6c757d;">Hi <strong>{{ $employee->full_name }}</strong>,</p>

  {{-- Warning banner --}}
  <table width="100%" cellpadding="0" cellspacing="0"
         style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;margin-bottom:24px;">
    <tr>
      <td style="padding:14px 18px;">
        <table cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding-right:12px;vertical-align:top;">
              <span style="font-size:1.3rem;">⚠️</span>
            </td>
            <td>
              <strong style="font-size:0.95rem;color:#856404;display:block;">Formal Warning</strong>
              <span style="font-size:0.85rem;color:#856404;">{{ $subject }}</span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  {{-- Body --}}
  <div style="font-size:0.9rem;color:#1a1f2e;line-height:1.7;margin-bottom:24px;">
    {!! nl2br(e($body)) !!}
  </div>

  {{-- Meta --}}
  <table width="100%" cellpadding="0" cellspacing="0"
         style="background:#f8f9fa;border-radius:8px;border:1px solid #e9ecef;margin-bottom:24px;">
    <tr>
      <td style="padding:10px 16px;font-size:0.85rem;color:#6c757d;border-bottom:1px solid #e9ecef;">
        Employee Code: <strong style="color:#1a1f2e;">{{ $employee->employee_code }}</strong>
      </td>
    </tr>
    <tr>
      <td style="padding:10px 16px;font-size:0.85rem;color:#6c757d;border-bottom:1px solid #e9ecef;">
        Department: <strong style="color:#1a1f2e;">{{ $employee->department ?? '—' }}</strong>
      </td>
    </tr>
    <tr>
      <td style="padding:10px 16px;font-size:0.85rem;color:#6c757d;">
        Issued: <strong style="color:#1a1f2e;">{{ now()->format('d M Y') }}</strong>
      </td>
    </tr>
  </table>

  <p style="margin:0;font-size:0.875rem;color:#6c757d;">
    Please acknowledge this warning by contacting your HR representative or manager within 3 business days.
    Continued non-compliance may lead to further disciplinary action.
  </p>

</x-emails.layout>
