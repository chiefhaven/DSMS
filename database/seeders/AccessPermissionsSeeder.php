<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessPermissionsSeeder extends Seeder
{
    private const ROLES = [
        'admin',
        'financeAdmin',
        'instructor',
        'student',
    ];

    private const PERMISSIONS = [
        'manage system' => ['superAdmin'],
        'manage administration' => ['superAdmin', 'Super-Admin', 'admin'],
        'access management' => ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin'],
        'manage finance' => ['superAdmin', 'financeAdmin'],
        'manage operations' => ['superAdmin', 'admin', 'instructor'],
        'access operations' => ['superAdmin', 'admin', 'instructor', 'financeAdmin'],
        'access portal' => ['superAdmin', 'admin', 'instructor', 'student'],
        'access portal admin' => ['superAdmin', 'admin', 'student'],
        'view schedules' => ['superAdmin', 'Super-Admin', 'admin', 'instructor'],
        'view all schedules' => ['superAdmin', 'Super-Admin', 'admin'],
        'create schedules' => ['superAdmin', 'Super-Admin', 'instructor'],
        'edit schedules' => ['superAdmin', 'Super-Admin', 'instructor'],
        'delete schedules' => ['superAdmin', 'Super-Admin', 'instructor'],
        'restore schedules' => ['superAdmin', 'Super-Admin'],
        'view roles' => ['superAdmin', 'Super-Admin'],
        'create roles' => ['superAdmin', 'Super-Admin'],
        'edit roles' => ['superAdmin', 'Super-Admin'],
        'delete roles' => ['superAdmin', 'Super-Admin'],
        'view students' => ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin', 'instructor'],
        'view all students' => ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin'],
        'create students' => ['superAdmin', 'Super-Admin', 'admin'],
        'edit students' => ['superAdmin', 'Super-Admin'],
        'delete students' => ['superAdmin', 'Super-Admin'],
        'assign students' => ['superAdmin', 'Super-Admin', 'admin'],
        'change student status' => ['superAdmin', 'Super-Admin', 'admin'],
        'generate student reports' => ['superAdmin', 'Super-Admin'],
        'generate student letters' => ['superAdmin', 'Super-Admin'],
        'view courses' => ['superAdmin', 'Super-Admin', 'admin'],
        'create courses' => ['superAdmin', 'Super-Admin', 'admin'],
        'edit courses' => ['superAdmin', 'Super-Admin', 'admin'],
        'delete courses' => ['superAdmin', 'Super-Admin', 'admin'],
        'edit course lessons' => ['superAdmin', 'Super-Admin', 'admin'],
        'view lessons' => ['superAdmin', 'Super-Admin', 'admin', 'instructor'],
        'create lessons' => ['superAdmin', 'Super-Admin', 'admin'],
        'edit lessons' => ['superAdmin', 'Super-Admin', 'admin'],
        'delete lessons' => ['superAdmin', 'Super-Admin', 'admin'],
        'view classrooms' => ['superAdmin', 'Super-Admin', 'admin'],
        'create classrooms' => ['superAdmin', 'Super-Admin', 'admin'],
        'edit classrooms' => ['superAdmin', 'Super-Admin', 'admin'],
        'delete classrooms' => ['superAdmin', 'Super-Admin', 'admin'],
        'view fleet' => ['superAdmin', 'Super-Admin', 'admin'],
        'create fleet' => ['superAdmin', 'Super-Admin', 'admin'],
        'edit fleet' => ['superAdmin', 'Super-Admin', 'admin'],
        'delete fleet' => ['superAdmin', 'Super-Admin', 'admin'],
        'view expense types' => ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin'],
        'create expense types' => ['superAdmin', 'Super-Admin', 'admin'],
        'edit expense types' => ['superAdmin', 'Super-Admin', 'admin'],
        'delete expense types' => ['superAdmin', 'Super-Admin'],
        'view expense payments' => ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin'],
        'record expense payments' => ['superAdmin', 'Super-Admin', 'financeAdmin'],
        'delete expense payments' => ['superAdmin', 'Super-Admin'],
        'download expense payment receipts' => ['superAdmin', 'Super-Admin', 'financeAdmin'],
        'report expense payments' => ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin'],
        'view settings' => ['superAdmin', 'Super-Admin', 'admin'],
        'edit school settings' => ['superAdmin', 'Super-Admin', 'admin'],
        'edit attendance settings' => ['superAdmin', 'Super-Admin', 'admin'],
        'edit invoice settings' => ['superAdmin', 'Super-Admin', 'admin'],
        'view announcements' => ['superAdmin', 'Super-Admin'],
        'send announcements' => ['superAdmin', 'Super-Admin'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        foreach (self::PERMISSIONS as $permissionName => $roleNames) {
            $permission = Permission::findOrCreate($permissionName, 'web');

            foreach ($roleNames as $roleName) {
                Role::findOrCreate($roleName, 'web')->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
