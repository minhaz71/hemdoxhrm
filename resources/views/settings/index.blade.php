<x-app-layout>
    <x-slot name="title">System Settings</x-slot>

    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">System Settings</h5>
            <small class="text-muted">Configure company info, rules, email, and appearance</small>
        </div>
    </div>

    {{-- Toast --}}
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1200">
        <div id="sToast" class="toast align-items-center text-white border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="sToastMsg"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <div class="row g-0">

        {{-- ── Vertical Tab Nav ─────────────────────────────────────── --}}
        <div class="col-auto" style="width:220px;">
            <div class="hrms-card me-3 overflow-hidden" style="position:sticky;top:76px;">
                <div class="nav flex-column nav-pills p-2" id="settingsTabs" role="tablist">
                    @php
                        $tabs = [
                            ['id'=>'company',      'icon'=>'bi-building',         'label'=>'Company'],
                            ['id'=>'localization', 'icon'=>'bi-globe',            'label'=>'Localization'],
                            ['id'=>'currency',     'icon'=>'bi-currency-exchange', 'label'=>'Currency'],
                            ['id'=>'attendance',   'icon'=>'bi-clock-history',    'label'=>'Attendance'],
                            ['id'=>'holiday',      'icon'=>'bi-calendar-heart',   'label'=>'Holiday & W.Off'],
                            ['id'=>'leave',        'icon'=>'bi-calendar-check',   'label'=>'Leave'],
                            ['id'=>'payroll',      'icon'=>'bi-cash-stack',       'label'=>'Payroll'],
                            ['id'=>'smtp',         'icon'=>'bi-envelope-at',      'label'=>'Email / SMTP'],
                            ['id'=>'theme',        'icon'=>'bi-palette',          'label'=>'Theme'],
                            ['id'=>'access',       'icon'=>'bi-shield-lock',      'label'=>'Access'],
                        ];
                    @endphp
                    @foreach($tabs as $i => $tab)
                    <button class="nav-link text-start d-flex align-items-center gap-2 py-2 px-3 {{ $i === 0 ? 'active' : '' }}"
                            id="tab-{{ $tab['id'] }}"
                            data-bs-toggle="pill"
                            data-bs-target="#panel-{{ $tab['id'] }}"
                            type="button" role="tab"
                            style="font-size:.85rem;border-radius:6px;">
                        <i class="bi {{ $tab['icon'] }}" style="font-size:.9rem;min-width:16px;"></i>
                        {{ $tab['label'] }}
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Tab Content ──────────────────────────────────────────── --}}
        <div class="col">
            <div class="tab-content" id="settingsTabContent">

                {{-- ══ COMPANY ════════════════════════════════════════ --}}
                <div class="tab-pane fade show active" id="panel-company" role="tabpanel">
                    <div class="hrms-card p-4">
                        <h6 class="fw-semibold mb-1"><i class="bi bi-building me-2 text-primary"></i>Company Information</h6>
                        <small class="text-muted d-block mb-4">Displayed in the sidebar, payslips, and reports.</small>

                        {{-- Logo --}}
                        <div class="mb-4 p-3 bg-light rounded">
                            <label class="form-label fw-semibold">Company Logo</label>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div id="logoPreviewWrap" class="{{ $company['logo'] ? '' : 'd-none' }}">
                                    <img id="logoPreview"
                                         src="{{ $company['logo'] ? Storage::disk('public')->url($company['logo']) : '' }}"
                                         alt="Logo" style="max-height:60px;max-width:160px;object-fit:contain;border-radius:6px;">
                                </div>
                                <div id="logoPlaceholder" class="{{ $company['logo'] ? 'd-none' : '' }} text-muted small border rounded p-3 text-center" style="min-width:100px;">
                                    <i class="bi bi-image fs-3 d-block mb-1 opacity-25"></i>No logo set
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <label class="btn btn-outline-primary btn-sm mb-0" style="cursor:pointer;">
                                        <i class="bi bi-upload me-1"></i> Upload Logo
                                        <input type="file" id="logoFile" accept="image/*" class="d-none">
                                    </label>
                                    <button class="btn btn-outline-danger btn-sm {{ $company['logo'] ? '' : 'd-none' }}" id="btnDeleteLogo">
                                        <i class="bi bi-trash me-1"></i> Remove
                                    </button>
                                </div>
                                <small class="text-muted">PNG, JPG, SVG · Max 2 MB</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Company Name <span class="text-danger">*</span></label>
                                <input type="text" id="company_name" class="form-control" value="{{ $company['name'] }}" maxlength="150">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tagline / Sub-brand</label>
                                <input type="text" id="company_tagline" class="form-control" value="{{ $company['tagline'] }}" maxlength="200">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Email</label>
                                <input type="email" id="company_email" class="form-control" value="{{ $company['email'] }}" maxlength="150">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" id="company_phone" class="form-control" value="{{ $company['phone'] }}" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Website URL</label>
                                <input type="url" id="company_website" class="form-control" value="{{ $company['website'] }}" maxlength="200">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea id="company_address" class="form-control" rows="3" maxlength="500" placeholder="Street, City, Country">{{ $company['address'] }}</textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-primary" onclick="saveSection('company', '/settings/company', ['company_name','company_tagline','company_address','company_phone','company_email','company_website'])">
                                <i class="bi bi-check2 me-1"></i> Save Company Info
                            </button>
                        </div>
                    </div>

                    {{-- Branch Feature Toggle --}}
                    <div class="hrms-card p-4 mt-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0 mt-1" style="width:40px;height:40px;background:#e8f4fd;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-geo-alt text-primary fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold mb-1">Multiple Branch Support</div>
                                <div class="text-muted small mb-3">
                                    Does your company operate from more than one location?<br>
                                    When enabled, you can add branches, assign employees to branches, and filter records branch-wise.
                                    When disabled, all branch fields are hidden across the system.
                                </div>

                                <div class="d-flex align-items-center gap-4 flex-wrap">
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="enable_branches_radio" id="branches_yes" value="1"
                                               {{ setting('enable_branches','0') === '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="branches_yes">
                                            <i class="bi bi-check-circle-fill text-success me-1"></i> Yes, we have multiple branches
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="enable_branches_radio" id="branches_no" value="0"
                                               {{ setting('enable_branches','0') !== '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="branches_no">
                                            <i class="bi bi-x-circle-fill text-secondary me-1"></i> No, single location only
                                        </label>
                                    </div>
                                </div>

                                <div id="branchesEnabledNote" class="{{ setting('enable_branches','0') === '1' ? '' : 'd-none' }} mt-3 alert alert-info alert-sm p-2 mb-0" style="font-size:.82rem;">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Branch management is active. Go to
                                    <a href="{{ route('company.index') }}" class="alert-link">Company Profile → Branches</a>
                                    to add or manage branches.
                                </div>

                                <div class="mt-3">
                                    <button class="btn btn-primary btn-sm" onclick="saveBranchToggle()">
                                        <i class="bi bi-floppy me-1"></i> Save Preference
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══ LOCALIZATION ═════════════════════════════════════ --}}
                <div class="tab-pane fade" id="panel-localization" role="tabpanel">
                    <div class="hrms-card p-4">
                        <h6 class="fw-semibold mb-1"><i class="bi bi-globe me-2 text-primary"></i>Localization</h6>
                        <small class="text-muted d-block mb-4">Timezone, date format, and calendar preferences.</small>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Timezone <span class="text-danger">*</span></label>
                                <select id="timezone" class="form-select">
                                    @foreach($timezones as $value => $label)
                                    <option value="{{ $value }}" {{ $localization['timezone'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date Format <span class="text-danger">*</span></label>
                                <select id="date_format" class="form-select">
                                    @foreach($dateFormats as $fmt => $sample)
                                    <option value="{{ $fmt }}" {{ $localization['date_format'] === $fmt ? 'selected' : '' }}>{{ $sample }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Sample: <span id="dateSample">{{ \Carbon\Carbon::parse('2026-05-09')->format($localization['date_format']) }}</span></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Time Format <span class="text-danger">*</span></label>
                                <select id="time_format" class="form-select">
                                    @foreach($timeFormats as $fmt => $sample)
                                    <option value="{{ $fmt }}" {{ $localization['time_format'] === $fmt ? 'selected' : '' }}>{{ $sample }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Week Starts On</label>
                                <select id="week_starts_on" class="form-select">
                                    <option value="1" {{ $localization['week_starts_on'] == '1' ? 'selected' : '' }}>Monday</option>
                                    <option value="0" {{ $localization['week_starts_on'] == '0' ? 'selected' : '' }}>Sunday</option>
                                    <option value="6" {{ $localization['week_starts_on'] == '6' ? 'selected' : '' }}>Saturday</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-primary" onclick="saveSection('localization', '/settings/localization', ['timezone','date_format','time_format','week_starts_on'])">
                                <i class="bi bi-check2 me-1"></i> Save Localization
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ══ CURRENCY ═════════════════════════════════════════ --}}
                <div class="tab-pane fade" id="panel-currency" role="tabpanel">
                    <div class="hrms-card p-4">
                        <h6 class="fw-semibold mb-1"><i class="bi bi-currency-exchange me-2 text-primary"></i>Currency Settings</h6>
                        <small class="text-muted d-block mb-4">Applied to all salary, payroll, and report views.</small>

                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="text-muted small">Live Preview:</span>
                            <span class="badge bg-primary-subtle text-primary px-3 py-2 fs-6" id="currPreviewBadge">{{ currency(1234567.89) }}</span>
                        </div>

                        {{-- Presets --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Quick Select</label>
                            <div class="row g-2" id="presetCards">
                                @foreach($currencyPresets as $code => $preset)
                                <div class="col-6 col-sm-4 col-md-2">
                                    <div class="preset-card border rounded p-2 text-center {{ $currencyConfig['code'] === $code ? 'border-primary bg-primary-subtle' : 'border-secondary-subtle' }}"
                                         data-code="{{ $code }}" data-symbol="{{ $preset['symbol'] }}"
                                         data-position="{{ $preset['position'] }}" data-decimal="{{ $preset['decimal'] }}"
                                         data-thousand="{{ $preset['thousand'] }}"
                                         style="cursor:pointer;transition:all .15s;">
                                        <div style="font-size:1.3rem;">{{ $preset['symbol'] }}</div>
                                        <div class="fw-semibold" style="font-size:.8rem;">{{ $code }}</div>
                                        <div class="text-muted" style="font-size:.7rem;">{{ $preset['name'] }}</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Currency Code</label>
                                <input type="text" id="currency_code" class="form-control" value="{{ $currencyConfig['code'] }}" maxlength="10">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Symbol</label>
                                <input type="text" id="currency_symbol" class="form-control" value="{{ $currencyConfig['symbol'] }}" maxlength="10">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Position</label>
                                <select id="currency_position" class="form-select">
                                    <option value="before" {{ $currencyConfig['position']==='before' ? 'selected' : '' }}>Before ($ 100)</option>
                                    <option value="after"  {{ $currencyConfig['position']==='after'  ? 'selected' : '' }}>After (100 $)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Decimal Separator</label>
                                <select id="decimal_separator" class="form-select">
                                    <option value="." {{ $currencyConfig['decimal']==='.' ? 'selected' : '' }}>Dot ( 1.50 )</option>
                                    <option value="," {{ $currencyConfig['decimal']===',' ? 'selected' : '' }}>Comma ( 1,50 )</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Thousand Separator</label>
                                <select id="thousand_separator" class="form-select">
                                    <option value="," {{ $currencyConfig['thousand']===',' ? 'selected' : '' }}>Comma ( 1,000 )</option>
                                    <option value="." {{ $currencyConfig['thousand']==='.' ? 'selected' : '' }}>Dot ( 1.000 )</option>
                                    <option value=" " {{ $currencyConfig['thousand']===' ' ? 'selected' : '' }}>Space ( 1 000 )</option>
                                    <option value=""  {{ $currencyConfig['thousand']===''  ? 'selected' : '' }}>None ( 1000 )</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-primary" id="btnSaveCurrency">
                                <i class="bi bi-check2 me-1"></i> Save Currency Settings
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ══ ATTENDANCE ═══════════════════════════════════════ --}}
                <div class="tab-pane fade" id="panel-attendance" role="tabpanel">
                    <div class="hrms-card p-4">
                        <h6 class="fw-semibold mb-1"><i class="bi bi-clock-history me-2 text-primary"></i>Attendance Rules</h6>
                        <small class="text-muted d-block mb-4">Governs work hours, grace periods, and overtime thresholds.</small>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Work Hours Per Day <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" id="work_hours_per_day" class="form-control" value="{{ $attendance['work_hours_per_day'] }}" min="1" max="24">
                                    <span class="input-group-text">hrs</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Late Arrival Grace Period</label>
                                <div class="input-group">
                                    <input type="number" id="late_grace_minutes" class="form-control" value="{{ $attendance['late_grace_minutes'] }}" min="0" max="120">
                                    <span class="input-group-text">min</span>
                                </div>
                                <div class="form-text">Minutes after shift start before marking late.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Overtime After</label>
                                <div class="input-group">
                                    <input type="number" id="overtime_threshold_hours" class="form-control" value="{{ $attendance['overtime_threshold_hours'] }}" min="0" max="24" step="0.5">
                                    <span class="input-group-text">hrs</span>
                                </div>
                                <div class="form-text">Hours worked beyond this are overtime.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Work Days <span class="text-danger">*</span></label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                                    <label class="d-flex align-items-center gap-1 px-3 py-2 border rounded workday-chip {{ in_array($day, $attendance['work_days']) ? 'border-primary bg-primary-subtle' : 'border-light bg-light' }}" style="cursor:pointer;">
                                        <input type="checkbox" name="work_days[]" value="{{ $day }}" class="workday-cb d-none" {{ in_array($day, $attendance['work_days']) ? 'checked' : '' }}>
                                        <span style="font-size:.85rem;font-weight:600;">{{ $day }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-primary" id="btnSaveAttendance">
                                <i class="bi bi-check2 me-1"></i> Save Attendance Rules
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ══ HOLIDAY & WEEKLY OFF ══════════════════════════════ --}}
                <div class="tab-pane fade" id="panel-holiday" role="tabpanel">

                    {{-- ─── Holiday Email Notifications ────────────────── --}}
                    <div class="hrms-card p-4 mb-3">
                        <h6 class="fw-semibold mb-1">
                            <i class="bi bi-envelope-heart me-2 text-primary"></i>Holiday Email Notifications
                        </h6>
                        <small class="text-muted d-block mb-4">
                            Controls whether the system automatically emails employees before upcoming holidays.
                        </small>

                        {{-- Master switch --}}
                        <div class="d-flex align-items-center justify-content-between mb-4 p-3 rounded border">
                            <div>
                                <div class="fw-semibold" style="font-size:.9rem;">Enable Holiday Emails</div>
                                <div class="text-muted" style="font-size:.8rem;">
                                    When on, the daily cron sends reminder emails before each upcoming holiday.
                                </div>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="holiday_email_enabled" style="width:2.5em;height:1.4em;cursor:pointer;"
                                       {{ $holiday['email_enabled'] === true || $holiday['email_enabled'] === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="holiday_email_enabled" id="holidayEmailLabel">
                                    {{ ($holiday['email_enabled'] === true || $holiday['email_enabled'] === 'true') ? 'Enabled' : 'Disabled' }}
                                </label>
                            </div>
                        </div>

                        {{-- Default notify before days --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">
                                    Default Notify Before <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" id="holiday_notify_before_days" class="form-control"
                                           value="{{ $holiday['notify_before_days'] }}" min="0" max="30">
                                    <span class="input-group-text">days</span>
                                </div>
                                <div class="form-text">
                                    Applied to holidays where "Notify Before" is not set individually.
                                    0 = send on the holiday start day.
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-primary" id="btnSaveHolidayEmail">
                            <i class="bi bi-check2 me-1"></i> Save Email Settings
                        </button>
                    </div>

                    {{-- ─── Attendance Behavior ─────────────────────────── --}}
                    <div class="hrms-card p-4 mb-3">
                        <h6 class="fw-semibold mb-1">
                            <i class="bi bi-calendar-event me-2 text-primary"></i>Attendance Behavior on Non-Working Days
                        </h6>
                        <small class="text-muted d-block mb-4">
                            Controls what the daily absent cron creates for holidays and weekly offs.
                        </small>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold">When an employee has a holiday or weekly off</label>
                                <div class="d-flex flex-column gap-2 mt-2">
                                    <label class="d-flex align-items-start gap-3 p-3 border rounded"
                                           style="cursor:pointer;" id="lbl-skip">
                                        <input type="radio" name="holiday_attendance_record" id="att_skip"
                                               value="skip" class="form-check-input mt-1 flex-shrink-0"
                                               {{ $holiday['attendance_record'] === 'skip' ? 'checked' : '' }}>
                                        <div>
                                            <div class="fw-semibold" style="font-size:.9rem;">Skip — do not create any attendance record</div>
                                            <div class="text-muted" style="font-size:.8rem;">
                                                Legacy behaviour. No row is written; reports infer the day type from the calendar.
                                                <strong>Weekly offs are always recorded regardless of this setting.</strong>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="d-flex align-items-start gap-3 p-3 border rounded"
                                           style="cursor:pointer;" id="lbl-record">
                                        <input type="radio" name="holiday_attendance_record" id="att_record"
                                               value="record" class="form-check-input mt-1 flex-shrink-0"
                                               {{ $holiday['attendance_record'] === 'record' ? 'checked' : '' }}>
                                        <div>
                                            <div class="fw-semibold" style="font-size:.9rem;">Record — create a <code>holiday</code> attendance row</div>
                                            <div class="text-muted" style="font-size:.8rem;">
                                                A holiday row is created for each employee on each holiday.
                                                Useful for attendance exports and payslip detail.
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-primary" id="btnSaveAttendanceBehavior">
                            <i class="bi bi-check2 me-1"></i> Save Attendance Behavior
                        </button>
                    </div>

                    {{-- ─── Access Permissions ──────────────────────────── --}}
                    <div class="hrms-card p-4">
                        <h6 class="fw-semibold mb-1">
                            <i class="bi bi-shield-check me-2 text-primary"></i>Management Permissions
                        </h6>
                        <small class="text-muted d-block mb-4">
                            Control which roles can manage holidays and weekly offs. When disabled, only Admins have write access.
                        </small>

                        {{-- HR manage holidays --}}
                        <div class="d-flex align-items-center justify-content-between mb-3 p-3 rounded border">
                            <div>
                                <div class="fw-semibold" style="font-size:.9rem;">
                                    <i class="bi bi-sun me-1 text-warning"></i>Allow HR to Manage Holidays
                                </div>
                                <div class="text-muted" style="font-size:.8rem;">
                                    HR users can create, edit and toggle holidays. When off, only Admin can.
                                </div>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="hr_manage_holidays" style="width:2.5em;height:1.4em;cursor:pointer;"
                                       {{ $holiday['hr_manage_holidays'] === true || $holiday['hr_manage_holidays'] === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="hr_manage_holidays"></label>
                            </div>
                        </div>

                        {{-- HR manage weekly off --}}
                        <div class="d-flex align-items-center justify-content-between mb-3 p-3 rounded border">
                            <div>
                                <div class="fw-semibold" style="font-size:.9rem;">
                                    <i class="bi bi-calendar2-week me-1 text-secondary"></i>Allow HR to Manage Weekly Offs
                                </div>
                                <div class="text-muted" style="font-size:.8rem;">
                                    HR users can assign, edit, and deactivate employee weekly-off schedules.
                                </div>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="hr_manage_weekly_off" style="width:2.5em;height:1.4em;cursor:pointer;"
                                       {{ $weeklyOff['hr_manage_weekly_off'] === true || $weeklyOff['hr_manage_weekly_off'] === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="hr_manage_weekly_off"></label>
                            </div>
                        </div>

                        {{-- Registration weekly off --}}
                        <div class="d-flex align-items-center justify-content-between p-3 rounded border">
                            <div>
                                <div class="fw-semibold" style="font-size:.9rem;">
                                    <i class="bi bi-person-plus me-1 text-primary"></i>Employee Weekly Off During Registration
                                </div>
                                <div class="text-muted" style="font-size:.8rem;">
                                    Show the weekly off selector on the invitation-based registration form.
                                    HR reviews and approves the choice before the account is activated.
                                </div>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="registration_weekly_off_enabled" style="width:2.5em;height:1.4em;cursor:pointer;"
                                       {{ $weeklyOff['registration_weekly_off_enabled'] === true || $weeklyOff['registration_weekly_off_enabled'] === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="registration_weekly_off_enabled"></label>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-primary" id="btnSaveHolidayAccess">
                                <i class="bi bi-check2 me-1"></i> Save Permission Settings
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ══ LEAVE ════════════════════════════════════════════ --}}
                <div class="tab-pane fade" id="panel-leave" role="tabpanel">
                    <div class="hrms-card p-4">
                        <h6 class="fw-semibold mb-1"><i class="bi bi-calendar-check me-2 text-primary"></i>Leave Limits</h6>
                        <small class="text-muted d-block mb-4">Default entitlements and rules for leave applications.</small>

                        <div class="row g-3">
                            @foreach([
                                ['key'=>'annual_leave_days',     'label'=>'Annual Leave',       'help'=>'Paid leave days per year'],
                                ['key'=>'sick_leave_days',        'label'=>'Sick Leave',         'help'=>'Sick leave days per year'],
                                ['key'=>'casual_leave_days',      'label'=>'Casual Leave',       'help'=>'Casual leave days per year'],
                                ['key'=>'carry_forward_days',     'label'=>'Carry-Forward (max)','help'=>'Max unused days rolled to next year'],
                                ['key'=>'max_leave_per_request',  'label'=>'Max Per Request',    'help'=>'Max days in one leave application'],
                            ] as $field)
                            <div class="col-md-4">
                                <label class="form-label">{{ $field['label'] }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" id="{{ $field['key'] }}" class="form-control"
                                           value="{{ $leave[$field['key']] }}" min="0" max="365">
                                    <span class="input-group-text">days</span>
                                </div>
                                <div class="form-text">{{ $field['help'] }}</div>
                            </div>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-primary" onclick="saveSection('leave', '/settings/leave', ['annual_leave_days','sick_leave_days','casual_leave_days','carry_forward_days','max_leave_per_request'])">
                                <i class="bi bi-check2 me-1"></i> Save Leave Limits
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ══ PAYROLL ══════════════════════════════════════════ --}}
                <div class="tab-pane fade" id="panel-payroll" role="tabpanel">
                    <div class="hrms-card p-4">
                        <h6 class="fw-semibold mb-1"><i class="bi bi-cash-stack me-2 text-primary"></i>Payroll Rules</h6>
                        <small class="text-muted d-block mb-4">Pay cycle, tax, and overtime defaults.</small>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Pay Cycle <span class="text-danger">*</span></label>
                                <select id="pay_cycle" class="form-select">
                                    <option value="weekly"   {{ $payroll['pay_cycle']==='weekly'   ? 'selected':'' }}>Weekly</option>
                                    <option value="biweekly" {{ $payroll['pay_cycle']==='biweekly' ? 'selected':'' }}>Bi-Weekly</option>
                                    <option value="monthly"  {{ $payroll['pay_cycle']==='monthly'  ? 'selected':'' }}>Monthly</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Default Tax Rate (%)</label>
                                <div class="input-group">
                                    <input type="number" id="tax_percentage" class="form-control" value="{{ $payroll['tax_percentage'] }}" min="0" max="100" step="0.01">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Overtime Pay Multiplier</label>
                                <div class="input-group">
                                    <span class="input-group-text">×</span>
                                    <input type="number" id="overtime_multiplier" class="form-control" value="{{ $payroll['overtime_multiplier'] }}" min="1" max="10" step="0.1">
                                </div>
                                <div class="form-text">e.g. 1.5 = time-and-a-half</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Payslip Footer Note</label>
                                <textarea id="payslip_footer_note" class="form-control" rows="2" maxlength="500">{{ $payroll['payslip_footer_note'] }}</textarea>
                                <div class="form-text">Printed at the bottom of every payslip.</div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-primary" onclick="saveSection('payroll', '/settings/payroll', ['pay_cycle','tax_percentage','overtime_multiplier','payslip_footer_note'])">
                                <i class="bi bi-check2 me-1"></i> Save Payroll Rules
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ══ SMTP ════════════════════════════════════════════ --}}
                <div class="tab-pane fade" id="panel-smtp" role="tabpanel">

                    {{-- ── Status + Toggle bar ──────────────────────────── --}}
                    <div class="hrms-card p-4 mb-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:42px;height:42px;background:#e8f4fd;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="bi bi-envelope-at text-primary fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold mb-0">Email / SMTP Settings</div>
                                    <div class="text-muted" style="font-size:.8rem;">
                                        @if($smtp['configured'])
                                            <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Configured</span>
                                            — {{ $smtp['host'] }}:{{ $smtp['port'] }}
                                        @else
                                            <span class="text-warning fw-semibold"><i class="bi bi-exclamation-circle-fill me-1"></i>Not configured</span>
                                            — fill in host and sender address to enable
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-muted small fw-semibold">Outgoing mail</span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="mailEnabledToggle" style="width:2.5em;height:1.4em;cursor:pointer;"
                                           {{ $smtp['enabled'] ? 'checked' : '' }}
                                           {{ !$smtp['configured'] ? 'disabled' : '' }}>
                                    <label class="form-check-label fw-semibold" for="mailEnabledToggle" id="mailEnabledLabel">
                                        {{ $smtp['enabled'] ? 'Enabled' : 'Disabled' }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Quick Presets ──────────────────────────────────── --}}
                    <div class="hrms-card p-4 mb-3">
                        <div class="fw-semibold mb-2" style="font-size:.85rem;">Quick Presets</div>
                        <div class="d-flex flex-wrap gap-2" id="smtpPresets">
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="smtp.gmail.com" data-port="587" data-enc="tls">
                                <i class="bi bi-google me-1"></i>Gmail
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="smtp.office365.com" data-port="587" data-enc="tls">
                                <i class="bi bi-microsoft me-1"></i>Outlook / Microsoft 365
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="smtp.sendgrid.net" data-port="587" data-enc="tls">
                                <i class="bi bi-lightning-charge me-1"></i>SendGrid
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="smtp.mailgun.org" data-port="587" data-enc="tls">
                                <i class="bi bi-envelope-paper me-1"></i>Mailgun
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm preset-btn"
                                    data-host="sandbox.smtp.mailtrap.io" data-port="2525" data-enc="tls">
                                <i class="bi bi-bug me-1"></i>Mailtrap (dev)
                            </button>
                        </div>
                        <div class="form-text mt-1">Clicking a preset fills Host, Port, and Encryption — you still need to enter credentials.</div>
                    </div>

                    {{-- ── SMTP Form ──────────────────────────────────────── --}}
                    <div class="hrms-card p-4 mb-3">
                        <h6 class="fw-semibold mb-3" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;">
                            <i class="bi bi-sliders me-1"></i>Connection Settings
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">SMTP Host <span class="text-danger">*</span></label>
                                <input type="text" id="smtp_host" class="form-control font-monospace"
                                       value="{{ $smtp['host'] }}" placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Port</label>
                                <input type="number" id="smtp_port" class="form-control font-monospace"
                                       value="{{ $smtp['port'] }}" min="1" max="65535">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Encryption</label>
                                <select id="smtp_encryption" class="form-select">
                                    <option value="tls"      {{ $smtp['encryption']==='tls'      ? 'selected':'' }}>TLS (587)</option>
                                    <option value="ssl"      {{ $smtp['encryption']==='ssl'      ? 'selected':'' }}>SSL (465)</option>
                                    <option value="starttls" {{ $smtp['encryption']==='starttls' ? 'selected':'' }}>STARTTLS (587)</option>
                                    <option value=""         {{ $smtp['encryption']===''         ? 'selected':'' }}>None (25)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" id="smtp_username" class="form-control"
                                       value="{{ $smtp['username'] }}" placeholder="your@email.com" autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" id="smtp_password" class="form-control"
                                           value="{{ $smtp['password'] }}" placeholder="{{ $smtp['password'] ? 'Leave blank to keep current password' : 'Enter SMTP password' }}"
                                           autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleSmtpPass" title="Toggle visibility">
                                        <i class="bi bi-eye" id="toggleSmtpPassIcon"></i>
                                    </button>
                                </div>
                                @if($smtp['password'])
                                <div class="form-text"><i class="bi bi-lock-fill text-success me-1"></i>Password saved (encrypted). Leave blank to keep it.</div>
                                @endif
                            </div>
                        </div>

                        <h6 class="fw-semibold mt-4 mb-3" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;">
                            <i class="bi bi-person-lines-fill me-1"></i>Sender Identity
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">From Name <span class="text-danger">*</span></label>
                                <input type="text" id="mail_from_name" class="form-control"
                                       value="{{ $smtp['from_name'] }}" placeholder="{{ config('app.name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">From Email Address <span class="text-danger">*</span></label>
                                <input type="email" id="mail_from_address" class="form-control"
                                       value="{{ $smtp['from_address'] }}" placeholder="noreply@yourcompany.com">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-primary" id="btnSaveSmtp">
                                <i class="bi bi-check2 me-1"></i> Save SMTP Settings
                            </button>
                        </div>
                    </div>

                    {{-- ── Notification Recipients ───────────────────────── --}}
                    <div class="hrms-card p-4 mb-3">
                        <h6 class="fw-semibold mb-1" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;">
                            <i class="bi bi-bell me-1"></i>Notification Recipients
                        </h6>
                        <p class="text-muted small mb-3">
                            Add extra emails for important HRMS updates. Use comma, semicolon, space, or a new line between addresses.
                        </p>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Important Update Emails</label>
                                <textarea id="important_update_emails" class="form-control" rows="5"
                                          placeholder="owner@company.com&#10;admin2@company.com">{{ $notifications['important_update_emails'] }}</textarea>
                                <div class="form-text">New leave applications are sent to admins plus these emails.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Leave Approval CC Emails</label>
                                <textarea id="leave_approval_cc_emails" class="form-control" rows="5"
                                          placeholder="ops@company.com">{{ $notifications['leave_approval_cc_emails'] }}</textarea>
                                <div class="form-text">Leave approvals go to employee, team leader if assigned, plus these emails.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Payslip CC Emails</label>
                                <textarea id="payslip_cc_emails" class="form-control" rows="5"
                                          placeholder="accounts@company.com">{{ $notifications['payslip_cc_emails'] }}</textarea>
                                <div class="form-text">Payslip emails go to employee, team leader if assigned, plus these emails.</div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-primary" id="btnSaveNotifications">
                                <i class="bi bi-check2 me-1"></i> Save Notification Recipients
                            </button>
                        </div>
                    </div>

                    {{-- ── Test Connection ────────────────────────────────── --}}
                    <div class="hrms-card p-4">
                        <h6 class="fw-semibold mb-1" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;">
                            <i class="bi bi-send-check me-1"></i>Test Connection
                        </h6>
                        <p class="text-muted small mb-3">Send a test email using the <strong>currently saved</strong> settings to verify your configuration is working.</p>

                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input type="email" id="smtpTestEmail" class="form-control"
                                   placeholder="Enter recipient email address" style="max-width:280px;">
                            <button class="btn btn-outline-success" id="btnTestSmtp" {{ !$smtp['configured'] ? 'disabled' : '' }}>
                                <span id="testSmtpSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                                <i class="bi bi-send me-1" id="testSmtpIcon"></i>
                                <span id="testSmtpLabel">Send Test Email</span>
                            </button>
                        </div>

                        {{-- Result panel --}}
                        <div id="smtpTestResult" class="mt-3 d-none">
                            <div id="smtpTestResultInner" class="p-3 rounded border" style="font-size:.875rem;"></div>
                        </div>

                        @if(!$smtp['configured'])
                        <div class="mt-2 text-muted small">
                            <i class="bi bi-info-circle me-1"></i>Configure and save SMTP settings first before testing.
                        </div>
                        @endif
                    </div>

                </div>

                {{-- ══ THEME ════════════════════════════════════════════ --}}
                <div class="tab-pane fade" id="panel-theme" role="tabpanel">
                    <div class="hrms-card p-4">
                        <h6 class="fw-semibold mb-1"><i class="bi bi-palette me-2 text-primary"></i>Theme Settings</h6>
                        <small class="text-muted d-block mb-4">Sidebar color, accent color, and branding text. Reload the page to see changes.</small>

                        <div class="row g-4 align-items-start">
                            <div class="col-md-4">
                                <label class="form-label">Primary / Accent Color</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" id="theme_primary_color" class="form-control form-control-color" value="{{ $theme['primary_color'] }}" style="width:60px;height:38px;">
                                    <input type="text" id="theme_primary_color_txt" class="form-control font-monospace" value="{{ $theme['primary_color'] }}" maxlength="7" style="width:110px;">
                                </div>
                                <div class="form-text">Buttons, links, active sidebar items.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sidebar Background Color</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" id="theme_sidebar_color" class="form-control form-control-color" value="{{ $theme['sidebar_color'] }}" style="width:60px;height:38px;">
                                    <input type="text" id="theme_sidebar_color_txt" class="form-control font-monospace" value="{{ $theme['sidebar_color'] }}" maxlength="7" style="width:110px;">
                                </div>
                                <div class="form-text">Background of the left navigation sidebar.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Logo Brand Text</label>
                                <input type="text" id="theme_logo_text" class="form-control" value="{{ $theme['logo_text'] }}" maxlength="30">
                                <div class="form-text">Bold word before "HRMS" in the sidebar.</div>
                            </div>
                        </div>

                        {{-- Live preview --}}
                        <div class="mt-4 p-3 rounded" id="themePreview" style="background:{{ $theme['sidebar_color'] }};max-width:280px;">
                            <div style="color:#fff;font-weight:700;font-size:1rem;">
                                <span id="previewLogoText" style="color:{{ $theme['primary_color'] }}">{{ $theme['logo_text'] }}</span>
                                <span> HRMS</span>
                            </div>
                            <div style="color:rgba(255,255,255,.35);font-size:.72rem;margin-top:2px;">Sidebar preview</div>
                            <div class="mt-2 d-flex flex-column gap-1">
                                <div style="color:rgba(255,255,255,.65);font-size:.82rem;padding:5px 0;">
                                    <i class="bi bi-grid-1x2 me-2"></i>Dashboard
                                </div>
                                <div style="background:rgba(255,255,255,.12);border-radius:4px;padding:5px 8px;font-size:.82rem;" id="previewActive">
                                    <i class="bi bi-gear me-2"></i>Settings
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-primary" onclick="saveSection('theme', '/settings/theme', ['theme_primary_color','theme_sidebar_color','theme_logo_text'])">
                                <i class="bi bi-check2 me-1"></i> Save Theme
                            </button>
                            <small class="text-muted ms-3">Reload the page after saving to apply theme changes.</small>
                        </div>
                    </div>
                </div>

                {{-- ══ ACCESS ═══════════════════════════════════════════ --}}
                <div class="tab-pane fade" id="panel-access" role="tabpanel">
                    <div class="hrms-card p-4">
                        <h6 class="fw-semibold mb-1"><i class="bi bi-shield-lock me-2 text-primary"></i>Access Control</h6>
                        <small class="text-muted d-block mb-4">Role-based feature toggles.</small>

                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                            <div>
                                <div class="fw-semibold">HR Can Manage Designations</div>
                                <small class="text-muted">Allow HR users to create, edit, and delete designations.</small>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="toggleDesignationHr"
                                       style="width:2.5rem;height:1.3rem;cursor:pointer;"
                                       {{ $designationHrAccess ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div id="designationToggleMsg" class="text-success small mt-2 d-none">Saved.</div>
                    </div>
                </div>

            </div>{{-- end tab-content --}}
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ── Core helpers ───────────────────────────────────────────
        const CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

        // Lazy toast — initialised once on first use so a missing Bootstrap
        // never crashes the whole script before any listeners are attached.
        let _toast = null;
        window.showToast = function showToast(msg, ok = true) {
            const el = document.getElementById('sToast');
            const msgEl = document.getElementById('sToastMsg');
            if (!el || !msgEl) { console.warn('Settings toast:', msg); return; }
            el.className = `toast align-items-center text-white border-0 ${ok ? 'bg-success' : 'bg-danger'}`;
            msgEl.textContent = msg;
            try {
                if (!_toast) _toast = new bootstrap.Toast(el, { delay: 3500 });
                _toast.show();
            } catch (e) {
                // Fallback: simple banner when Bootstrap Toast fails
                el.classList.add('show');
                setTimeout(() => el.classList.remove('show'), 3500);
            }
        }

        window.post = function post(url, data, method = 'POST') {
            return fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            }).then(r => r.json());
        }

        window.saveSection = function saveSection(name, url, fields) {
            const data = {};
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el) data[id] = el.value;
            });
            post(url, data)
                .then(d => showToast(d.message || 'Saved.', !!d.success))
                .catch(() => showToast('Save failed — check your connection.', false));
        }

        // ── Logo upload ────────────────────────────────────────────
        document.getElementById('logoFile')?.addEventListener('change', function () {
            if (!this.files[0]) return;
            const fd = new FormData();
            fd.append('logo', this.files[0]);
            fd.append('_token', CSRF);
            fetch('/settings/logo', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(d => {
                    if (!d.success) { showToast(d.message, false); return; }
                    document.getElementById('logoPreview').src = d.url;
                    document.getElementById('logoPreviewWrap').classList.remove('d-none');
                    document.getElementById('logoPlaceholder').classList.add('d-none');
                    document.getElementById('btnDeleteLogo').classList.remove('d-none');
                    showToast(d.message);
                })
                .catch(() => showToast('Upload failed.', false));
        });

        document.getElementById('btnDeleteLogo')?.addEventListener('click', () => {
            fetch('/settings/logo', { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(d => {
                    document.getElementById('logoPreviewWrap')?.classList.add('d-none');
                    document.getElementById('logoPlaceholder')?.classList.remove('d-none');
                    document.getElementById('btnDeleteLogo')?.classList.add('d-none');
                    showToast(d.message, !!d.success);
                })
                .catch(() => showToast('Delete failed.', false));
        });

        // ── Company save ───────────────────────────────────────────
        document.getElementById('btnSaveCompany')?.addEventListener('click', () =>
            saveSection('company', '/settings/company', [
                'company_name','company_tagline','company_address',
                'company_phone','company_email','company_website',
            ])
        );

        // ── Branch toggle ──────────────────────────────────────────
        document.querySelectorAll('[name="enable_branches_radio"]').forEach(radio => {
            radio.addEventListener('change', () => {
                document.getElementById('branchesEnabledNote')
                    ?.classList.toggle('d-none', radio.value !== '1');
            });
        });

        window.saveBranchToggle = function () {
            const val = document.querySelector('[name="enable_branches_radio"]:checked')?.value ?? '0';
            post('/settings/company', { enable_branches: val })
                .then(d => showToast(d.message || 'Saved.', !!d.success))
                .catch(() => showToast('Save failed.', false));
        };

        // ── Localization save ──────────────────────────────────────
        document.getElementById('btnSaveLocalization')?.addEventListener('click', () =>
            saveSection('localization', '/settings/localization', [
                'timezone','date_format','time_format','week_starts_on',
            ])
        );

        // ── Currency preset cards ──────────────────────────────────
        document.getElementById('presetCards')?.addEventListener('click', e => {
            const card = e.target.closest('.preset-card');
            if (!card) return;
            document.querySelectorAll('.preset-card').forEach(c => {
                c.classList.remove('border-primary', 'bg-primary-subtle');
                c.classList.add('border-secondary-subtle');
            });
            card.classList.add('border-primary', 'bg-primary-subtle');
            card.classList.remove('border-secondary-subtle');
            document.getElementById('currency_code').value      = card.dataset.code;
            document.getElementById('currency_symbol').value    = card.dataset.symbol;
            document.getElementById('currency_position').value  = card.dataset.position;
            document.getElementById('decimal_separator').value  = card.dataset.decimal;
            document.getElementById('thousand_separator').value = card.dataset.thousand;
            updateCurrencyPreview();
        });

        function updateCurrencyPreview() {
            const sym  = document.getElementById('currency_symbol')?.value    || '$';
            const pos  = document.getElementById('currency_position')?.value  || 'before';
            const dec  = document.getElementById('decimal_separator')?.value  || '.';
            const thou = document.getElementById('thousand_separator')?.value ?? ',';
            let int    = '1234567';
            if (thou !== '') int = int.replace(/\B(?=(\d{3})+(?!\d))/g, thou);
            const val  = pos === 'before' ? sym + int + dec + '89' : int + dec + '89 ' + sym;
            const el   = document.getElementById('currPreviewBadge');
            if (el) el.textContent = val;
        }

        ['currency_symbol','currency_position','decimal_separator','thousand_separator'].forEach(id => {
            document.getElementById(id)?.addEventListener('input',  updateCurrencyPreview);
            document.getElementById(id)?.addEventListener('change', updateCurrencyPreview);
        });

        document.getElementById('btnSaveCurrency')?.addEventListener('click', () => {
            const data = {
                currency_code:      document.getElementById('currency_code')?.value.trim(),
                currency_symbol:    document.getElementById('currency_symbol')?.value.trim(),
                currency_position:  document.getElementById('currency_position')?.value,
                decimal_separator:  document.getElementById('decimal_separator')?.value,
                thousand_separator: document.getElementById('thousand_separator')?.value,
            };
            post('/settings/currency', data)
                .then(d => showToast(d.message, !!d.success))
                .catch(() => showToast('Failed.', false));
        });

        // ── Attendance work-day chips ──────────────────────────────
        document.querySelectorAll('.workday-chip').forEach(chip => {
            chip.addEventListener('click', function () {
                const cb = this.querySelector('.workday-cb');
                if (!cb) return;
                cb.checked = !cb.checked;
                this.classList.toggle('border-primary',    cb.checked);
                this.classList.toggle('bg-primary-subtle', cb.checked);
                this.classList.toggle('border-light',      !cb.checked);
                this.classList.toggle('bg-light',          !cb.checked);
            });
        });

        document.getElementById('btnSaveAttendance')?.addEventListener('click', () => {
            const work_days = [...document.querySelectorAll('.workday-cb:checked')].map(cb => cb.value);
            const data = {
                work_hours_per_day:       document.getElementById('work_hours_per_day')?.value,
                late_grace_minutes:       document.getElementById('late_grace_minutes')?.value,
                overtime_threshold_hours: document.getElementById('overtime_threshold_hours')?.value,
                work_days,
            };
            post('/settings/attendance', data)
                .then(d => showToast(d.message, !!d.success))
                .catch(() => showToast('Failed.', false));
        });

        // ── Leave save ─────────────────────────────────────────────
        document.getElementById('btnSaveLeave')?.addEventListener('click', () =>
            saveSection('leave', '/settings/leave', [
                'annual_leave_days','sick_leave_days','casual_leave_days',
                'carry_forward_days','max_leave_per_request',
            ])
        );

        // ── Payroll save ───────────────────────────────────────────
        document.getElementById('btnSavePayroll')?.addEventListener('click', () =>
            saveSection('payroll', '/settings/payroll', [
                'pay_cycle','tax_percentage','overtime_multiplier','payslip_footer_note',
            ])
        );

        // ── Holiday email toggle label ─────────────────────────────
        document.getElementById('holiday_email_enabled')?.addEventListener('change', function () {
            const lbl = document.getElementById('holidayEmailLabel');
            if (lbl) lbl.textContent = this.checked ? 'Enabled' : 'Disabled';
        });

        function holidayPayload() {
            return {
                holiday_email_enabled:      document.getElementById('holiday_email_enabled')?.checked ? 'true' : 'false',
                holiday_notify_before_days: document.getElementById('holiday_notify_before_days')?.value,
                hr_manage_holidays:         document.getElementById('hr_manage_holidays')?.checked ? 'true' : 'false',
                holiday_attendance_record:  document.querySelector('input[name="holiday_attendance_record"]:checked')?.value ?? 'skip',
            };
        }

        document.getElementById('btnSaveHolidayEmail')?.addEventListener('click', () =>
            post('/settings/holiday', holidayPayload())
                .then(d => showToast(d.message, !!d.success))
                .catch(() => showToast('Failed.', false))
        );

        document.getElementById('btnSaveAttendanceBehavior')?.addEventListener('click', () =>
            post('/settings/holiday', holidayPayload())
                .then(d => showToast(d.message, !!d.success))
                .catch(() => showToast('Failed.', false))
        );

        document.getElementById('btnSaveHolidayAccess')?.addEventListener('click', () => {
            const weeklyOffData = {
                hr_manage_weekly_off:            document.getElementById('hr_manage_weekly_off')?.checked ? 'true' : 'false',
                registration_weekly_off_enabled: document.getElementById('registration_weekly_off_enabled')?.checked ? 'true' : 'false',
            };
            Promise.all([
                post('/settings/holiday',    holidayPayload()),
                post('/settings/weekly-off', weeklyOffData),
            ])
            .then(() => showToast('Permission settings saved.', true))
            .catch(() => showToast('Failed to save.', false));
        });

        // Radio label highlight
        document.querySelectorAll('input[name="holiday_attendance_record"]').forEach(radio => {
            radio.addEventListener('change', () => {
                document.querySelectorAll('#lbl-skip, #lbl-record').forEach(l =>
                    l.classList.remove('border-primary', 'bg-primary-subtle')
                );
                document.getElementById('lbl-' + radio.value)
                    ?.classList.add('border-primary', 'bg-primary-subtle');
            });
            if (radio.checked) {
                document.getElementById('lbl-' + radio.value)
                    ?.classList.add('border-primary', 'bg-primary-subtle');
            }
        });

        // ── SMTP presets ───────────────────────────────────────────
        document.getElementById('smtpPresets')?.addEventListener('click', e => {
            const btn = e.target.closest('.preset-btn');
            if (!btn) return;
            document.getElementById('smtp_host').value       = btn.dataset.host;
            document.getElementById('smtp_port').value       = btn.dataset.port;
            document.getElementById('smtp_encryption').value = btn.dataset.enc;
            document.querySelectorAll('.preset-btn').forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-secondary');
            });
            btn.classList.add('btn-primary');
            btn.classList.remove('btn-outline-secondary');
            setTimeout(() => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-secondary');
            }, 1200);
            showToast(`Preset applied: ${btn.dataset.host}`, true);
        });

        document.getElementById('smtp_encryption')?.addEventListener('change', function () {
            const portMap = { tls: 587, ssl: 465, starttls: 587, '': 25 };
            const port = portMap[this.value];
            if (port !== undefined) document.getElementById('smtp_port').value = port;
        });

        document.getElementById('toggleSmtpPass')?.addEventListener('click', () => {
            const inp  = document.getElementById('smtp_password');
            const icon = document.getElementById('toggleSmtpPassIcon');
            if (!inp) return;
            inp.type = inp.type === 'password' ? 'text' : 'password';
            if (icon) icon.className = inp.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
        });

        document.getElementById('btnSaveSmtp')?.addEventListener('click', function () {
            const data = {
                smtp_host:         document.getElementById('smtp_host')?.value.trim(),
                smtp_port:         document.getElementById('smtp_port')?.value,
                smtp_username:     document.getElementById('smtp_username')?.value.trim(),
                smtp_password:     document.getElementById('smtp_password')?.value,
                smtp_encryption:   document.getElementById('smtp_encryption')?.value,
                mail_from_name:    document.getElementById('mail_from_name')?.value.trim(),
                mail_from_address: document.getElementById('mail_from_address')?.value.trim(),
            };
            this.disabled = true;
            post('/settings/smtp', data)
                .then(d => { showToast(d.message, !!d.success); if (d.success) setTimeout(() => location.reload(), 800); })
                .catch(() => showToast('Failed to save SMTP settings.', false))
                .finally(() => { this.disabled = false; });
        });

        document.getElementById('btnSaveNotifications')?.addEventListener('click', function () {
            const data = {
                important_update_emails: document.getElementById('important_update_emails')?.value || '',
                leave_approval_cc_emails: document.getElementById('leave_approval_cc_emails')?.value || '',
                payslip_cc_emails: document.getElementById('payslip_cc_emails')?.value || '',
            };
            this.disabled = true;
            post('/settings/notifications', data)
                .then(d => showToast(d.message, !!d.success))
                .catch(() => showToast('Failed to save notification recipients.', false))
                .finally(() => { this.disabled = false; });
        });

        document.getElementById('mailEnabledToggle')?.addEventListener('change', function () {
            const enabled = this.checked;
            this.disabled = true;
            post('/settings/smtp/toggle', { enabled: enabled ? 1 : 0 })
                .then(d => {
                    document.getElementById('mailEnabledLabel').textContent = d.enabled ? 'Enabled' : 'Disabled';
                    showToast(d.message, !!d.success);
                })
                .catch(() => { this.checked = !enabled; showToast('Failed to toggle mail.', false); })
                .finally(() => { this.disabled = false; });
        });

        document.getElementById('btnTestSmtp')?.addEventListener('click', function () {
            const email = document.getElementById('smtpTestEmail')?.value.trim();
            if (!email) { showToast('Enter a recipient email address.', false); return; }
            const spinner = document.getElementById('testSmtpSpinner');
            const icon    = document.getElementById('testSmtpIcon');
            const label   = document.getElementById('testSmtpLabel');
            const result  = document.getElementById('smtpTestResult');
            const inner   = document.getElementById('smtpTestResultInner');
            this.disabled = true;
            spinner?.classList.remove('d-none');
            icon?.classList.add('d-none');
            if (label) label.textContent = 'Sending…';
            result?.classList.add('d-none');
            post('/settings/smtp/test', { email })
                .then(d => {
                    result?.classList.remove('d-none');
                    if (inner) {
                        inner.className = d.success
                            ? 'p-3 rounded border border-success-subtle bg-success-subtle text-success'
                            : 'p-3 rounded border border-danger-subtle bg-danger-subtle text-danger';
                        const icon2 = d.success ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
                        inner.innerHTML = `<i class="bi ${icon2} me-2"></i><strong>${d.message}</strong>`
                            + (d.detail ? `<br><small class="ms-4">${d.detail}</small>` : '');
                    }
                })
                .catch(() => {
                    result?.classList.remove('d-none');
                    if (inner) {
                        inner.className = 'p-3 rounded border border-danger-subtle bg-danger-subtle text-danger';
                        inner.innerHTML = '<i class="bi bi-x-circle-fill me-2"></i><strong>Request failed. Check network and try again.</strong>';
                    }
                })
                .finally(() => {
                    this.disabled = false;
                    spinner?.classList.add('d-none');
                    icon?.classList.remove('d-none');
                    if (label) label.textContent = 'Send Test Email';
                });
        });

        // ── Theme live preview ─────────────────────────────────────
        function updateThemePreview() {
            const primary  = document.getElementById('theme_primary_color')?.value || '#4e9af1';
            const sidebar  = document.getElementById('theme_sidebar_color')?.value || '#1a1f2e';
            const logoText = document.getElementById('theme_logo_text')?.value     || 'Hemdox';
            const preview  = document.getElementById('themePreview');
            if (preview) preview.style.background = sidebar;
            const previewLogo = document.getElementById('previewLogoText');
            if (previewLogo) { previewLogo.style.color = primary; previewLogo.textContent = logoText; }
            const activeItem = document.getElementById('previewActive');
            if (activeItem) activeItem.style.color = primary;
        }

        function syncColorPair(colorId, textId) {
            const colorEl = document.getElementById(colorId);
            const textEl  = document.getElementById(textId);
            if (!colorEl || !textEl) return;
            colorEl.addEventListener('input', () => { textEl.value = colorEl.value; updateThemePreview(); });
            textEl.addEventListener('input',  () => {
                if (/^#[0-9a-fA-F]{6}$/.test(textEl.value)) colorEl.value = textEl.value;
                updateThemePreview();
            });
        }

        syncColorPair('theme_primary_color', 'theme_primary_color_txt');
        syncColorPair('theme_sidebar_color', 'theme_sidebar_color_txt');
        document.getElementById('theme_logo_text')?.addEventListener('input', updateThemePreview);

        document.getElementById('btnSaveTheme')?.addEventListener('click', () =>
            saveSection('theme', '/settings/theme', [
                'theme_primary_color','theme_sidebar_color','theme_logo_text',
            ])
        );

        // ── Branch toggle ──────────────────────────────────────────
        document.querySelectorAll('[name="enable_branches_radio"]').forEach(radio => {
            radio.addEventListener('change', () => {
                document.getElementById('branchesEnabledNote')
                    ?.classList.toggle('d-none', radio.value !== '1');
            });
        });

        // ── Designation HR toggle ──────────────────────────────────
        document.getElementById('toggleDesignationHr')?.addEventListener('change', function () {
            fetch('/settings/designation-access', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(r => r.json())
            .then(d => {
                const msg = document.getElementById('designationToggleMsg');
                if (msg) {
                    msg.textContent = d.enabled ? 'HR designation access enabled.' : 'HR designation access disabled.';
                    msg.classList.remove('d-none');
                    setTimeout(() => msg.classList.add('d-none'), 2500);
                }
            })
            .catch(() => showToast('Failed to update access.', false));
        });

    }); // end DOMContentLoaded
    </script>
    @endpush
</x-app-layout>
