<x-emails.layout subject="Your Salary Has Been Processed">

  <p style="margin:0 0 8px;font-size:0.9rem;color:#6c757d;">Hi <strong>{{ $payroll->employee->full_name }}</strong>,</p>

  <p style="margin:0 0 24px;font-size:1rem;color:#1a1f2e;">
    Your salary for <strong>{{ $payroll->month_label }}</strong> has been processed and is now available.
  </p>

  {{-- Earnings / Deductions --}}
  <table width="100%" cellpadding="0" cellspacing="0"
         style="background:#f8f9fa;border-radius:8px;border:1px solid #e9ecef;margin-bottom:24px;">

    <tr style="background:#f1f3f5;">
      <td colspan="2" style="padding:10px 16px;font-size:0.78rem;font-weight:700;
                             color:#6c757d;letter-spacing:.05em;text-transform:uppercase;
                             border-bottom:1px solid #e9ecef;">
        Earnings
      </td>
    </tr>
    <tr>
      <td style="padding:10px 16px;font-size:0.9rem;color:#495057;border-bottom:1px solid #e9ecef;">
        Base Salary
      </td>
      <td align="right" style="padding:10px 16px;font-size:0.9rem;color:#1a1f2e;
                                font-weight:600;border-bottom:1px solid #e9ecef;">
        {{ currency($payroll->base_salary) }}
      </td>
    </tr>
    @if($payroll->bonus > 0)
    <tr>
      <td style="padding:10px 16px;font-size:0.9rem;color:#495057;border-bottom:1px solid #e9ecef;">
        Bonus
      </td>
      <td align="right" style="padding:10px 16px;font-size:0.9rem;color:#1a1f2e;
                                font-weight:600;border-bottom:1px solid #e9ecef;">
        {{ currency($payroll->bonus) }}
      </td>
    </tr>
    @endif
    @if($payroll->incentive > 0)
    <tr>
      <td style="padding:10px 16px;font-size:0.9rem;color:#495057;border-bottom:1px solid #e9ecef;">
        Incentive
      </td>
      <td align="right" style="padding:10px 16px;font-size:0.9rem;color:#1a1f2e;
                                font-weight:600;border-bottom:1px solid #e9ecef;">
        {{ currency($payroll->incentive) }}
      </td>
    </tr>
    @endif
    <tr>
      <td style="padding:10px 16px;font-size:0.9rem;color:#495057;border-bottom:1px solid #e9ecef;">
        Gross Salary
      </td>
      <td align="right" style="padding:10px 16px;font-size:0.9rem;font-weight:700;
                                color:#28a745;border-bottom:1px solid #e9ecef;">
        {{ currency($payroll->gross_salary) }}
      </td>
    </tr>

    @if($payroll->total_deductions > 0)
    <tr style="background:#f1f3f5;">
      <td colspan="2" style="padding:10px 16px;font-size:0.78rem;font-weight:700;
                             color:#6c757d;letter-spacing:.05em;text-transform:uppercase;
                             border-bottom:1px solid #e9ecef;">
        Deductions
      </td>
    </tr>
    @if($payroll->late_deduction > 0)
    <tr>
      <td style="padding:10px 16px;font-size:0.9rem;color:#495057;border-bottom:1px solid #e9ecef;">
        Late Penalty
      </td>
      <td align="right" style="padding:10px 16px;font-size:0.9rem;color:#dc3545;
                                border-bottom:1px solid #e9ecef;">
        ({{ currency($payroll->late_deduction) }})
      </td>
    </tr>
    @endif
    @if($payroll->absent_deduction > 0)
    <tr>
      <td style="padding:10px 16px;font-size:0.9rem;color:#495057;border-bottom:1px solid #e9ecef;">
        Absent Deduction
      </td>
      <td align="right" style="padding:10px 16px;font-size:0.9rem;color:#dc3545;
                                border-bottom:1px solid #e9ecef;">
        ({{ currency($payroll->absent_deduction) }})
      </td>
    </tr>
    @endif
    @if($payroll->leave_deduction > 0)
    <tr>
      <td style="padding:10px 16px;font-size:0.9rem;color:#495057;border-bottom:1px solid #e9ecef;">
        Unpaid Leave Deduction
      </td>
      <td align="right" style="padding:10px 16px;font-size:0.9rem;color:#dc3545;
                                border-bottom:1px solid #e9ecef;">
        ({{ currency($payroll->leave_deduction) }})
      </td>
    </tr>
    @endif
    @endif

    {{-- Net --}}
    <tr style="background:#1a1f2e;">
      <td style="padding:14px 16px;font-size:1rem;font-weight:700;color:#ffffff;
                 border-radius:0 0 0 8px;">
        Net Salary
      </td>
      <td align="right" style="padding:14px 16px;font-size:1.1rem;font-weight:700;
                                color:#4e9af1;border-radius:0 0 8px 0;">
        {{ currency($payroll->net_salary) }}
      </td>
    </tr>
  </table>

  <p style="margin:0;font-size:0.875rem;color:#6c757d;">
    Your payslip will be available in the HRMS portal once payment is confirmed.
    Contact HR if you have any questions about this payroll.
  </p>

</x-emails.layout>
