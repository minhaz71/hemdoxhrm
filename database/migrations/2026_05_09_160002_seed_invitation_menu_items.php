<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Insert "Invitations" link under Administration header
        DB::table('menus')->insert([
            ['type' => 'link',   'label' => 'Invitations',     'icon' => 'bi-envelope-plus',  'route' => 'invitations.index',    'permissions' => json_encode(['employee.create']), 'roles' => json_encode([]), 'sort_order' => 69, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'link',   'label' => 'Registrations',   'icon' => 'bi-person-check',   'route' => 'registrations.index',  'permissions' => json_encode(['employee.create']), 'roles' => json_encode([]), 'sort_order' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('menus')->whereIn('route', ['invitations.index', 'registrations.index'])->delete();
    }
};
