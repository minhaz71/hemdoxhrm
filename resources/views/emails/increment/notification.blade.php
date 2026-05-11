<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $emailSubject }}</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { background:#f0f2f5; font-family:Arial, Helvetica, sans-serif; }
  a    { color:#2563eb; }
</style>
</head>
<body>

@php
  $emp          = $salaryHistory->employee;
  $prevSalary   = (float)$salaryHistory->previous_salary;
  $newSalary    = (float)$salaryHistory->base_salary;
  $delta        = $newSalary - $prevSalary;
  $deltaPct     = $prevSalary > 0 ? abs(($delta / $prevSalary) * 100) : null;
  $isPositive   = $delta >= 0;
  $accentHex    = $isPositive ? '#16a34a' : '#dc2626';
  $effectDate   = $salaryHistory->effective_from->format('F j, Y');
  $co           = $companyName ?: config('app.name');
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5; padding:40px 16px;">
  <tr>
    <td align="center">
      <table width="620" cellpadding="0" cellspacing="0"
             style="max-width:620px; width:100%; border-radius:14px; overflow:hidden;
                    box-shadow:0 4px 24px rgba(0,0,0,.08);">

        {{-- ── Top accent bar ───────────────────────────────── --}}
        <tr>
          <td style="height:5px; background:linear-gradient(90deg, #2563eb 0%, {{ $accentHex }} 100%);"></td>
        </tr>

        {{-- ── Header ────────────────────────────────────────── --}}
        <tr>
          <td style="background:#1e293b; padding:28px 36px;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td>
                  <span style="font-size:1.15rem; font-weight:700; color:#60a5fa;">{{ $co }}</span>
                </td>
                <td align="right">
                  <span style="display:inline-block; background:{{ $accentHex }}; color:#fff;
                               font-size:.72rem; font-weight:700; letter-spacing:.07em;
                               text-transform:uppercase; padding:4px 12px; border-radius:20px;">
                    {{ $isPositive ? 'Salary Increment' : 'Salary Revision' }}
                  </span>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- ── Hero section ─────────────────────────────────── --}}
        <tr>
          <td style="background:#fff; padding:36px 36px 0;">

            <p style="font-size:.88rem; color:#64748b; margin-bottom:4px;">
              Dear <strong style="color:#1e293b;">{{ $emp->full_name }}</strong>,
            </p>

            @if($introText)
            <p style="font-size:.95rem; color:#334155; line-height:1.7; margin:16px 0 0;">
              {{ $introText }}
            </p>
            @else
            <p style="font-size:.95rem; color:#334155; line-height:1.7; margin:16px 0 0;">
              We are pleased to inform you that your salary has been revised,
              effective <strong>{{ $effectDate }}</strong>. Please find the details
              of your revised compensation below.
            </p>
            @endif

          </td>
        </tr>

        {{-- ── Salary Highlight Boxes ──────────────────────── --}}
        <tr>
          <td style="background:#fff; padding:28px 36px 0;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>

                {{-- Previous --}}
                <td width="30%" style="padding-right:8px;">
                  <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;
                              padding:16px 14px; text-align:center;">
                    <p style="font-size:.7rem; font-weight:700; color:#94a3b8;
                               letter-spacing:.06em; text-transform:uppercase; margin-bottom:6px;">
                      Previous Salary
                    </p>
                    <p style="font-size:1.1rem; font-weight:700; color:#64748b;">
                      {{ currency($prevSalary) }}
                    </p>
                  </div>
                </td>

                {{-- Arrow --}}
                <td width="8%" align="center" style="padding:0 4px;">
                  <div style="font-size:1.4rem; color:#94a3b8; font-weight:300;">→</div>
                </td>

                {{-- New --}}
                <td width="30%" style="padding:0 8px;">
                  <div style="background:{{ $isPositive ? '#f0fdf4' : '#fef2f2' }};
                              border:1px solid {{ $isPositive ? '#bbf7d0' : '#fecaca' }};
                              border-radius:10px; padding:16px 14px; text-align:center;">
                    <p style="font-size:.7rem; font-weight:700;
                               color:{{ $isPositive ? '#16a34a' : '#dc2626' }};
                               letter-spacing:.06em; text-transform:uppercase; margin-bottom:6px;">
                      New Salary
                    </p>
                    <p style="font-size:1.1rem; font-weight:700;
                               color:{{ $isPositive ? '#15803d' : '#b91c1c' }};">
                      {{ currency($newSalary) }}
                    </p>
                  </div>
                </td>

                {{-- Delta --}}
                <td width="8%" align="center" style="padding:0 4px;">
                  <div style="font-size:1rem; color:#94a3b8;">≡</div>
                </td>

                {{-- Change box --}}
                <td width="24%">
                  <div style="background:{{ $isPositive ? '#2563eb' : '#7f1d1d' }};
                              border-radius:10px; padding:16px 14px; text-align:center;">
                    <p style="font-size:.7rem; font-weight:700; color:rgba(255,255,255,.7);
                               letter-spacing:.06em; text-transform:uppercase; margin-bottom:6px;">
                      {{ $isPositive ? 'Increase' : 'Change' }}
                    </p>
                    <p style="font-size:1.05rem; font-weight:700; color:#fff;">
                      {{ $delta >= 0 ? '+' : '' }}{{ currency($delta) }}
                    </p>
                    @if($deltaPct !== null)
                    <p style="font-size:.78rem; color:rgba(255,255,255,.75); margin-top:2px;">
                      {{ number_format($deltaPct, 1) }}%
                    </p>
                    @endif
                  </div>
                </td>

              </tr>
            </table>
          </td>
        </tr>

        {{-- ── Detailed Breakdown Table ─────────────────────── --}}
        <tr>
          <td style="background:#fff; padding:24px 36px 0;">
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">

              {{-- Table header --}}
              <tr style="background:#1e293b;">
                <td colspan="2" style="padding:12px 18px;">
                  <span style="font-size:.72rem; font-weight:700; color:rgba(255,255,255,.6);
                               letter-spacing:.07em; text-transform:uppercase;">
                    Salary Revision Details
                  </span>
                </td>
              </tr>

              {{-- Employee --}}
              <tr>
                <td style="padding:13px 18px; font-size:.88rem; color:#64748b;
                           border-bottom:1px solid #f1f5f9; background:#fff;">
                  Employee Name
                </td>
                <td align="right" style="padding:13px 18px; font-size:.88rem; font-weight:600;
                           color:#1e293b; border-bottom:1px solid #f1f5f9; background:#fff;">
                  {{ $emp->full_name }}
                  @if($emp->employee_code)
                  <span style="color:#94a3b8; font-size:.78rem; font-weight:400;">
                    ({{ $emp->employee_code }})
                  </span>
                  @endif
                </td>
              </tr>

              {{-- Previous Salary --}}
              @if($prevSalary > 0)
              <tr>
                <td style="padding:13px 18px; font-size:.88rem; color:#64748b;
                           border-bottom:1px solid #f1f5f9; background:#f8fafc;">
                  Previous Salary
                </td>
                <td align="right" style="padding:13px 18px; font-size:.88rem; color:#64748b;
                           border-bottom:1px solid #f1f5f9; background:#f8fafc;">
                  {{ currency($prevSalary) }}
                </td>
              </tr>
              @endif

              {{-- New Salary --}}
              <tr>
                <td style="padding:13px 18px; font-size:.88rem; color:#64748b;
                           border-bottom:1px solid #f1f5f9; background:#fff;">
                  Revised Salary
                </td>
                <td align="right" style="padding:13px 18px; font-size:.95rem; font-weight:700;
                           color:{{ $accentHex }}; border-bottom:1px solid #f1f5f9; background:#fff;">
                  {{ currency($newSalary) }}
                </td>
              </tr>

              {{-- Increment Amount --}}
              @if($prevSalary > 0)
              <tr>
                <td style="padding:13px 18px; font-size:.88rem; color:#64748b;
                           border-bottom:1px solid #f1f5f9; background:#f8fafc;">
                  {{ $isPositive ? 'Increment Amount' : 'Change Amount' }}
                </td>
                <td align="right" style="padding:13px 18px; font-size:.88rem; font-weight:600;
                           color:{{ $accentHex }}; border-bottom:1px solid #f1f5f9; background:#f8fafc;">
                  {{ $delta >= 0 ? '+' : '' }}{{ currency($delta) }}
                </td>
              </tr>

              {{-- Percentage --}}
              @if($deltaPct !== null)
              <tr>
                <td style="padding:13px 18px; font-size:.88rem; color:#64748b;
                           border-bottom:1px solid #f1f5f9; background:#fff;">
                  Increment Percentage
                </td>
                <td align="right" style="padding:13px 18px; font-size:.88rem; font-weight:600;
                           color:{{ $accentHex }}; border-bottom:1px solid #f1f5f9; background:#fff;">
                  {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta >= 0 ? $deltaPct : -$deltaPct, 2) }}%
                </td>
              </tr>
              @endif
              @endif

              {{-- Effective Date --}}
              <tr>
                <td style="padding:13px 18px; font-size:.88rem; color:#64748b; background:#f8fafc;">
                  Effective Date
                </td>
                <td align="right" style="padding:13px 18px; font-size:.88rem; font-weight:600;
                           color:#1e293b; background:#f8fafc;">
                  {{ $effectDate }}
                </td>
              </tr>

            </table>
          </td>
        </tr>

        {{-- ── Closing message ─────────────────────────────── --}}
        <tr>
          <td style="background:#fff; padding:24px 36px 0;">
            @if($closingText)
            <p style="font-size:.9rem; color:#475569; line-height:1.75;">
              {{ $closingText }}
            </p>
            @else
            <p style="font-size:.9rem; color:#475569; line-height:1.75;">
              Thank you for your continued dedication and contribution to the organization.
              If you have any questions regarding this revision, please contact the HR department.
            </p>
            @endif
          </td>
        </tr>

        {{-- ── Signature ────────────────────────────────────── --}}
        <tr>
          <td style="background:#fff; padding:28px 36px 36px;">
            <table cellpadding="0" cellspacing="0">
              <tr>
                <td style="border-left:3px solid #2563eb; padding-left:14px;">
                  <p style="font-size:.9rem; color:#6b7280; margin-bottom:2px;">Warm regards,</p>
                  <p style="font-size:.95rem; font-weight:700; color:#1e293b; margin-bottom:1px;">
                    {{ $signatureName ?: 'HR Department' }}
                  </p>
                  @if($signatureTitle)
                  <p style="font-size:.82rem; color:#64748b; margin-bottom:0;">
                    {{ $signatureTitle }}
                  </p>
                  @endif
                  @if($signatureContact)
                  <p style="font-size:.82rem; color:#64748b; margin-top:2px;">
                    {{ $signatureContact }}
                  </p>
                  @endif
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- ── Notice strip ─────────────────────────────────── --}}
        <tr>
          <td style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:14px 36px;">
            <p style="font-size:.72rem; color:#94a3b8; text-align:center; line-height:1.6; margin:0;">
              This is an official notification from <strong>{{ $co }}</strong>.
              Please do not reply to this email — contact HR directly for any queries.
            </p>
          </td>
        </tr>

        {{-- ── Bottom accent bar ───────────────────────────── --}}
        <tr>
          <td style="height:4px; background:linear-gradient(90deg, #2563eb 0%, {{ $accentHex }} 100%);
                     border-radius:0 0 14px 14px;"></td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
