<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ── Define all system permissions ─────────────────────────
        $permissions = [
            // Employee
            ['name' => 'employee.view',      'label' => 'View Employees',         'module' => 'employee'],
            ['name' => 'employee.create',    'label' => 'Create Employee',         'module' => 'employee'],
            ['name' => 'employee.edit',      'label' => 'Edit Employee',           'module' => 'employee'],
            ['name' => 'employee.delete',    'label' => 'Delete Employee',         'module' => 'employee'],
            ['name' => 'employee.terminate', 'label' => 'Terminate Employee',      'module' => 'employee'],

            // Attendance
            ['name' => 'attendance.view',    'label' => 'View Attendance',         'module' => 'attendance'],
            ['name' => 'attendance.mark',    'label' => 'Mark Attendance',         'module' => 'attendance'],
            ['name' => 'attendance.edit',    'label' => 'Edit Attendance',         'module' => 'attendance'],
            ['name' => 'attendance.delete',  'label' => 'Delete Attendance',       'module' => 'attendance'],

            // Leave
            ['name' => 'leave.view',         'label' => 'View All Leaves',         'module' => 'leave'],
            ['name' => 'leave.apply',        'label' => 'Apply for Leave',         'module' => 'leave'],
            ['name' => 'leave.approve',      'label' => 'Approve / Reject Leave',  'module' => 'leave'],
            ['name' => 'leave.delete',       'label' => 'Delete Leave Request',    'module' => 'leave'],

            // Payroll
            ['name' => 'payroll.view',       'label' => 'View Payroll',            'module' => 'payroll'],
            ['name' => 'payroll.generate',   'label' => 'Generate Payroll',        'module' => 'payroll'],
            ['name' => 'payroll.edit',       'label' => 'Edit / Adjust Payroll',   'module' => 'payroll'],
            ['name' => 'payroll.pay',        'label' => 'Mark Payroll as Paid',    'module' => 'payroll'],

            // Payslip
            ['name' => 'payslip.view',       'label' => 'View Payslips',           'module' => 'payslip'],
            ['name' => 'payslip.generate',   'label' => 'Generate Payslips',       'module' => 'payslip'],
            ['name' => 'payslip.download',   'label' => 'Download Payslips',       'module' => 'payslip'],

            // Reports
            ['name' => 'reports.view',       'label' => 'View Reports',            'module' => 'reports'],

            // Education
            ['name' => 'education.manage',   'label' => 'Manage Education Records','module' => 'education'],

            // Designations
            ['name' => 'designations.manage','label' => 'Manage Designations',     'module' => 'designations'],

            // Degree config
            ['name' => 'degree.manage',      'label' => 'Manage Degree Types & Names', 'module' => 'degree'],

            // Settings
            ['name' => 'settings.view',      'label' => 'View System Settings',    'module' => 'settings'],
            ['name' => 'settings.edit',      'label' => 'Edit System Settings',    'module' => 'settings'],

            // Users
            ['name' => 'users.view',         'label' => 'View Users',              'module' => 'users'],
            ['name' => 'users.manage',       'label' => 'Manage Users',            'module' => 'users'],

            // Roles / RBAC
            ['name' => 'roles.view',         'label' => 'View Roles & Permissions','module' => 'roles'],
            ['name' => 'roles.manage',       'label' => 'Manage Roles & Permissions','module' => 'roles'],
        ];

        foreach ($permissions as &$p) {
            $p['is_system']   = true;
            $p['description'] = null;
            $p['created_at']  = $now;
            $p['updated_at']  = $now;
        }

        DB::table('permissions')->upsert($permissions, ['name'], ['label', 'module']);

        // ── Mark system roles ──────────────────────────────────────
        DB::table('roles')
            ->whereIn('name', ['admin', 'hr', 'manager', 'employee'])
            ->update(['is_system' => true]);

        // ── Helper: get IDs ────────────────────────────────────────
        $perm = DB::table('permissions')->pluck('id', 'name');
        $role = DB::table('roles')->pluck('id', 'name');

        // ── HR permissions ─────────────────────────────────────────
        $hrPerms = [
            'employee.view', 'employee.create', 'employee.edit', 'employee.terminate',
            'attendance.view', 'attendance.mark', 'attendance.edit', 'attendance.delete',
            'leave.view', 'leave.apply', 'leave.approve', 'leave.delete',
            'payroll.view', 'payroll.generate', 'payroll.edit', 'payroll.pay',
            'payslip.view', 'payslip.generate', 'payslip.download',
            'reports.view', 'education.manage', 'designations.manage',
        ];

        // ── Manager permissions ────────────────────────────────────
        $managerPerms = [
            'employee.view',
            'attendance.view', 'attendance.mark',
            'leave.view', 'leave.apply', 'leave.approve',
            'reports.view',
        ];

        // ── Employee permissions ───────────────────────────────────
        $employeePerms = [
            'leave.apply',
            'payslip.view', 'payslip.download',
        ];

        $pivotRows = [];

        foreach ($hrPerms as $permName) {
            if (isset($role['hr'], $perm[$permName])) {
                $pivotRows[] = ['role_id' => $role['hr'], 'permission_id' => $perm[$permName]];
            }
        }
        foreach ($managerPerms as $permName) {
            if (isset($role['manager'], $perm[$permName])) {
                $pivotRows[] = ['role_id' => $role['manager'], 'permission_id' => $perm[$permName]];
            }
        }
        foreach ($employeePerms as $permName) {
            if (isset($role['employee'], $perm[$permName])) {
                $pivotRows[] = ['role_id' => $role['employee'], 'permission_id' => $perm[$permName]];
            }
        }

        if ($pivotRows) {
            DB::table('role_permission')->upsert($pivotRows, ['role_id', 'permission_id'], []);
        }
    }

    public function down(): void
    {
        DB::table('role_permission')->delete();
        DB::table('permissions')->delete();
    }
};
