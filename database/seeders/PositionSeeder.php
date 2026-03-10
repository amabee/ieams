<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            // Management
            ['title' => 'General Manager',          'department' => 'Management'],
            ['title' => 'Operations Manager',        'department' => 'Management'],
            ['title' => 'Branch Manager',            'department' => 'Management'],
            ['title' => 'Assistant Manager',         'department' => 'Management'],
            ['title' => 'Department Head',           'department' => 'Management'],

            // Human Resources
            ['title' => 'HR Manager',                'department' => 'Human Resources'],
            ['title' => 'HR Officer',                'department' => 'Human Resources'],
            ['title' => 'HR Assistant',              'department' => 'Human Resources'],
            ['title' => 'Recruitment Specialist',    'department' => 'Human Resources'],
            ['title' => 'Training Coordinator',      'department' => 'Human Resources'],
            ['title' => 'Payroll Officer',           'department' => 'Human Resources'],

            // Finance & Accounting
            ['title' => 'Finance Manager',           'department' => 'Finance'],
            ['title' => 'Accountant',                'department' => 'Finance'],
            ['title' => 'Accounting Assistant',      'department' => 'Finance'],
            ['title' => 'Bookkeeper',                'department' => 'Finance'],
            ['title' => 'Cashier',                   'department' => 'Finance'],
            ['title' => 'Billing Officer',           'department' => 'Finance'],

            // Information Technology
            ['title' => 'IT Manager',                'department' => 'Information Technology'],
            ['title' => 'Software Developer',        'department' => 'Information Technology'],
            ['title' => 'System Administrator',      'department' => 'Information Technology'],
            ['title' => 'IT Support Specialist',     'department' => 'Information Technology'],
            ['title' => 'Network Engineer',          'department' => 'Information Technology'],

            // Sales & Marketing
            ['title' => 'Sales Manager',             'department' => 'Sales & Marketing'],
            ['title' => 'Sales Representative',      'department' => 'Sales & Marketing'],
            ['title' => 'Marketing Officer',         'department' => 'Sales & Marketing'],
            ['title' => 'Account Executive',         'department' => 'Sales & Marketing'],

            // Operations
            ['title' => 'Operations Supervisor',     'department' => 'Operations'],
            ['title' => 'Team Leader',               'department' => 'Operations'],
            ['title' => 'Senior Staff',              'department' => 'Operations'],
            ['title' => 'Staff',                     'department' => 'Operations'],
            ['title' => 'Clerk',                     'department' => 'Operations'],
            ['title' => 'Receptionist',              'department' => 'Operations'],

            // Logistics
            ['title' => 'Logistics Supervisor',      'department' => 'Logistics'],
            ['title' => 'Warehouse Staff',           'department' => 'Logistics'],
            ['title' => 'Driver',                    'department' => 'Logistics'],
            ['title' => 'Messenger',                 'department' => 'Logistics'],

            // Security & Maintenance
            ['title' => 'Security Guard',            'department' => 'Security'],
            ['title' => 'Maintenance Staff',         'department' => 'Maintenance'],
            ['title' => 'Janitor / Utility',         'department' => 'Maintenance'],
        ];

        foreach ($positions as $pos) {
            Position::firstOrCreate(['title' => $pos['title']], $pos + ['is_active' => true]);
        }
    }
}
