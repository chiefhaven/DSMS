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
        $table = config('permission.table_names.model_has_permissions');
        $actions = [
            'view attendances' => ['access operations', ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin', 'instructor']],
            'view all attendances' => ['manage system', ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin']],
            'record attendances' => [null, ['superAdmin', 'Super-Admin', 'instructor']],
            'edit attendances' => ['manage system', ['superAdmin', 'Super-Admin']],
            'delete attendances' => ['manage system', ['superAdmin', 'Super-Admin']],
            'download attendance reports' => ['access operations', ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin', 'instructor']],
        ];

        foreach ($actions as $name => [$sourceName, $defaults]) {
            $permission = Permission::findOrCreate($name, 'web');
            $source = $sourceName ? Permission::where('name', $sourceName)->where('guard_name', 'web')->first() : null;
            $roles = $source
                ? Role::where('guard_name', 'web')->whereHas('permissions', fn ($query) => $query->whereKey($source->id))->get()
                : collect();
            foreach ($roles->merge(Role::whereIn('name', $defaults)->where('guard_name', 'web')->get()) as $role) {
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
