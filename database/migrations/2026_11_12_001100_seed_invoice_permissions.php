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
            'view invoices' => ['access management', ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin']],
            'create invoices' => ['manage finance', ['superAdmin', 'Super-Admin', 'financeAdmin']],
            'edit invoices' => ['manage system', ['superAdmin', 'Super-Admin']],
            'delete invoices' => ['manage system', ['superAdmin', 'Super-Admin']],
            'download invoices' => ['access management', ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin']],
        ];

        foreach ($actions as $name => [$sourceName, $defaults]) {
            $permission = Permission::findOrCreate($name, 'web');
            $source = Permission::where('name', $sourceName)->where('guard_name', 'web')->first();
            $roles = Role::whereIn('name', $defaults)->where('guard_name', 'web')->get();
            if ($source) {
                $roles = $roles->merge(Role::where('guard_name', 'web')
                    ->whereHas('permissions', fn ($permissions) => $permissions->whereKey($source->id))->get());
                foreach (DB::table($table)->where('permission_id', $source->id)->get() as $grant) {
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
