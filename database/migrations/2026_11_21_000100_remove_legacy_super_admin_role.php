<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rolesTable = config('permission.table_names.roles');
        $role = DB::table($rolesTable)
            ->where('name', 'Super-Admin')
            ->where('guard_name', 'web')
            ->first();

        if (!$role) {
            return;
        }

        DB::table(config('permission.table_names.model_has_roles'))
            ->where('role_id', $role->id)
            ->delete();

        DB::table(config('permission.table_names.role_has_permissions'))
            ->where('role_id', $role->id)
            ->delete();

        DB::table($rolesTable)->where('id', $role->id)->delete();
    }

    public function down(): void
    {
        // The removed role is intentionally not restored.
    }
};
