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
        $permission = Permission::findOrCreate('view sms balance', 'web');
        $sources = Permission::whereIn('name', ['manage finance', 'manage system'])
            ->where('guard_name', 'web')
            ->get();

        if ($sources->count() === 2) {
            $roleQuery = Role::where('guard_name', 'web');
            foreach ($sources as $source) {
                $roleQuery->whereHas('permissions', fn ($query) => $query->whereKey($source->id));
            }
            foreach ($roleQuery->get() as $role) {
                $role->givePermissionTo($permission);
            }

            $grants = DB::table($table)->whereIn('permission_id', $sources->pluck('id'))->get();
            foreach ($grants->where('permission_id', $sources->first()->id) as $grant) {
                $hasBoth = $sources->every(fn ($source) => $grants->contains(fn ($other) =>
                    (string) $other->permission_id === (string) $source->id
                    && $other->model_type === $grant->model_type
                    && $other->model_id === $grant->model_id
                    && (!$teamKey || $other->{$teamKey} === $grant->{$teamKey})
                ));
                if ($hasBoth) {
                    $row = (array) $grant;
                    $row['permission_id'] = $permission->id;
                    DB::table($table)->insertOrIgnore($row);
                }
            }
        }

        Role::where('name', 'superAdmin')->where('guard_name', 'web')->first()
            ?->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Retain permissions and any later role assignments when rolling back.
    }
};
