<?php

use App\Models\User;
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

        $view = Permission::findOrCreate('view vehicle locations', 'web');
        $record = Permission::findOrCreate('record vehicle locations', 'web');
        $source = Permission::where('name', 'manage administration')->where('guard_name', 'web')->first();
        $roles = $source
            ? Role::where('guard_name', 'web')->whereHas('permissions', fn ($query) => $query->whereKey($source->id))->get()
            : collect();
        foreach ($roles->merge(Role::whereIn('name', ['superAdmin', 'Super-Admin', 'admin'])->where('guard_name', 'web')->get()) as $role) {
            $role->givePermissionTo($view);
        }
        if ($source) {
            $table = config('permission.table_names.model_has_permissions');
            foreach (DB::table($table)->where('permission_id', $source->id)->get() as $grant) {
                $row = (array) $grant;
                $row['permission_id'] = $view->id;
                DB::table($table)->insertOrIgnore($row);
            }
        }

        foreach (Role::whereIn('name', ['superAdmin', 'Super-Admin', 'instructor'])->where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo($record);
        }
        User::whereNotNull('instructor_id')
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'instructor')->where('guard_name', 'web'))
            ->eachById(fn (User $user) => $user->givePermissionTo($record));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Retain permissions and any later role assignments when rolling back.
    }
};
