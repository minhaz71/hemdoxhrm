<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    private const DEFAULTS = [
        ['name' => 'Manager',         'description' => 'Team or department manager responsible for overseeing operations.'],
        ['name' => 'HR Executive',    'description' => 'Handles recruitment, onboarding, and employee relations.'],
        ['name' => 'Accountant',      'description' => 'Manages financial records, payroll processing, and reporting.'],
        ['name' => 'Sales Executive', 'description' => 'Drives revenue through client acquisition and account management.'],
        ['name' => 'Developer',       'description' => 'Designs, builds, and maintains software systems.'],
        ['name' => 'Designer',        'description' => 'Creates visual assets, UI/UX designs, and branding materials.'],
        ['name' => 'Support Staff',   'description' => 'Provides operational and administrative support across departments.'],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $row) {
            Designation::updateOrCreate(
                ['name' => $row['name']],
                [
                    'description' => $row['description'],
                    'status'      => 'active',
                    'created_by'  => null, // system-seeded
                ]
            );
        }
    }
}
