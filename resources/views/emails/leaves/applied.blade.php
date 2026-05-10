<x-emails.layout>
    <x-slot name="subject">New Leave Application</x-slot>

    <p style="margin:0 0 14px;color:#334155;font-size:14px;line-height:1.6;">
        A new leave application has been submitted and is waiting for review.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:12px;">
        <tr>
            <td style="padding:8px 0;color:#64748b;font-size:13px;">Employee</td>
            <td style="padding:8px 0;color:#0f172a;font-size:13px;font-weight:600;text-align:right;">
                {{ $leave->employee->full_name }} ({{ $leave->employee->employee_code }})
            </td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#64748b;font-size:13px;">Leave Type</td>
            <td style="padding:8px 0;color:#0f172a;font-size:13px;font-weight:600;text-align:right;">
                {{ $leave->effective_type_name }}
            </td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#64748b;font-size:13px;">Period</td>
            <td style="padding:8px 0;color:#0f172a;font-size:13px;font-weight:600;text-align:right;">
                {{ $leave->start_date->format('M j, Y') }} - {{ $leave->end_date->format('M j, Y') }}
            </td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#64748b;font-size:13px;">Working Days</td>
            <td style="padding:8px 0;color:#0f172a;font-size:13px;font-weight:600;text-align:right;">
                {{ $leave->total_days }}
            </td>
        </tr>
        @if($leave->reason)
        <tr>
            <td style="padding:8px 0;color:#64748b;font-size:13px;vertical-align:top;">Reason</td>
            <td style="padding:8px 0;color:#0f172a;font-size:13px;text-align:right;">
                {{ $leave->reason }}
            </td>
        </tr>
        @endif
    </table>
</x-emails.layout>
