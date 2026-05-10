<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'         => Role::ADMIN,
                'display_name' => 'Administrator',
                'description'  => 'Full system access',
            ],
            [
                'name'         => Role::HR,
                'display_name' => 'HR Manager',
                'description'  => 'Manages employees, leave, and payroll',
            ],
            [
                'name'         => Role::MANAGER,
                'display_name' => 'Manager',
                'description'  => 'Team oversight and leave approvals',
            ],
            [
                'name'         => Role::EMPLOYEE,
                'display_name' => 'Employee',
                'description'  => 'Standard employee access',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
