<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $subject ?? 'Hemdox HRMS' }}</title>
<style>
  body { margin:0; padding:0; background:#f4f6f9; font-family:Arial,Helvetica,sans-serif; }
  a    { color:#4e9af1; }
</style>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

        {{-- Header --}}
        <tr>
          <td style="background:#1a1f2e;border-radius:10px 10px 0 0;padding:24px 32px;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td>
                  <span style="color:#4e9af1;font-size:1.1rem;font-weight:700;">Hemdox</span>
                  <span style="color:#ffffff;font-size:1.1rem;font-weight:700;"> HRMS</span>
                </td>
                <td align="right">
                  <span style="color:rgba(255,255,255,0.4);font-size:0.75rem;">by Hemdox Digital</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Body --}}
        <tr>
          <td style="background:#ffffff;padding:32px;border-left:1px solid #e9ecef;border-right:1px solid #e9ecef;">
            {{ $slot }}
          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td style="background:#f8f9fa;border:1px solid #e9ecef;border-top:none;border-radius:0 0 10px 10px;
                     padding:16px 32px;text-align:center;">
            <p style="margin:0;font-size:0.75rem;color:#6c757d;line-height:1.6;">
              This is an automated message from <strong>Hemdox HRMS</strong>.<br>
              Please do not reply to this email.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
