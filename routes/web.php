<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminPasswordResetController;
use App\Http\Controllers\AdminUserManagementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StaffUserController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PendingRegistrationController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TimeDoctorImportController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DegreeTypeController;
use App\Http\Controllers\DegreeNameController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeEducationController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\HolidayCsvImportController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryHistoryController;
use App\Http\Controllers\SalaryIncrementController;
use App\Http\Controllers\PayrollRegenerationController;
use App\Http\Controllers\IncrementEmailController;
use App\Http\Controllers\WeeklyOffController;
use Illuminate\Support\Facades\Route;

// ── Root ──────────────────────────────────────────────────────────
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// ── Dashboard ─────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/crm', [DashboardController::class, 'crm'])->name('dashboard.crm');
    Route::get('/dashboard/time-doctor', [DashboardController::class, 'timeDoctor'])->name('dashboard.time-doctor');
});

// ── Profile ───────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile',           [ProfileController::class, 'edit'])           ->name('profile.edit');
    Route::patch('/profile',         [ProfileController::class, 'update'])         ->name('profile.update');
    Route::patch('/profile/password',[ProfileController::class, 'updatePassword']) ->name('profile.password');
    Route::delete('/profile',        [ProfileController::class, 'destroy'])        ->name('profile.destroy');
});

// ── Admin only ────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');
});

// ── Activity Logs (admin + hr) ────────────────────────────────────
Route::middleware(['auth', 'role:admin,hr'])->prefix('activity')->name('activity.')->group(function () {
    Route::get('/',              [ActivityLogController::class, 'index']) ->name('index');
    Route::get('/users/{user}',  [ActivityLogController::class, 'user'])  ->name('user');
    Route::get('/stats',         [ActivityLogController::class, 'stats']) ->name('stats');
});

// ── Reports (admin + hr + manager) ───────────────────────────────
Route::middleware(['auth', 'role:admin,hr,manager'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/',           [ReportController::class, 'index'])      ->name('index');
    Route::get('attendance',  [ReportController::class, 'attendance']) ->name('attendance');
    Route::get('salary',      [ReportController::class, 'salary'])     ->name('salary');
    Route::get('leave',       [ReportController::class, 'leave'])      ->name('leave');
});

// ── Admin + HR ────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,hr'])->group(function () {
    // Employees
    Route::resource('employees', EmployeeController::class)->except(['show']);
    Route::patch('employees/{employee}/terminate', [EmployeeController::class, 'terminate'])
        ->name('employees.terminate');

    // Salary History (admin + hr manage; approve is admin-only via policy)
    Route::get('employees/{employee}/salary-history',
        [SalaryHistoryController::class, 'index'])->name('employees.salary-history.index');
    Route::post('employees/{employee}/salary-history',
        [SalaryHistoryController::class, 'store'])->name('employees.salary-history.store');
    Route::patch('employees/{employee}/salary-history/{salaryHistory}/approve',
        [SalaryHistoryController::class, 'approve'])->name('employees.salary-history.approve');
    Route::patch('employees/{employee}/salary-history/{salaryHistory}/reject',
        [SalaryHistoryController::class, 'reject'])->name('employees.salary-history.reject');
    Route::delete('employees/{employee}/salary-history/{salaryHistory}',
        [SalaryHistoryController::class, 'destroy'])->name('employees.salary-history.destroy');
    Route::get('salary-history',
        [SalaryHistoryController::class, 'directory'])->name('salary-history.index');

    // Payroll
    Route::get('payroll',                  [PayrollController::class, 'index'])   ->name('payroll.index');
    Route::get('payroll/create',           [PayrollController::class, 'create'])  ->name('payroll.create');
    Route::post('payroll',                 [PayrollController::class, 'store'])   ->name('payroll.store');
    Route::get('payroll/{payroll}/edit',   [PayrollController::class, 'edit'])    ->name('payroll.edit');
    Route::patch('payroll/{payroll}',      [PayrollController::class, 'update'])  ->name('payroll.update');
    Route::patch('payroll/{payroll}/pay',         [PayrollController::class, 'pay'])        ->name('payroll.pay');
    Route::post('payroll/{payroll}/regenerate',  [PayrollController::class, 'regenerate'])  ->name('payroll.regenerate');
    Route::post('payroll/bulk-pay',              [PayrollController::class, 'bulkPay'])     ->name('payroll.bulk-pay');

    Route::prefix('holidays/import')->name('holidays.import.')->group(function () {
        Route::get('/', [HolidayCsvImportController::class, 'index'])->name('index');
        Route::post('/', [HolidayCsvImportController::class, 'store'])
            ->middleware('throttle:security-sensitive')
            ->name('store');
        Route::get('/sample', [HolidayCsvImportController::class, 'sample'])->name('sample');
    });
    Route::resource('holidays', HolidayController::class)->except('destroy');
    Route::patch('holidays/{holiday}/toggle',       [HolidayController::class, 'toggle'])      ->name('holidays.toggle');
    Route::post('holidays/{holiday}/send-emails',   [HolidayController::class, 'sendEmails'])  ->name('holidays.send-emails');
    Route::post('holidays/{holiday}/retry-emails',  [HolidayController::class, 'retryEmails']) ->name('holidays.retry-emails');
    Route::post('weekly-offs/defaults', [WeeklyOffController::class, 'updateDefaults'])->name('weekly-offs.defaults');
    Route::resource('weekly-offs', WeeklyOffController::class)->except(['show']);

    // Salary Increment Emails
    Route::prefix('increment-emails')->name('increment-emails.')->group(function () {
        Route::get('/',              [IncrementEmailController::class, 'index'])        ->name('index');
        Route::post('/template',     [IncrementEmailController::class, 'templateSave'])->name('template.save');
        Route::post('/preview',      [IncrementEmailController::class, 'preview'])     ->name('preview');
        Route::post('/render',       [IncrementEmailController::class, 'render'])      ->name('render');
        Route::post('/send',         [IncrementEmailController::class, 'send'])        ->name('send');
        Route::get('/logs',          [IncrementEmailController::class, 'logs'])        ->name('logs');
        Route::get('/logs/{log}',    [IncrementEmailController::class, 'logShow'])     ->name('log-show');
    });

    // Payroll Regeneration (admin UI + audit log)
    Route::prefix('payroll-regeneration')->name('payroll-regeneration.')->group(function () {
        Route::get('/',             [PayrollRegenerationController::class, 'index'])   ->name('index');
        Route::post('/',            [PayrollRegenerationController::class, 'store'])   ->name('store');
        Route::post('/bulk',        [PayrollRegenerationController::class, 'bulk'])    ->name('bulk');
        Route::get('/logs',         [PayrollRegenerationController::class, 'logs'])    ->name('logs');
        Route::get('/logs/{log}',   [PayrollRegenerationController::class, 'logShow'])->name('log-show');
    });

    // Salary Increments
    Route::prefix('salary-increments')->name('salary-increments.')->group(function () {
        Route::get('/',                              [SalaryIncrementController::class, 'index'])   ->name('index');
        Route::get('/create',                        [SalaryIncrementController::class, 'create'])  ->name('create');
        Route::post('/',                             [SalaryIncrementController::class, 'store'])   ->name('store');
        Route::get('/approval',                      [SalaryIncrementController::class, 'approval'])->name('approval');
        Route::get('/{salaryIncrement}',             [SalaryIncrementController::class, 'show'])    ->name('show');
        Route::get('/{salaryIncrement}/edit',        [SalaryIncrementController::class, 'edit'])    ->name('edit');
        Route::put('/{salaryIncrement}',             [SalaryIncrementController::class, 'update'])  ->name('update');
        Route::post('/{salaryIncrement}/approve',    [SalaryIncrementController::class, 'approve']) ->name('approve');
        Route::post('/{salaryIncrement}/reject',     [SalaryIncrementController::class, 'reject'])  ->name('reject');
    });
});

// ── Admin + HR + Manager ──────────────────────────────────────────
Route::middleware(['auth', 'role:admin,hr,manager'])->group(function () {
    // Attendance
    Route::get('attendance',                         [AttendanceController::class, 'index'])   ->name('attendance.index');
    Route::post('attendance',                        [AttendanceController::class, 'store'])   ->name('attendance.store');
    Route::patch('attendance/{attendance}/checkout', [AttendanceController::class, 'checkout'])->name('attendance.checkout');
    Route::get('attendance/{attendance}/edit',       [AttendanceController::class, 'edit'])    ->name('attendance.edit');
    Route::patch('attendance/{attendance}',          [AttendanceController::class, 'update'])  ->name('attendance.update');
    Route::delete('attendance/{attendance}',         [AttendanceController::class, 'destroy']) ->name('attendance.destroy');
    // Leave approvals
    Route::patch('leaves/{leave}/action', [LeaveController::class, 'action'])->name('leaves.action');
});

// ── Time Doctor imports (admin + hr) ─────────────────────────────
Route::middleware(['auth', 'role:admin,hr', 'throttle:security-sensitive'])
    ->prefix('time-doctor')
    ->name('time-doctor.')
    ->group(function () {
        Route::get('imports',                 [TimeDoctorImportController::class, 'index'])->name('imports.index');
        Route::post('imports',                [TimeDoctorImportController::class, 'store'])->name('imports.store');
        Route::get('imports/{import}',        [TimeDoctorImportController::class, 'show'])->name('imports.show');
    });

// ── All authenticated ─────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Employee self-service
    Route::get('employees/me',                 [EmployeeController::class, 'me'])       ->name('employees.me');
    Route::get('employees/{employee}',         [EmployeeController::class, 'show'])     ->name('employees.show');
    Route::get('attendance/my',                [AttendanceController::class, 'my'])     ->name('attendance.my');
    Route::get('attendance/{employee}/history',[AttendanceController::class, 'history'])->name('attendance.history');
    Route::get('payroll/my',                   [PayrollController::class, 'my'])        ->name('payroll.my');
    Route::get('payroll/{payroll}',            [PayrollController::class, 'show'])      ->name('payroll.show');

    // Leaves
    Route::get('leaves',            [LeaveController::class, 'index'])  ->name('leaves.index');
    Route::get('leaves/create',     [LeaveController::class, 'create']) ->name('leaves.create');
    Route::post('leaves',           [LeaveController::class, 'store'])  ->name('leaves.store');
    Route::get('leaves/{leave}',    [LeaveController::class, 'show'])   ->name('leaves.show');
    Route::delete('leaves/{leave}', [LeaveController::class, 'destroy'])->name('leaves.destroy');
    Route::get('leaves/balance',    [LeaveController::class, 'balance'])->name('leaves.balance');

    // Payslips — employee self-service
    Route::get('payslips/my',                   [PayslipController::class, 'my'])       ->name('payslips.my');
    Route::get('payslips/{payslip}/view',        [PayslipController::class, 'view'])     ->name('payslips.view');
    Route::get('payslips/{payslip}/download',    [PayslipController::class, 'download']) ->name('payslips.download');
});

// ── Payslip management (admin + hr) ──────────────────────────────
Route::middleware(['auth', 'role:admin,hr'])->group(function () {
    Route::get('payslips',                       [PayslipController::class, 'index'])         ->name('payslips.index');
    Route::post('payslips/generate',             [PayslipController::class, 'generate'])      ->name('payslips.generate');
    Route::post('payslips/bulk-generate',        [PayslipController::class, 'bulkGenerate'])  ->name('payslips.bulk-generate');
});

// ── System Settings (admin only) ─────────────────────────────────
Route::middleware(['auth', 'permission:settings.edit'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/',                         [SettingsController::class, 'index'])                  ->name('index');

    // Company
    Route::post('/company',                 [SettingsController::class, 'updateCompany'])          ->name('company.update');
    Route::post('/logo',                    [SettingsController::class, 'updateLogo'])             ->name('logo.update');
    Route::delete('/logo',                  [SettingsController::class, 'deleteLogo'])             ->name('logo.delete');

    // Localization
    Route::post('/localization',            [SettingsController::class, 'updateLocalization'])     ->name('localization.update');

    // Currency
    Route::post('/currency',                [SettingsController::class, 'updateCurrency'])         ->name('currency.update');

    // Attendance
    Route::post('/attendance',              [SettingsController::class, 'updateAttendance'])       ->name('attendance.update');

    // Leave
    Route::post('/leave',                   [SettingsController::class, 'updateLeave'])            ->name('leave.update');

    // Payroll
    Route::post('/payroll',                 [SettingsController::class, 'updatePayroll'])          ->name('payroll.update');

    // SMTP
    Route::post('/smtp',                    [SettingsController::class, 'updateSmtp'])             ->name('smtp.update');
    Route::post('/smtp/toggle',             [SettingsController::class, 'toggleSmtp'])             ->name('smtp.toggle');
    Route::post('/smtp/test',               [SettingsController::class, 'testSmtp'])               ->name('smtp.test');
    Route::get('/smtp/gmail-guide',         [SettingsController::class, 'gmailGuide'])             ->name('smtp.gmail-guide');
    Route::post('/notifications',           [SettingsController::class, 'updateNotifications'])    ->name('notifications.update');

    // Theme
    Route::post('/theme',                   [SettingsController::class, 'updateTheme'])            ->name('theme.update');

    // Holiday & Weekly Off
    Route::post('/holiday',                 [SettingsController::class, 'updateHoliday'])           ->name('holiday.update');
    Route::post('/weekly-off',              [SettingsController::class, 'updateWeeklyOff'])         ->name('weekly-off.update');

    // Access
    Route::post('/designation-access',      [SettingsController::class, 'updateDesignationAccess'])->name('designation-access');
});

// ── Degree Types (admin only) ─────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('degree-types')->name('degree-types.')->group(function () {
    Route::get('/',                           [DegreeTypeController::class, 'index'])  ->name('index');
    Route::post('/',                          [DegreeTypeController::class, 'store'])  ->name('store');
    Route::get('/{degreeType}',               [DegreeTypeController::class, 'show'])   ->name('show');
    Route::patch('/{degreeType}',             [DegreeTypeController::class, 'update']) ->name('update');
    Route::delete('/{degreeType}',            [DegreeTypeController::class, 'destroy'])->name('destroy');
    Route::patch('/{degreeType}/toggle',      [DegreeTypeController::class, 'toggle']) ->name('toggle');
});

// ── Degree Names (admin only) ─────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('degree-names')->name('degree-names.')->group(function () {
    Route::get('/',                           [DegreeNameController::class, 'index'])  ->name('index');
    Route::post('/',                          [DegreeNameController::class, 'store'])  ->name('store');
    Route::get('/{degreeName}',               [DegreeNameController::class, 'show'])   ->name('show');
    Route::patch('/{degreeName}',             [DegreeNameController::class, 'update']) ->name('update');
    Route::delete('/{degreeName}',            [DegreeNameController::class, 'destroy'])->name('destroy');
    Route::patch('/{degreeName}/toggle',      [DegreeNameController::class, 'toggle']) ->name('toggle');
});

// byType AJAX — authenticated users need this for employee education forms
Route::middleware(['auth'])
    ->get('/degree-names/by-type/{degreeType}', [DegreeNameController::class, 'byType'])
    ->name('degree-names.by-type');

// ── Employee Education (admin/hr can manage anyone; employees can manage self) ──
Route::middleware(['auth'])->group(function () {
    Route::get('/employees/{employee}/education',                             [EmployeeEducationController::class, 'index'])  ->name('employees.education.index');
    Route::post('/employees/{employee}/education',                            [EmployeeEducationController::class, 'store'])  ->name('employees.education.store');
    Route::get('/employees/{employee}/education/{education}',                 [EmployeeEducationController::class, 'show'])   ->name('employees.education.show');
    Route::patch('/employees/{employee}/education/{education}',               [EmployeeEducationController::class, 'update']) ->name('employees.education.update');
    Route::delete('/employees/{employee}/education/{education}',              [EmployeeEducationController::class, 'destroy'])->name('employees.education.destroy');
});

// ── Designations ──────────────────────────────────────────────────
Route::middleware(['auth', 'can_manage_designations'])->prefix('designations')->name('designations.')->group(function () {
    Route::get('/',                       [DesignationController::class, 'index'])          ->name('index');
    Route::post('/',                      [DesignationController::class, 'store'])          ->name('store');
    Route::post('/settings/update',       [DesignationController::class, 'updateSettings']) ->name('settings.update');
    Route::get('/{designation}',          [DesignationController::class, 'show'])           ->name('show');
    Route::patch('/{designation}',        [DesignationController::class, 'update'])         ->name('update');
    Route::delete('/{designation}',       [DesignationController::class, 'destroy'])        ->name('destroy');
    Route::patch('/{designation}/toggle', [DesignationController::class, 'toggle'])         ->name('toggle');
});

// ── Staff user management (admin only) ───────────────────────────
Route::middleware(['auth', 'permission:roles.manage', 'throttle:security-sensitive'])->prefix('rbac/staff-users')->name('rbac.staff-users.')->group(function () {
    Route::get('/',              [StaffUserController::class, 'index'])  ->name('index');
    Route::get('/create',        [StaffUserController::class, 'create']) ->name('create');
    Route::post('/',             [StaffUserController::class, 'store'])  ->name('store');
    Route::get('/{staffUser}',   [StaffUserController::class, 'show'])   ->name('show');
    Route::get('/{staffUser}/edit',   [StaffUserController::class, 'edit'])   ->name('edit');
    Route::patch('/{staffUser}', [StaffUserController::class, 'update']) ->name('update');
});

// ── RBAC (admin only) ─────────────────────────────────────────────
Route::middleware(['auth', 'permission:roles.manage', 'throttle:security-sensitive'])->prefix('rbac')->name('rbac.')->group(function () {
    // Roles
    Route::get('roles',                              [RoleController::class, 'index'])             ->name('roles.index');
    Route::post('roles',                             [RoleController::class, 'store'])             ->name('roles.store');
    Route::get('roles/{role}',                       [RoleController::class, 'show'])              ->name('roles.show');
    Route::patch('roles/{role}',                     [RoleController::class, 'update'])            ->name('roles.update');
    Route::delete('roles/{role}',                    [RoleController::class, 'destroy'])           ->name('roles.destroy');
    Route::post('roles/{role}/permissions',          [RoleController::class, 'syncPermissions'])   ->name('roles.permissions.sync');

    // Custom permissions
    Route::post('permissions',                       [RoleController::class, 'storePermission'])   ->name('permissions.store');
    Route::delete('permissions/{permission}',        [RoleController::class, 'destroyPermission']) ->name('permissions.destroy');

    // User access management
    Route::get('users',                              [RoleController::class, 'userIndex'])          ->name('users.index');
    Route::get('users/{user}/permissions',           [RoleController::class, 'userPermissions'])    ->name('users.permissions');
    Route::get('users/{user}/permissions/effective', [RoleController::class, 'effectivePermissions'])->name('users.permissions.effective');
    // Role/permission mutation routes — also protected by escalation guard
    Route::post('users/{user}/permissions/grant',    [RoleController::class, 'grantUserPermission'])->name('users.permissions.grant')  ->middleware('prevent.escalation');
    Route::post('users/{user}/permissions/deny',     [RoleController::class, 'denyUserPermission']) ->name('users.permissions.deny')    ->middleware('prevent.escalation');
    Route::delete('users/{user}/permissions/remove', [RoleController::class, 'removeUserOverride']) ->name('users.permissions.remove')  ->middleware('prevent.escalation');
    Route::post('users/{user}/roles',                [RoleController::class, 'syncUserRoles'])      ->name('users.roles.sync')          ->middleware('prevent.escalation');
});

// ── Menu Manager (admin) ─────────────────────────────────────────
Route::middleware(['auth', 'permission:settings.edit'])->prefix('menus')->name('menus.')->group(function () {
    Route::get('/',                     [MenuController::class, 'index'])            ->name('index');
    Route::post('/',                    [MenuController::class, 'store'])            ->name('store');
    Route::patch('/{menu}',             [MenuController::class, 'update'])           ->name('update');
    Route::delete('/{menu}',            [MenuController::class, 'destroy'])          ->name('destroy');
    Route::patch('/{menu}/toggle',      [MenuController::class, 'toggle'])           ->name('toggle');
    Route::post('/reorder',             [MenuController::class, 'reorder'])          ->name('reorder');
    Route::get('/user-overrides',       [MenuController::class, 'userOverrides'])    ->name('user-overrides');
    Route::post('/user-overrides',      [MenuController::class, 'saveUserOverrides'])->name('user-overrides.save');
});

// ── Company Profile (admin) ──────────────────────────────────────
Route::middleware(['auth', 'permission:settings.edit'])->prefix('company')->name('company.')->group(function () {
    Route::get('/',                              [CompanyController::class, 'index'])            ->name('index');
    Route::post('/',                             [CompanyController::class, 'upsertCompany'])    ->name('upsert');

    // Branches
    Route::post('/branches',                     [CompanyController::class, 'storeBranch'])      ->name('branches.store');
    Route::patch('/branches/{branch}',           [CompanyController::class, 'updateBranch'])     ->name('branches.update');
    Route::delete('/branches/{branch}',          [CompanyController::class, 'destroyBranch'])    ->name('branches.destroy');
    Route::patch('/branches/{branch}/toggle',    [CompanyController::class, 'toggleBranch'])     ->name('branches.toggle');

    // Departments
    Route::post('/departments',                  [CompanyController::class, 'storeDepartment'])  ->name('departments.store');
    Route::patch('/departments/{department}',    [CompanyController::class, 'updateDepartment']) ->name('departments.update');
    Route::delete('/departments/{department}',   [CompanyController::class, 'destroyDepartment'])->name('departments.destroy');

    // Shifts
    Route::post('/shifts',                       [CompanyController::class, 'storeShift'])       ->name('shifts.store');
    Route::patch('/shifts/{shift}',              [CompanyController::class, 'updateShift'])      ->name('shifts.update');
    Route::delete('/shifts/{shift}',             [CompanyController::class, 'destroyShift'])     ->name('shifts.destroy');
    Route::patch('/shifts/{shift}/toggle',       [CompanyController::class, 'toggleShift'])      ->name('shifts.toggle');
});

// AJAX: departments by branch (used in employee create/edit forms)
Route::middleware(['auth', 'role:admin,hr'])
    ->get('/departments/by-branch', [CompanyController::class, 'departmentsByBranch'])
    ->name('departments.by-branch');

// ── Registration invitations (permission-based: admin + hr + any role granted access) ──
Route::middleware(['auth', 'permission:invitations.manage', 'throttle:security-sensitive'])->group(function () {
    Route::get('invitations',                [InvitationController::class, 'index'])  ->name('invitations.index');
    Route::post('invitations',               [InvitationController::class, 'store'])  ->name('invitations.store');
    Route::delete('invitations/{invitation}',[InvitationController::class, 'destroy'])->name('invitations.destroy');

    Route::get('registrations',                                    [PendingRegistrationController::class, 'index'])          ->name('registrations.index');
    Route::get('registrations/{registration}',                     [PendingRegistrationController::class, 'show'])           ->name('registrations.show');
    Route::patch('registrations/{registration}',                   [PendingRegistrationController::class, 'update'])         ->name('registrations.update');
    Route::post('registrations/{registration}/approve',            [PendingRegistrationController::class, 'approve'])        ->name('registrations.approve');
    Route::post('registrations/{registration}/save-and-approve',   [PendingRegistrationController::class, 'saveAndApprove']) ->name('registrations.save-and-approve');
    Route::post('registrations/{registration}/reject',             [PendingRegistrationController::class, 'reject'])         ->name('registrations.reject');
    Route::post('registrations/{registration}/correction',         [PendingRegistrationController::class, 'correction'])     ->name('registrations.correction');
});

// ── Public registration (no auth required) ────────────────────
Route::get('/invite/{token}',    [RegistrationController::class, 'show'])
    ->middleware(['signed', 'throttle:invite-view'])
    ->name('register.form');
Route::post('/invite/{token}',   [RegistrationController::class, 'store'])
    ->middleware(['signed', 'throttle:invite-submit'])
    ->name('register.submit');
Route::get('/register/success',  [RegistrationController::class, 'success'])     ->name('register.success');
Route::get('/check-login-id',    [RegistrationController::class, 'checkLoginId'])->name('register.check-login-id');

// ── Force password change (authenticated, but allowed even with force_password_reset=true) ──
Route::middleware('auth')->group(function () {
    Route::get('/force-password-change',  [AdminPasswordResetController::class, 'showForceChange'])   ->name('auth.force-password-change');
    Route::post('/force-password-change', [AdminPasswordResetController::class, 'handleForceChange'])  ->name('auth.force-password-change.submit');
});

// ── Admin password reset management (admin + HR with roles.manage or admin role) ──
Route::middleware(['auth', 'permission:roles.manage', 'throttle:security-sensitive'])->prefix('rbac/users/{user}')->name('rbac.users.')->group(function () {
    Route::post('send-reset-link',   [AdminPasswordResetController::class, 'sendLink'])       ->name('send-reset-link');
    Route::post('reset-password',    [AdminPasswordResetController::class, 'resetDirect'])    ->name('reset-password');
    Route::post('force-reset',       [AdminPasswordResetController::class, 'forceReset'])     ->name('force-reset');
    Route::post('clear-force-reset', [AdminPasswordResetController::class, 'clearForceReset'])->name('clear-force-reset');
    Route::get('reset-logs',         [AdminPasswordResetController::class, 'logs'])           ->name('reset-logs');
    Route::post('suspend',           [AdminUserManagementController::class, 'suspend'])        ->name('suspend');
    Route::post('activate',          [AdminUserManagementController::class, 'activate'])       ->name('activate');
    Route::post('force-logout',      [AdminUserManagementController::class, 'forceLogout'])   ->name('force-logout');
    Route::post('impersonate',       [AdminUserManagementController::class, 'impersonate'])    ->name('impersonate');
    Route::get('login-history',      [AdminUserManagementController::class, 'loginHistory'])   ->name('login-history');
    Route::get('audit-history',      [AdminUserManagementController::class, 'auditHistory'])   ->name('audit-history');
});

Route::middleware(['auth', 'throttle:security-sensitive'])
    ->post('/impersonation/stop', [AdminUserManagementController::class, 'stopImpersonating'])
    ->name('impersonation.stop');

require __DIR__.'/auth.php';
