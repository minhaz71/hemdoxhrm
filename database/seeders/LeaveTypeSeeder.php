<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name'         => 'Paid Leave',
                'slug'         => LeaveType::PAID,
                'days_allowed' => 14,
                'is_paid'      => true,
                'is_active'    => true,
            ],
            [
                'name'         => 'Sick Leave',
                'slug'         => LeaveType::SICK,
                'days_allowed' => 10,
                'is_paid'      => true,
                'is_active'    => true,
            ],
            [
                'name'         => 'Unpaid Leave',
                'slug'         => LeaveType::UNPAID,
                'days_allowed' => 0,   // unlimited — always deducted from salary
                'is_paid'      => false,
                'is_active'    => true,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
