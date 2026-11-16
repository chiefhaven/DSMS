<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SmsBalancePermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);
        (require base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();
        foreach (['superAdmin', 'Super-Admin', 'admin', 'financeAdmin', 'instructor', 'student'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }
        (require base_path('database/migrations/2026_11_11_120000_seed_access_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_15_000200_seed_sms_balance_permission.php'))->up();
    }

    public function test_sms_balance_endpoint_requires_the_dedicated_permission(): void
    {
        $this->authenticate(Role::findByName('student', 'web'));
        $this->getJson('/api/sms-balance')->assertForbidden();

        $this->authenticate(Role::findByName('financeAdmin', 'web'));
        $this->getJson('/api/sms-balance')->assertForbidden();

        Http::fake([
            '*/balance' => Http::response(['data' => ['remaining_balance' => 250]], 200),
        ]);
        $this->authenticate(Role::findByName('superAdmin', 'web'));
        $this->getJson('/api/sms-balance')->assertOk()->assertJson(['balance' => 250]);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/balance'));
    }

    public function test_migration_preserves_only_intersecting_legacy_grants_idempotently(): void
    {
        $role = Role::create(['name' => 'sms-manager', 'guard_name' => 'web']);
        $role->givePermissionTo(['manage finance', 'manage system']);
        $partialRole = Role::create(['name' => 'finance-only', 'guard_name' => 'web']);
        $partialRole->givePermissionTo('manage finance');

        $user = new User();
        $user->id = 'direct-sms-manager';
        foreach (['manage finance', 'manage system'] as $name) {
            DB::table(config('permission.table_names.model_has_permissions'))->insert([
                'permission_id' => Permission::findByName($name, 'web')->id,
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->id,
            ]);
        }

        $migration = require base_path('database/migrations/2026_11_15_000200_seed_sms_balance_permission.php');
        $migration->up();
        $migration->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('view sms balance'));
        $this->assertFalse($partialRole->fresh()->hasPermissionTo('view sms balance'));
        $this->assertTrue(Role::findByName('superAdmin', 'web')->hasPermissionTo('view sms balance'));
        $this->assertFalse(Role::findByName('financeAdmin', 'web')->hasPermissionTo('view sms balance'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('view sms balance', 'web')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    private function authenticate(Role $role): void
    {
        $user = new User(['name' => 'SMS balance test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        Sanctum::actingAs($user, ['basic-access']);
    }
}
