<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['superadmin', 'admin', 'hr', 'branch_manager', 'employee'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $permissions = [
            // Users
            'view users', 'create users', 'edit users', 'delete users',
            // Branches
            'view branches', 'create branches', 'edit branches', 'delete branches',
            // Employees
            'view employees', 'create employees', 'edit employees', 'delete employees',
            // Attendance
            'view attendance', 'record attendance', 'edit attendance', 'approve attendance correction',
            // Leaves
            'view leaves', 'create leaves', 'approve leaves',
            // Schedules
            'view schedules', 'create schedules', 'edit schedules',
            // Reports
            'view reports', 'export reports',
            // Analytics
            'view analytics', 'view forecasting', 'run forecast',
            // System
            'view audit logs', 'manage backups', 'manage settings',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Assign all permissions to superadmin
        Role::findByName('superadmin')->syncPermissions(Permission::all());

        Role::findByName('admin')->syncPermissions([
            'view users', 'create users', 'edit users',
            'view branches', 'create branches', 'edit branches',
            'view employees', 'create employees', 'edit employees', 'delete employees',
            'view attendance', 'record attendance', 'edit attendance', 'approve attendance correction',
            'view leaves', 'create leaves', 'approve leaves',
            'view schedules', 'create schedules', 'edit schedules',
            'view reports', 'export reports',
            'view analytics', 'view forecasting', 'run forecast',
            'view audit logs', 'manage backups', 'manage settings',
        ]);

        Role::findByName('hr')->syncPermissions([
            'view employees', 'create employees', 'edit employees',
            'view attendance', 'record attendance', 'edit attendance', 'approve attendance correction',
            'view leaves', 'approve leaves',
            'view schedules', 'create schedules', 'edit schedules',
            'view reports', 'export reports',
            'view analytics', 'view forecasting', 'run forecast',
        ]);

        Role::findByName('branch_manager')->syncPermissions([
            'view employees',
            'view attendance', 'record attendance',
            'view leaves', 'approve leaves',
            'view schedules',
            'view reports', 'export reports',
            'view analytics',
        ]);

        Role::findByName('employee')->syncPermissions([
            'view attendance', 'record attendance',
            'create leaves', 'view leaves',
            'view schedules',
        ]);
    }
}
