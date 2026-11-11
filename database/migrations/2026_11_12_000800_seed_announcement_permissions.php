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
        $teamKey = config('permission.teams') ? config('permission.column_names.team_foreign_key') : null;
        $actions = [
            'view announcements' => ['manage system', 'manage administration'],
            'send announcements' => ['manage system'],
        ];

        foreach ($actions as $name => $sources) {
            $permission = Permission::findOrCreate($name, 'web');
            $existing = Permission::whereIn('name', $sources)->where('guard_name', 'web')->get();
            $roles = Role::whereIn('name', ['superAdmin', 'Super-Admin'])->where('guard_name', 'web')->get();
            if ($existing->count() === count($sources)) {
                $query = Role::where('guard_name', 'web');
                foreach ($existing as $source) {
                    $query->whereHas('permissions', fn ($permissions) => $permissions->whereKey($source->id));
                }
                $roles = $roles->merge($query->get());
                $grants = DB::table($table)->whereIn('permission_id', $existing->pluck('id'))->get();
                foreach ($grants->where('permission_id', $existing->first()->id) as $grant) {
                    $allSources = $existing->every(fn ($source) => $grants->contains(fn ($other) =>
                        (string) $other->permission_id === (string) $source->id
                        && $other->model_type === $grant->model_type
                        && $other->model_id === $grant->model_id
                        && (!$teamKey || $other->{$teamKey} === $grant->{$teamKey})
                    ));
                    if ($allSources) {
                        $row = (array) $grant;
                        $row['permission_id'] = $permission->id;
                        DB::table($table)->insertOrIgnore($row);
                    }
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
