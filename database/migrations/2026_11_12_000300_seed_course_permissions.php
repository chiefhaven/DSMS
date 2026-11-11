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
        $sources = Permission::where('name', 'manage administration')
            ->where('guard_name', 'web')->pluck('id');
        $roles = Role::whereHas('permissions', fn ($query) => $query->whereKey($sources))->get();
        $table = config('permission.table_names.model_has_permissions');
        $directGrants = DB::table($table)->whereIn('permission_id', $sources)->get();

        foreach (['view courses', 'create courses', 'edit courses', 'delete courses', 'edit course lessons'] as $name) {
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
