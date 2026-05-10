<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Seed invitations.manage permission (idempotent) ─────
        DB::table('permissions')->updateOrInsert(
            ['name' => 'invitations.manage'],
            [
                'module'     => 'employee',
                'label'      => 'Manage Registration Invitations',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $permId = DB::table('permissions')->where('name', 'invitations.manage')->value('id');

        // ── 2. Assign to HR role (admin always bypasses via Gate::before) ──
        $hrRole = DB::table('roles')->where('name', 'hr')->first();
        if ($hrRole) {
            DB::table('role_permission')->updateOrInsert(
                ['role_id' => $hrRole->id, 'permission_id' => $permId],
                []
            );
        }

        // ── 3. Move Invitations + Registrations under the People section ──
        // People section currently: header@20, Employees@21, Designations@22
        // New layout:               Employees@21, Designations@22, Invitations@23, Registrations@24

        // Shift down anything that was between 23 and 68 to make room
        // (nothing sits at 23–24 right now, so this is safe without shifting)

        DB::table('menus')
            ->where('route', 'invitations.index')
            ->update([
                'sort_order'  => 23,
                'permissions' => json_encode(['invitations.manage']),
            ]);

        DB::table('menus')
            ->where('route', 'registrations.index')
            ->update([
                'sort_order'  => 24,
                'permissions' => json_encode(['invitations.manage']),
            ]);

        // Remove any stale divider that was at sort_order 70 (left over from
        // the original seed which put Registrations there).
        DB::table('menus')
            ->where('type', 'divider')
            ->where('sort_order', 70)
            ->delete();
    }

    public function down(): void
    {
        // Remove permission
        $perm = DB::table('permissions')->where('name', 'invitations.manage')->first();
        if ($perm) {
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }

        // Restore menu items to original positions
        DB::table('menus')
            ->where('route', 'invitations.index')
            ->update([
                'sort_order'  => 69,
                'permissions' => json_encode(['employee.create']),
            ]);

        DB::table('menus')
            ->where('route', 'registrations.index')
            ->update([
                'sort_order'  => 70,
                'permissions' => json_encode(['employee.create']),
            ]);
    }
};
