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
        $source = Permission::where('name', 'manage system')->where('guard_name', 'web')->first();
        $roles = $source
            ? Role::whereHas('permissions', fn ($query) => $query->whereKey($source->id))->get()
            : collect();
        $tables = config('permission.table_names');

        foreach (['view roles', 'create roles', 'edit roles', 'delete roles'] as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            foreach ($roles as $role) {
                $role->givePermissionTo($permission);
            }
            foreach (Role::whereIn('name', ['superAdmin', 'Super-Admin'])->where('guard_name', 'web')->get() as $role) {
                $role->givePermissionTo($permission);
            }
            if ($source) {
                $grants = DB::table($tables['model_has_permissions'])
                    ->where('permission_id', $source->id)->get();
                foreach ($grants as $grant) {
                    $row = (array) $grant;
                    $row['permission_id'] = $permission->id;
                    DB::table($tables['model_has_permissions'])->insertOrIgnore($row);
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
