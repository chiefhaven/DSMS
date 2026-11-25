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

        $source = Permission::where('name', 'edit student TRN')->where('guard_name', 'web')->first();
        $permission = Permission::findOrCreate('enter student TRN', 'web');

        if ($source) {
            foreach (Role::where('guard_name', 'web')->whereHas('permissions', fn ($query) => $query->whereKey($source->id))->get() as $role) {
                $role->givePermissionTo($permission);
            }

            $table = config('permission.table_names.model_has_permissions');
            foreach (DB::table($table)->where('permission_id', $source->id)->get() as $grant) {
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
