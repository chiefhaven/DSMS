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
        $source = Permission::where('name', 'manage administration')->where('guard_name', 'web')->first();
        $roles = $source
            ? Role::whereHas('permissions', fn ($query) => $query->whereKey($source->id))->get()
            : collect();
        $table = config('permission.table_names.model_has_permissions');
        $directGrants = $source ? DB::table($table)->where('permission_id', $source->id)->get() : collect();

        foreach (['view settings', 'edit school settings', 'edit attendance settings', 'edit invoice settings'] as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            foreach ($roles->merge(Role::whereIn('name', ['superAdmin', 'Super-Admin', 'admin'])->where('guard_name', 'web')->get()) as $role) {
                $role->givePermissionTo($permission);
            }
            foreach ($directGrants as $grant) {
                $row = (array) $grant;
                $row['permission_id'] = $permission->id;
                DB::table($table)->insertOrIgnore($row);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Retain permissions and any later role assignments when rolling back.
    }
};
