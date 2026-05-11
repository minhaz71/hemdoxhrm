<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\TestSmtpRequest;
use App\Http\Requests\Settings\UpdateSmtpRequest;
use App\Services\CurrencyService;
use App\Services\SettingService;
use App\Services\SmtpSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingService      $settings,
        private readonly CurrencyService     $currency,
        private readonly SmtpSettingsService $smtp,
    ) {}

    // ── Index ──────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $s = $this->settings;

        return view('settings.index', [
            // Company
            'company' => [
                'name'     => $s->get('company_name',    'Hemdox HRMS'),
                'tagline'  => $s->get('company_tagline', 'by Hemdox Digital'),
                'address'  => $s->get('company_address', ''),
                'phone'    => $s->get('company_phone',   ''),
                'email'    => $s->get('company_email',   ''),
                'website'  => $s->get('company_website', ''),
                'logo'     => $s->get('company_logo',    ''),
            ],

            // Localization
            'localization' => [
                'timezone'       => $s->get('timezone',       'UTC'),
                'date_format'    => $s->get('date_format',    'Y-m-d'),
                'time_format'    => $s->get('time_format',    'H:i'),
                'week_starts_on' => $s->get('week_starts_on', '1'),
            ],

            // Currency (existing)
            'currencyConfig'  => $this->currency->getConfig(),
            'currencyPresets' => CurrencyService::$presets,

            // Attendance
            'attendance' => [
                'work_hours_per_day'      => $s->get('work_hours_per_day',      8),
                'late_grace_minutes'      => $s->get('late_grace_minutes',      15),
                'overtime_threshold_hours'=> $s->get('overtime_threshold_hours','8'),
                'work_days'               => json_decode($s->get('work_days', '["Mon","Tue","Wed","Thu","Fri"]'), true) ?? [],
            ],

            // Leave
            'leave' => [
                'annual_leave_days'    => $s->get('annual_leave_days',    20),
                'sick_leave_days'      => $s->get('sick_leave_days',      10),
                'casual_leave_days'    => $s->get('casual_leave_days',    10),
                'carry_forward_days'   => $s->get('carry_forward_days',   5),
                'max_leave_per_request'=> $s->get('max_leave_per_request',30),
            ],

            // Payroll
            'payroll' => [
                'pay_cycle'                  => $s->get('pay_cycle',                  'monthly'),
                'tax_percentage'             => $s->get('tax_percentage',             '0'),
                'overtime_multiplier'        => $s->get('overtime_multiplier',        '1.5'),
                'payslip_footer_note'        => $s->get('payslip_footer_note',        ''),
                'salary_change_effect_mode'  => $s->get('salary_change_effect_mode',  'month_start'),
                'late_penalty_enabled'       => $s->get('late_penalty_enabled',       true),
                'late_penalty_amount'        => $s->get('late_penalty_amount',        '10'),
                'leave_penalty_enabled'      => $s->get('leave_penalty_enabled',      true),
                'leave_penalty_rate'         => $s->get('leave_penalty_rate',         '1'),
                'overtime_pay_enabled'       => $s->get('overtime_pay_enabled',       false),
            ],

            // SMTP — use service so password is decrypted then re-masked for display
            'smtp' => (function () {
                $cfg = $this->smtp->getConfig();
                return [
                    'enabled'      => $cfg['enabled'],
                    'host'         => $cfg['host'],
                    'port'         => (string) $cfg['port'],
                    'username'     => $cfg['username'],
                    'password'     => $cfg['password'] !== '' ? '••••••••' : '',
                    'encryption'   => $cfg['encryption'],
                    'from_name'    => $cfg['from_name'],
                    'from_address' => $cfg['from_address'],
                    'configured'   => $this->smtp->isConfigured(),
                ];
            })(),

            // Notification routing
            'notifications' => [
                'important_update_emails' => $s->get('important_update_emails', ''),
                'leave_approval_cc_emails' => $s->get('leave_approval_cc_emails', ''),
                'payslip_cc_emails' => $s->get('payslip_cc_emails', ''),
            ],

            // Theme
            'theme' => [
                'primary_color' => $s->get('theme_primary_color', '#4e9af1'),
                'sidebar_color' => $s->get('theme_sidebar_color', '#1a1f2e'),
                'logo_text'     => $s->get('theme_logo_text',     'Hemdox'),
            ],

            // Holiday & Weekly Off
            'holiday' => [
                'email_enabled'       => $s->get('holiday_email_enabled',       true),
                'notify_before_days'  => $s->get('holiday_notify_before_days',  3),
                'hr_manage_holidays'  => $s->get('hr_manage_holidays',          true),
                'attendance_record'   => $s->get('holiday_attendance_record',   'skip'),
            ],
            'weeklyOff' => [
                'hr_manage_weekly_off'          => $s->get('hr_manage_weekly_off',          true),
                'registration_weekly_off_enabled' => $s->get('registration_weekly_off_enabled', true),
            ],

            // Access
            'designationHrAccess' => $s->get('designation_hr_access', false),

            // Misc
            'timezones'  => $this->commonTimezones(),
            'dateFormats'=> $this->dateFormats(),
            'timeFormats'=> $this->timeFormats(),
        ]);
    }

    // ── Company ────────────────────────────────────────────────────

    public function updateCompany(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name'    => ['sometimes', 'required', 'string', 'max:150'],
            'company_tagline' => ['nullable', 'string', 'max:200'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_phone'   => ['nullable', 'string', 'max:50'],
            'company_email'   => ['nullable', 'email', 'max:150'],
            'company_website' => ['nullable', 'url', 'max:200'],
            'enable_branches' => ['nullable', 'in:0,1'],
        ]);

        $this->settings->setMany($data, 'company');

        return response()->json(['success' => true, 'message' => 'Company settings saved.']);
    }

    public function updateLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ]);

        // Remove old logo
        $old = $this->settings->get('company_logo', '');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('logo')->store('settings', 'public');
        $this->settings->set('company_logo', $path, 'Company Logo', null, 'company', 'file');

        return response()->json([
            'success' => true,
            'message' => 'Logo uploaded.',
            'url'     => Storage::disk('public')->url($path),
        ]);
    }

    public function deleteLogo(): JsonResponse
    {
        $path = $this->settings->get('company_logo', '');
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        $this->settings->set('company_logo', '', 'Company Logo', null, 'company', 'file');

        return response()->json(['success' => true, 'message' => 'Logo removed.']);
    }

    // ── Localization ───────────────────────────────────────────────

    public function updateLocalization(Request $request): JsonResponse
    {
        $data = $request->validate([
            'timezone'       => ['required', 'string', 'timezone'],
            'date_format'    => ['required', 'string', 'in:Y-m-d,d/m/Y,m/d/Y,d-m-Y,d M Y,M j\, Y,j F Y'],
            'time_format'    => ['required', 'string', 'in:H:i,g:i A,h:i A'],
            'week_starts_on' => ['required', 'integer', 'in:0,1,6'],
        ]);

        $this->settings->setMany($data, 'localization');

        return response()->json(['success' => true, 'message' => 'Localization settings saved.']);
    }

    // ── Currency (existing, kept for backward compat) ──────────────

    public function updateCurrency(Request $request): JsonResponse
    {
        $data = $request->validate([
            'currency_code'      => ['required', 'string', 'max:10'],
            'currency_symbol'    => ['required', 'string', 'max:10'],
            'currency_position'  => ['required', 'in:before,after'],
            'decimal_separator'  => ['required', 'in:.,\,'],
            'thousand_separator' => ['nullable', 'string', 'max:1'],
        ]);

        $this->settings->setMany($data, 'currency');
        $this->currency->invalidateCache();

        return response()->json([
            'success' => true,
            'message' => "Currency updated to {$data['currency_code']} ({$data['currency_symbol']}).",
            'preview' => $this->currency->format(1234567.89),
        ]);
    }

    // ── Attendance ─────────────────────────────────────────────────

    public function updateAttendance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'work_hours_per_day'       => ['required', 'integer', 'min:1', 'max:24'],
            'late_grace_minutes'       => ['required', 'integer', 'min:0', 'max:120'],
            'overtime_threshold_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'work_days'                => ['required', 'array', 'min:1'],
            'work_days.*'              => ['string', 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun'],
        ]);

        // work_days stored as JSON
        $data['work_days'] = json_encode($data['work_days']);

        $this->settings->setMany($data, 'attendance');

        return response()->json(['success' => true, 'message' => 'Attendance rules saved.']);
    }

    // ── Leave ──────────────────────────────────────────────────────

    public function updateLeave(Request $request): JsonResponse
    {
        $data = $request->validate([
            'annual_leave_days'     => ['required', 'integer', 'min:0', 'max:365'],
            'sick_leave_days'       => ['required', 'integer', 'min:0', 'max:365'],
            'casual_leave_days'     => ['required', 'integer', 'min:0', 'max:365'],
            'carry_forward_days'    => ['required', 'integer', 'min:0', 'max:365'],
            'max_leave_per_request' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $this->settings->setMany($data, 'leave');

        return response()->json(['success' => true, 'message' => 'Leave limits saved.']);
    }

    // ── Payroll ────────────────────────────────────────────────────

    public function updatePayroll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pay_cycle'                 => ['required', 'in:weekly,biweekly,monthly'],
            'tax_percentage'            => ['required', 'numeric', 'min:0', 'max:100'],
            'overtime_multiplier'       => ['required', 'numeric', 'min:1', 'max:10'],
            'payslip_footer_note'       => ['nullable', 'string', 'max:500'],
            'salary_change_effect_mode' => ['required', 'in:month_start,month_end,prorated'],
            'late_penalty_enabled'      => ['required', 'in:true,false,0,1'],
            'late_penalty_amount'       => ['required', 'numeric', 'min:0', 'max:100000'],
            'leave_penalty_enabled'     => ['required', 'in:true,false,0,1'],
            'leave_penalty_rate'        => ['required', 'numeric', 'min:0', 'max:10'],
            'overtime_pay_enabled'      => ['required', 'in:true,false,0,1'],
        ]);

        foreach (['late_penalty_enabled', 'leave_penalty_enabled', 'overtime_pay_enabled'] as $key) {
            $data[$key] = in_array($data[$key], ['true', '1', true, 1], true) ? 'true' : 'false';
        }

        $this->settings->setMany($data, 'payroll');

        return response()->json(['success' => true, 'message' => 'Payroll rules saved.']);
    }

    // ── SMTP ───────────────────────────────────────────────────────

    public function updateSmtp(UpdateSmtpRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->smtp->save($data);

        // Re-apply runtime config so subsequent mail calls in this request use new settings
        $this->smtp->applyToRuntime();

        return response()->json(['success' => true, 'message' => 'SMTP settings saved.']);
    }

    public function toggleSmtp(Request $request): JsonResponse
    {
        $enabled = (bool) $request->input('enabled', false);
        $this->smtp->toggleEnabled($enabled);
        $this->smtp->applyToRuntime();

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled ? 'Email sending enabled.' : 'Email sending disabled.',
        ]);
    }

    public function testSmtp(TestSmtpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->smtp->testConnection($validated['email'], company_name());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $data = $request->validate([
            'important_update_emails' => ['nullable', 'string', 'max:2000'],
            'leave_approval_cc_emails' => ['nullable', 'string', 'max:2000'],
            'payslip_cc_emails' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ($data as $key => $value) {
            $emails = $this->normalizeEmailList((string) $value);
            if ($emails === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more notification email addresses are invalid.',
                ], 422);
            }
            $data[$key] = implode("\n", $emails);
        }

        $this->settings->setMany($data, 'notifications');

        return response()->json(['success' => true, 'message' => 'Notification recipients saved.']);
    }

    public function gmailGuide(): \Illuminate\View\View
    {
        $smtpCfg = $this->smtp->getConfig();

        return view('settings.smtp.gmail-guide', [
            'currentHost'     => $smtpCfg['host'],
            'isGmailConfig'   => str_contains($smtpCfg['host'], 'gmail.com'),
            'isConfigured'    => $this->smtp->isConfigured(),
        ]);
    }

    // ── Theme ──────────────────────────────────────────────────────

    public function updateTheme(Request $request): JsonResponse
    {
        $data = $request->validate([
            'theme_primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_sidebar_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_logo_text'     => ['required', 'string', 'max:30'],
        ]);

        $this->settings->setMany($data, 'theme');

        return response()->json(['success' => true, 'message' => 'Theme settings saved.']);
    }

    // ── Holiday & Weekly Off ───────────────────────────────────────

    public function updateHoliday(Request $request): JsonResponse
    {
        $data = $request->validate([
            'holiday_email_enabled'      => ['required', 'in:true,false,0,1'],
            'holiday_notify_before_days' => ['required', 'integer', 'min:0', 'max:30'],
            'hr_manage_holidays'         => ['required', 'in:true,false,0,1'],
            'holiday_attendance_record'  => ['required', 'in:skip,record'],
        ]);

        // Normalize bool-like values
        foreach (['holiday_email_enabled', 'hr_manage_holidays'] as $key) {
            $data[$key] = in_array($data[$key], ['true', '1', true, 1], true) ? 'true' : 'false';
        }

        $this->settings->setMany($data, 'holiday');

        return response()->json(['success' => true, 'message' => 'Holiday settings saved.']);
    }

    public function updateWeeklyOff(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hr_manage_weekly_off'           => ['required', 'in:true,false,0,1'],
            'registration_weekly_off_enabled' => ['required', 'in:true,false,0,1'],
        ]);

        foreach (['hr_manage_weekly_off', 'registration_weekly_off_enabled'] as $key) {
            $data[$key] = in_array($data[$key], ['true', '1', true, 1], true) ? 'true' : 'false';
        }

        $this->settings->setMany($data, 'weekly_off');

        return response()->json(['success' => true, 'message' => 'Weekly off settings saved.']);
    }

    // ── Access (existing) ──────────────────────────────────────────

    public function updateDesignationAccess(): JsonResponse
    {
        $current = $this->settings->get('designation_hr_access', false);
        $this->settings->set('designation_hr_access', ! $current);

        return response()->json([
            'success' => true,
            'enabled' => ! $current,
            'message' => 'Setting saved.',
        ]);
    }

    // ── Lookup data ────────────────────────────────────────────────

    private function commonTimezones(): array
    {
        return [
            'UTC'                    => 'UTC (GMT+0)',
            'America/New_York'       => 'Eastern Time (US & Canada)',
            'America/Chicago'        => 'Central Time (US & Canada)',
            'America/Denver'         => 'Mountain Time (US & Canada)',
            'America/Los_Angeles'    => 'Pacific Time (US & Canada)',
            'America/Sao_Paulo'      => 'Brasilia (Brazil)',
            'America/Argentina/Buenos_Aires' => 'Buenos Aires (Argentina)',
            'Europe/London'          => 'London (GMT/BST)',
            'Europe/Paris'           => 'Paris / Central European Time',
            'Europe/Berlin'          => 'Berlin / Central European Time',
            'Europe/Moscow'          => 'Moscow Time',
            'Africa/Cairo'           => 'Cairo (EET)',
            'Africa/Lagos'           => 'Lagos (WAT)',
            'Africa/Nairobi'         => 'Nairobi (EAT)',
            'Asia/Dhaka'             => 'Dhaka (BST +6)',
            'Asia/Kolkata'           => 'India Standard Time',
            'Asia/Karachi'           => 'Pakistan Standard Time',
            'Asia/Dubai'             => 'Dubai (GST)',
            'Asia/Riyadh'            => 'Riyadh (AST)',
            'Asia/Singapore'         => 'Singapore Time',
            'Asia/Tokyo'             => 'Japan Standard Time',
            'Asia/Shanghai'          => 'China Standard Time',
            'Asia/Seoul'             => 'Korea Standard Time',
            'Australia/Sydney'       => 'Sydney (AEDT/AEST)',
            'Pacific/Auckland'       => 'New Zealand Time',
        ];
    }

    private function normalizeEmailList(string $value): array|false
    {
        $items = collect(preg_split('/[\s,;]+/', $value) ?: [])
            ->map(fn ($email) => strtolower(trim($email)))
            ->filter()
            ->unique()
            ->values();

        $invalid = $items->first(fn ($email) => ! filter_var($email, FILTER_VALIDATE_EMAIL));

        return $invalid ? false : $items->all();
    }

    private function dateFormats(): array
    {
        $sample = \Carbon\Carbon::parse('2026-05-09');
        $formats = [
            'Y-m-d'     => 'Y-m-d',
            'd/m/Y'     => 'd/m/Y',
            'm/d/Y'     => 'm/d/Y',
            'd-m-Y'     => 'd-m-Y',
            'd M Y'     => 'd M Y',
            'M j\, Y'   => 'M j, Y',
            'j F Y'     => 'j F Y',
        ];

        return array_map(fn ($fmt) => $sample->format($fmt), $formats);
    }

    private function timeFormats(): array
    {
        $sample = \Carbon\Carbon::parse('14:05:00');
        return [
            'H:i'   => $sample->format('H:i')   . ' (24-hour)',
            'g:i A' => $sample->format('g:i A') . ' (12-hour)',
            'h:i A' => $sample->format('h:i A') . ' (12-hour, zero-padded)',
        ];
    }
}
