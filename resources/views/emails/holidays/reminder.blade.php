<x-emails.layout :subject="$subject">

  {{-- Greeting --}}
  <p style="margin:0 0 20px;font-size:0.9rem;color:#6c757d;">
    Hi <strong style="color:#1a1f2e;">{{ $employee->full_name }}</strong>,
  </p>

  {{-- Holiday banner --}}
  <table width="100%" cellpadding="0" cellspacing="0"
         style="background:linear-gradient(135deg,#fff8e6 0%,#fff3cd 100%);
                border:1px solid #ffc107;border-left:4px solid #fd7e14;
                border-radius:8px;margin-bottom:24px;">
    <tr>
      <td style="padding:18px 20px;">
        <table cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding-right:14px;vertical-align:top;font-size:1.6rem;line-height:1;">
              🎉
            </td>
            <td>
              <strong style="font-size:1.05rem;color:#1a1f2e;display:block;margin-bottom:3px;">
                {{ $holiday->title }}
              </strong>
              <span style="font-size:0.82rem;color:#856404;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">
                Upcoming Holiday
              </span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  {{-- Intro paragraph --}}
  <p style="margin:0 0 20px;font-size:0.9rem;color:#374151;line-height:1.7;">
    We are pleased to inform you that the following holiday has been declared.
    Please plan your work accordingly and enjoy your well-deserved time off.
  </p>

  {{-- Holiday detail card --}}
  <table width="100%" cellpadding="0" cellspacing="0"
         style="border:1px solid #e9ecef;border-radius:8px;margin-bottom:24px;overflow:hidden;">

    {{-- Date range --}}
    <tr>
      <td style="padding:12px 18px;border-bottom:1px solid #e9ecef;
                 background:#f8f9fa;width:36%;">
        <span style="font-size:0.78rem;color:#6c757d;font-weight:600;text-transform:uppercase;
                     letter-spacing:0.5px;">📅 Date</span>
      </td>
      <td style="padding:12px 18px;border-bottom:1px solid #e9ecef;background:#ffffff;">
        @if ($holiday->start_date->equalTo($holiday->end_date))
          <strong style="color:#1a1f2e;font-size:0.9rem;">
            {{ $holiday->start_date->format('l, d M Y') }}
          </strong>
          <span style="display:block;font-size:0.8rem;color:#6c757d;margin-top:2px;">
            1 day
          </span>
        @else
          <strong style="color:#1a1f2e;font-size:0.9rem;">
            {{ $holiday->start_date->format('d M Y') }} &rarr; {{ $holiday->end_date->format('d M Y') }}
          </strong>
          <span style="display:block;font-size:0.8rem;color:#6c757d;margin-top:2px;">
            {{ $holiday->start_date->diffInDays($holiday->end_date) + 1 }} days
          </span>
        @endif
      </td>
    </tr>

    {{-- Reason --}}
    <tr>
      <td style="padding:12px 18px;border-bottom:1px solid #e9ecef;
                 background:#f8f9fa;vertical-align:top;">
        <span style="font-size:0.78rem;color:#6c757d;font-weight:600;text-transform:uppercase;
                     letter-spacing:0.5px;">📝 Reason</span>
      </td>
      <td style="padding:12px 18px;border-bottom:1px solid #e9ecef;background:#ffffff;">
        <span style="font-size:0.9rem;color:#374151;line-height:1.5;">
          {{ $holiday->reason ?: 'Company holiday' }}
        </span>
      </td>
    </tr>

    {{-- Scope --}}
    <tr>
      <td style="padding:12px 18px;background:#f8f9fa;">
        <span style="font-size:0.78rem;color:#6c757d;font-weight:600;text-transform:uppercase;
                     letter-spacing:0.5px;">🏢 Applies To</span>
      </td>
      <td style="padding:12px 18px;background:#ffffff;">
        @if ($holiday->type === 'global')
          <span style="display:inline-block;background:#e8f4fd;color:#1a8fe3;
                       font-size:0.78rem;font-weight:600;padding:3px 10px;border-radius:20px;">
            All Employees
          </span>
        @elseif ($holiday->type === 'branch')
          <span style="display:inline-block;background:#eaf7ee;color:#28a745;
                       font-size:0.78rem;font-weight:600;padding:3px 10px;border-radius:20px;">
            {{ $holiday->branch?->name ?? 'Branch' }}
          </span>
        @elseif ($holiday->type === 'department')
          <span style="display:inline-block;background:#fff3cd;color:#856404;
                       font-size:0.78rem;font-weight:600;padding:3px 10px;border-radius:20px;">
            {{ $holiday->department?->name ?? 'Department' }}
          </span>
        @else
          <span style="display:inline-block;background:#f3e8ff;color:#7c3aed;
                       font-size:0.78rem;font-weight:600;padding:3px 10px;border-radius:20px;">
            Specific Employees
          </span>
        @endif
      </td>
    </tr>
  </table>

  {{-- Closing note --}}
  <p style="margin:0 0 6px;font-size:0.875rem;color:#374151;line-height:1.7;">
    If you have any questions about this holiday, please reach out to the HR department.
  </p>
  <p style="margin:0;font-size:0.875rem;color:#374151;line-height:1.7;">
    Warm regards,<br>
    <strong style="color:#1a1f2e;">HR Department</strong>
  </p>

</x-emails.layout>
