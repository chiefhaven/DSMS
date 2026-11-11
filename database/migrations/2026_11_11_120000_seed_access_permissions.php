<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Permissions that replace the former role-based access checks, granted to the roles that passed those checks.
return new class extends Migration
{
    private array $permissions = [
        'manage system' => ['superAdmin'],
        'manage administration' => ['superAdmin', 'Super-Admin', 'admin'],
        'access management' => ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin'],
        'manage finance' => ['superAdmin', 'financeAdmin'],
        'manage operations' => ['superAdmin', 'admin', 'instructor'],
        'access operations' => ['superAdmin', 'admin', 'instructor', 'financeAdmin'],
        'access portal' => ['superAdmin', 'admin', 'instructor', 'student'],
        'access portal admin' => ['superAdmin', 'admin', 'student'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $name => $roles) {
            $permission = Permission::findOrCreate($name, 'web');

            foreach (Role::whereIn('name', $roles)->where('guard_name', 'web')->get() as $role) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys($this->permissions))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
