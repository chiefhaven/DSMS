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
            ? Role::where('guard_name', 'web')->whereHas('permissions', fn ($query) => $query->whereKey($source->id))->get()
            : collect();
        $roles = $roles->merge(Role::whereIn('name', ['superAdmin', 'Super-Admin', 'admin'])->where('guard_name', 'web')->get());
        $table = config('permission.table_names.model_has_permissions');
        foreach (['view instructors', 'create instructors', 'edit instructors', 'delete instructors', 'manage instructor accounts'] as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            foreach ($roles as $role) {
                $role->givePermissionTo($permission);
            }
            if ($source) {
                foreach (DB::table($table)->where('permission_id', $source->id)->get() as $grant) {
                    $row = (array) $grant;
                    $row['permission_id'] = $permission->id;
                    DB::table($table)->insertOrIgnore($row);
                }
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Retain permissions and any later role assignments when rolling back.
    }
};
