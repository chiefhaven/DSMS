<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'view schedules' => ['superAdmin', 'Super-Admin', 'admin', 'instructor'],
        'view all schedules' => ['superAdmin', 'Super-Admin', 'admin'],
        'create schedules' => ['superAdmin', 'Super-Admin', 'instructor'],
        'edit schedules' => ['superAdmin', 'Super-Admin', 'instructor'],
        'delete schedules' => ['superAdmin', 'Super-Admin', 'instructor'],
        'restore schedules' => ['superAdmin', 'Super-Admin'],
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
        // Retain permissions and any later role assignments when rolling back.
    }
};
