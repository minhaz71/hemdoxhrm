<x-emails.layout subject="Leave Request Rejected">

  <p style="margin:0 0 8px;font-size:0.9rem;color:#6c757d;">Hi <strong>{{ $leave->employee->full_name }}</strong>,</p>

  <p style="margin:0 0 24px;font-size:1rem;color:#1a1f2e;">
    We regret to inform you that your leave request has been <strong style="color:#dc3545;">rejected</strong>.
  </p>

  {{-- Details table --}}
  <table width="100%" cellpadding="0" cellspacing="0"
         style="background:#f8f9fa;border-radius:8px;border:1px solid #e9ecef;margin-bottom:24px;">
    <tr>
      <td style="padding:12px 16px;border-bottom:1px solid #e9ecef;">
        <span style="font-size:0.8rem;color:#6c757d;display:block;">Leave Type</span>
        <strong style="font-size:0.95rem;color:#1a1f2e;">{{ $leave->leaveType->name ?? '—' }}</strong>
      </td>
    </tr>
    <tr>
      <td style="padding:12px 16px;border-bottom:1px solid #e9ecef;">
        <span style="font-size:0.8rem;color:#6c757d;display:block;">Requested Period</span>
        <strong style="font-size:0.95rem;color:#1a1f2e;">
          {{ $leave->start_date->format('d M Y') }} — {{ $leave->end_date->format('d M Y') }}
        </strong>
      </td>
    </tr>
    <tr>
      <td style="padding:12px 16px;border-bottom:1px solid #e9ecef;">
        <span style="font-size:0.8rem;color:#6c757d;display:block;">Rejected By</span>
        <strong style="font-size:0.95rem;color:#1a1f2e;">{{ $leave->approvedBy->name ?? '—' }}</strong>
        @if($leave->actioned_at)
          <span style="font-size:0.8rem;color:#6c757d;margin-left:6px;">
            on {{ $leave->actioned_at->format('d M Y, h:i A') }}
          </span>
        @endif
      </td>
    </tr>
    @if($leave->rejection_note)
    <tr>
      <td style="padding:12px 16px;">
        <span style="font-size:0.8rem;color:#6c757d;display:block;">Reason</span>
        <span style="font-size:0.95rem;color:#1a1f2e;">{{ $leave->rejection_note }}</span>
      </td>
    </tr>
    @endif
  </table>

  <p style="margin:0;font-size:0.875rem;color:#6c757d;">
    If you have questions about this decision, please speak with your HR representative.
  </p>

</x-emails.layout>
