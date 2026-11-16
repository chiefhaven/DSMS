<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BalanceReminderPermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);
        (require base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();
        foreach (['superAdmin', 'Super-Admin', 'admin', 'student'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }
        (require base_path('database/migrations/2026_11_11_120000_seed_access_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_15_000100_seed_balance_reminder_permissions.php'))->up();
        Schema::create('students', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->softDeletes();
        });
    }

    public function test_balance_reminder_endpoint_requires_its_action_permission(): void
    {
        $this->authenticate(Role::findByName('student', 'web'));
        $this->postJson('/send-balance-sms/missing/Balance')->assertForbidden();

        $role = Role::create(['name' => 'reminder-sender', 'guard_name' => 'web']);
        $role->givePermissionTo('send balance reminders');
        $this->authenticate($role);
        $this->postJson('/send-balance-sms/missing/Balance')->assertRedirect();
    }

    public function test_migration_preserves_legacy_role_and_direct_grants_idempotently(): void
    {
        $role = Role::create(['name' => 'legacy-admin-manager', 'guard_name' => 'web']);
        $role->givePermissionTo('manage administration');
        $user = new User();
        $user->id = 'direct-manager';
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => Permission::findByName('manage administration', 'web')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);

        $migration = require base_path('database/migrations/2026_11_15_000100_seed_balance_reminder_permissions.php');
        $migration->up();
        $migration->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('send balance reminders'));
        $this->assertTrue(Role::findByName('admin', 'web')->hasPermissionTo('send balance reminders'));
        $this->assertFalse(Role::findByName('student', 'web')->hasPermissionTo('send balance reminders'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('send balance reminders', 'web')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    private function authenticate(Role $role): void
    {
        $user = new User(['name' => 'Balance reminder test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}
