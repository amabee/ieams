<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Position;
use App\Models\Shift;
use App\Models\Employee;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // System settings
        $settings = [
            ['key' => 'org_name',              'value' => 'PetPal Multi-Branch Organization', 'group' => 'general'],
            ['key' => 'late_threshold_minutes', 'value' => '15',  'group' => 'attendance'],
            ['key' => 'work_hours_per_day',     'value' => '8',   'group' => 'attendance'],
            ['key' => 'forecast_horizon_days',  'value' => '30',  'group' => 'forecasting'],
            ['key' => 'forecast_alpha',         'value' => '0.3', 'group' => 'forecasting'],
            ['key' => 'forecast_beta',          'value' => '0.1', 'group' => 'forecasting'],
            ['key' => 'forecast_gamma',         'value' => '0.2', 'group' => 'forecasting'],
            ['key' => 'alert_absence_threshold','value' => '3',   'group' => 'alerts'],
        ];
        foreach ($settings as $s) {
            SystemSetting::firstOrCreate(['key' => $s['key']], $s);
        }

        // Branches
        $branch1 = Branch::firstOrCreate(['name' => 'Main Branch'], [
            'address'    => '123 Rizal St., Cagayan de Oro City',
            'contact_no' => '09171234567',
            'email'      => 'main@petpal.com',
            'is_active'  => true,
        ]);
        $branch2 = Branch::firstOrCreate(['name' => 'North Branch'], [
            'address'    => '45 Limketkai Dr., CDO City',
            'contact_no' => '09181234568',
            'email'      => 'north@petpal.com',
            'is_active'  => true,
        ]);
        $branch3 = Branch::firstOrCreate(['name' => 'South Branch'], [
            'address'    => '78 Corrales Ave., CDO City',
            'contact_no' => '09191234569',
            'email'      => 'south@petpal.com',
            'is_active'  => true,
        ]);

        // Shifts
        $morningShift = Shift::firstOrCreate(['name' => 'Morning Shift'], [
            'start_time'             => '08:00:00',
            'end_time'               => '17:00:00',
            'late_threshold_minutes' => 15,
        ]);
        $afternoonShift = Shift::firstOrCreate(['name' => 'Afternoon Shift'], [
            'start_time'             => '13:00:00',
            'end_time'               => '22:00:00',
            'late_threshold_minutes' => 15,
        ]);

        // Superadmin user
        $superadmin = User::firstOrCreate(['email' => 'superadmin@ieams.test'], [
            'name'      => 'Super Admin',
            'password'  => Hash::make('password'),
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);
        $superadmin->assignRole('superadmin');

        // HR user
        $hr = User::firstOrCreate(['email' => 'hr@ieams.test'], [
            'name'      => 'HR Staff',
            'password'  => Hash::make('password'),
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);
        $hr->assignRole('hr');

        // Sample employees
        $groomerPos = \App\Models\Position::where('title', 'like', '%Groo%')->first()?->id;
        $vetPos     = \App\Models\Position::where('title', 'like', '%Vet%')->first()?->id;

        $emp1 = Employee::firstOrCreate(['employee_no' => 'EMP-001'], [
            'first_name'      => 'Maria',
            'last_name'       => 'Santos',
            'position_id'     => $groomerPos,
            'employment_type' => 'full_time',
            'branch_id'       => $branch1->id,
            'shift_id'        => $morningShift->id,
            'hire_date'       => '2024-01-15',
            'status'          => 'active',
        ]);
        $empUser1 = User::firstOrCreate(['email' => 'maria.santos@ieams.test'], [
            'name'        => 'Maria Santos',
            'password'    => Hash::make('password'),
            'employee_id' => $emp1->id,
            'branch_id'   => $branch1->id,
            'is_active'   => true,
        ]);
        $empUser1->assignRole('employee');

        $emp2 = Employee::firstOrCreate(['employee_no' => 'EMP-002'], [
            'first_name'      => 'Juan',
            'last_name'       => 'Dela Cruz',
            'position_id'     => $vetPos,
            'employment_type' => 'full_time',
            'branch_id'       => $branch2->id,
            'shift_id'        => $afternoonShift->id,
            'hire_date'       => '2024-03-01',
            'status'          => 'active',
        ]);
        $empUser2 = User::firstOrCreate(['email' => 'juan.delacruz@ieams.test'], [
            'name'        => 'Juan Dela Cruz',
            'password'    => Hash::make('password'),
            'employee_id' => $emp2->id,
            'branch_id'   => $branch2->id,
            'is_active'   => true,
        ]);
        $empUser2->assignRole('employee');

        // Branch manager accounts — one per branch
        $bm1 = User::firstOrCreate(['email' => 'bm.main@ieams.test'], [
            'name'      => 'Carlo Reyes',
            'password'  => Hash::make('password'),
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);
        $bm1->assignRole('branch_manager');
        $branch1->update(['manager_id' => $bm1->id]);

        $bm2 = User::firstOrCreate(['email' => 'bm.north@ieams.test'], [
            'name'      => 'Ana Santos',
            'password'  => Hash::make('password'),
            'branch_id' => $branch2->id,
            'is_active' => true,
        ]);
        $bm2->assignRole('branch_manager');
        $branch2->update(['manager_id' => $bm2->id]);

        $bm3 = User::firstOrCreate(['email' => 'bm.south@ieams.test'], [
            'name'      => 'Miguel Bautista',
            'password'  => Hash::make('password'),
            'branch_id' => $branch3->id,
            'is_active' => true,
        ]);
        $bm3->assignRole('branch_manager');
        $branch3->update(['manager_id' => $bm3->id]);

        // Admin user
        $admin = User::firstOrCreate(['email' => 'admin@ieams.test'], [
            'name'      => 'Rosa Villanueva',
            'password'  => Hash::make('password'),
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');
    }
}
