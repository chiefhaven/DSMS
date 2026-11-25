<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'view student tickets' => ['student'],
        'view all student tickets' => ['superAdmin', 'Super-Admin', 'admin'],
        'create student tickets' => ['student'],
        'assign student tickets' => ['superAdmin', 'Super-Admin', 'admin'],
        'reply to student tickets' => ['superAdmin', 'Super-Admin', 'admin', 'student'],
        'close student tickets' => ['superAdmin', 'Super-Admin', 'admin'],
        'reopen student tickets' => ['superAdmin', 'Super-Admin', 'admin'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $name => $roleNames) {
            $permission = Permission::findOrCreate($name, 'web');

            foreach (Role::whereIn('name', $roleNames)->where('guard_name', 'web')->get() as $role) {
                $role->givePermissionTo($permission);
            }
        }

        $adminActions = Permission::whereIn('name', [
            'view all student tickets',
            'assign student tickets',
            'reply to student tickets',
            'close student tickets',
            'reopen student tickets',
        ])->where('guard_name', 'web')->get();

        foreach (Role::where('guard_name', 'web')->get() as $role) {
            if ($role->hasPermissionTo('manage student tickets')) {
                $role->givePermissionTo($adminActions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys($this->permissions))
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
