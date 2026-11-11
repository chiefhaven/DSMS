<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $source = Permission::where('name', 'manage operations')->where('guard_name', 'web')->first();
        $readerRoles = $source
            ? Role::whereHas('permissions', fn ($query) => $query->whereKey($source->id))->get()
            : collect();
        $table = config('permission.table_names.model_has_permissions');
        $directGrants = $source ? DB::table($table)->where('permission_id', $source->id)->get() : collect();

        foreach (['view lessons', 'create lessons', 'edit lessons', 'delete lessons'] as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            $defaults = $name === 'view lessons'
                ? ['superAdmin', 'Super-Admin', 'admin', 'instructor']
                : ['superAdmin', 'Super-Admin', 'admin'];
            $roles = Role::whereIn('name', $defaults)->where('guard_name', 'web')->get();
            if ($name === 'view lessons') {
                $roles = $roles->merge($readerRoles);
                foreach ($directGrants as $grant) {
                    $row = (array) $grant;
                    $row['permission_id'] = $permission->id;
                    DB::table($table)->insertOrIgnore($row);
                }
            }
            foreach ($roles as $role) {
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
