<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@hemdox.com'],
            [
                'name'            => 'Super Admin',
                'password'        => Hash::make(env('ADMIN_PASSWORD', 'HemdoxAdmin#2026')),
                'approval_status' => 'approved',
                'status'          => 'active',
            ]
        );

        $admin->assignRole(Role::ADMIN);
    }
}
