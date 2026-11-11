<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'view students' => ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin', 'instructor'],
        'view all students' => ['superAdmin', 'Super-Admin', 'admin', 'financeAdmin'],
        'create students' => ['superAdmin', 'Super-Admin', 'admin'],
        'edit students' => ['superAdmin', 'Super-Admin'],
        'delete students' => ['superAdmin', 'Super-Admin'],
        'assign students' => ['superAdmin', 'Super-Admin', 'admin'],
        'change student status' => ['superAdmin', 'Super-Admin', 'admin'],
        'generate student reports' => ['superAdmin', 'Super-Admin'],
        'generate student letters' => ['superAdmin', 'Super-Admin'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $source = Permission::where('name', 'manage system')->where('guard_name', 'web')->first();
        $access = Permission::where('name', 'access operations')->where('guard_name', 'web')->first();
        $systemRoles = $source && $access
            ? Role::whereHas('permissions', fn ($query) => $query->whereKey($source->id))
                ->whereHas('permissions', fn ($query) => $query->whereKey($access->id))->get()
            : collect();
        $table = config('permission.table_names.model_has_permissions');
        $directGrants = collect();
        if ($source && $access) {
            $accessGrants = DB::table($table)->where('permission_id', $access->id)->get();
            $teamKey = config('permission.teams') ? config('permission.column_names.team_foreign_key') : null;
            $directGrants = DB::table($table)->where('permission_id', $source->id)->get()
                ->filter(fn ($grant) => $accessGrants->contains(fn ($other) =>
                    $other->model_type === $grant->model_type
                    && $other->model_id === $grant->model_id
                    && (!$teamKey || $other->{$teamKey} === $grant->{$teamKey})
                ));
        }

        foreach ($this->permissions as $name => $roleNames) {
            $permission = Permission::findOrCreate($name, 'web');
            foreach (Role::whereIn('name', $roleNames)->where('guard_name', 'web')->get()->merge($systemRoles) as $role) {
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
