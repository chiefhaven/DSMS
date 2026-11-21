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
        'manage administration' => ['superAdmin', 'admin'],
        'access management' => ['superAdmin', 'admin', 'financeAdmin'],
        'manage finance' => ['superAdmin', 'financeAdmin'],
        'manage operations' => ['superAdmin', 'admin', 'instructor'],
        'access operations' => ['superAdmin', 'admin', 'instructor', 'financeAdmin'],
        'access portal' => ['superAdmin', 'admin', 'instructor', 'student'],
        'access portal admin' => ['superAdmin', 'admin', 'student'],
        'view schedules' => ['superAdmin', 'admin', 'instructor'],
        'view all schedules' => ['superAdmin', 'admin'],
        'create schedules' => ['superAdmin', 'instructor'],
        'edit schedules' => ['superAdmin', 'instructor'],
        'delete schedules' => ['superAdmin', 'instructor'],
        'restore schedules' => ['superAdmin'],
        'view roles' => ['superAdmin'],
        'create roles' => ['superAdmin'],
        'edit roles' => ['superAdmin'],
        'delete roles' => ['superAdmin'],
        'view students' => ['superAdmin', 'admin', 'financeAdmin', 'instructor'],
        'view all students' => ['superAdmin', 'admin', 'financeAdmin'],
        'create students' => ['superAdmin', 'admin'],
        'edit students' => ['superAdmin'],
        'delete students' => ['superAdmin'],
        'assign students' => ['superAdmin', 'admin'],
        'change student status' => ['superAdmin', 'admin'],
        'generate student reports' => ['superAdmin'],
        'generate student letters' => ['superAdmin'],
        'view invoices' => ['superAdmin', 'admin', 'financeAdmin'],
        'create invoices' => ['superAdmin', 'financeAdmin'],
        'edit invoices' => ['superAdmin'],
        'delete invoices' => ['superAdmin'],
        'download invoices' => ['superAdmin', 'admin', 'financeAdmin'],
        'view fee payments' => ['superAdmin', 'admin', 'financeAdmin'],
        'view all fee payments' => ['superAdmin', 'financeAdmin'],
        'record fee payments' => ['superAdmin', 'financeAdmin'],
        'delete fee payments' => ['superAdmin'],
        'view bulk attendances' => ['superAdmin', 'admin', 'financeAdmin', 'instructor'],
        'create bulk attendances' => ['superAdmin', 'admin', 'instructor'],
        'edit bulk attendances' => ['superAdmin', 'admin', 'instructor'],
        'delete bulk attendances' => ['superAdmin', 'admin', 'instructor'],
        'view attendances' => ['superAdmin', 'admin', 'financeAdmin', 'instructor'],
        'view all attendances' => ['superAdmin', 'admin', 'financeAdmin'],
        'record attendances' => ['superAdmin', 'instructor'],
        'edit attendances' => ['superAdmin'],
        'delete attendances' => ['superAdmin'],
        'download attendance reports' => ['superAdmin', 'admin', 'financeAdmin', 'instructor'],
        'view instructor payments' => ['superAdmin', 'admin'],
        'process instructor payments' => ['superAdmin'],
        'view instructors' => ['superAdmin', 'admin'],
        'create instructors' => ['superAdmin', 'admin'],
        'edit instructors' => ['superAdmin', 'admin'],
        'delete instructors' => ['superAdmin', 'admin'],
        'manage instructor accounts' => ['superAdmin', 'admin'],
        'view administrators' => ['superAdmin', 'admin'],
        'create administrators' => ['superAdmin', 'admin'],
        'edit administrators' => ['superAdmin', 'admin'],
        'delete administrators' => ['superAdmin', 'admin'],
        'manage administrator accounts' => ['superAdmin', 'admin'],
        'assign administrator roles' => ['superAdmin', 'admin'],
        'view courses' => ['superAdmin', 'admin'],
        'create courses' => ['superAdmin', 'admin'],
        'edit courses' => ['superAdmin', 'admin'],
        'delete courses' => ['superAdmin', 'admin'],
        'edit course lessons' => ['superAdmin', 'admin'],
        'view lessons' => ['superAdmin', 'admin', 'instructor'],
        'create lessons' => ['superAdmin', 'admin'],
        'edit lessons' => ['superAdmin', 'admin'],
        'delete lessons' => ['superAdmin', 'admin'],
        'view classrooms' => ['superAdmin', 'admin'],
        'create classrooms' => ['superAdmin', 'admin'],
        'edit classrooms' => ['superAdmin', 'admin'],
        'delete classrooms' => ['superAdmin', 'admin'],
        'view fleet' => ['superAdmin', 'admin'],
        'create fleet' => ['superAdmin', 'admin'],
        'edit fleet' => ['superAdmin', 'admin'],
        'delete fleet' => ['superAdmin', 'admin'],
        'view vehicle locations' => ['superAdmin', 'admin'],
        'record vehicle locations' => ['superAdmin', 'instructor'],
        'view expense types' => ['superAdmin', 'admin', 'financeAdmin'],
        'create expense types' => ['superAdmin', 'admin'],
        'edit expense types' => ['superAdmin', 'admin'],
        'delete expense types' => ['superAdmin'],
        'view expenses' => ['superAdmin', 'admin', 'financeAdmin'],
        'create expenses' => ['superAdmin', 'admin'],
        'edit expenses' => ['superAdmin', 'admin'],
        'delete expenses' => ['superAdmin'],
        'review expenses' => ['superAdmin'],
        'approve expenses' => ['superAdmin'],
        'download expense letters' => ['superAdmin', 'admin', 'financeAdmin'],
        'report expenses' => ['superAdmin', 'admin', 'financeAdmin'],
        'view expense payments' => ['superAdmin', 'admin', 'financeAdmin'],
        'record expense payments' => ['superAdmin', 'financeAdmin'],
        'delete expense payments' => ['superAdmin'],
        'download expense payment receipts' => ['superAdmin', 'financeAdmin'],
        'report expense payments' => ['superAdmin', 'admin', 'financeAdmin'],
        'view settings' => ['superAdmin', 'admin'],
        'edit school settings' => ['superAdmin', 'admin'],
        'edit attendance settings' => ['superAdmin', 'admin'],
        'edit invoice settings' => ['superAdmin', 'admin'],
        'view announcements' => ['superAdmin'],
        'send announcements' => ['superAdmin'],
        'view notification templates' => ['superAdmin', 'admin'],
        'edit notification templates' => ['superAdmin', 'admin'],
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
