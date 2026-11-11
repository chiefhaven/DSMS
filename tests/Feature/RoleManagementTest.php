<?php

namespace Tests\Feature;

use App\Http\Controllers\RoleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\AccessPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);

        Schema::create('users', function ($table) {
            $table->uuid('id')->primary();
            $table->softDeletes();
        });
        (require base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();

        Permission::create(['name' => 'view students']);
        Permission::create(['name' => 'edit students']);
    }

    public function test_routes_require_their_specific_role_permissions(): void
    {
        foreach ([
            'roles.index' => 'view roles',
            'roles.create' => 'create roles',
            'roles.store' => 'create roles',
            'roles.edit' => 'edit roles',
            'roles.update' => 'edit roles',
            'roles.destroy' => 'delete roles',
        ] as $name => $permission) {
            $middleware = app('router')->getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertContains('permission:'.$permission, $middleware, $name);
        }
    }

    public function test_role_action_migration_preserves_existing_system_grants(): void
    {
        $system = Permission::create(['name' => 'manage system', 'guard_name' => 'web']);
        $manager = Role::create(['name' => 'system-manager', 'guard_name' => 'web']);
        $manager->givePermissionTo($system);
        $other = Role::create(['name' => 'unprivileged', 'guard_name' => 'web']);
        $user = new User;
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect());
        DB::table('model_has_permissions')->insert([
            'permission_id' => $system->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);

        $migration = require base_path('database/migrations/2026_11_12_000100_seed_role_action_permissions.php');
        $migration->up();
        $migration->up();

        foreach (['view roles', 'create roles', 'edit roles', 'delete roles'] as $permission) {
            $this->assertTrue($manager->hasPermissionTo($permission));
            $this->assertTrue($user->can($permission));
            $this->assertFalse($other->hasPermissionTo($permission));
        }
    }

    public function test_role_viewer_cannot_change_roles(): void
    {
        (require base_path('database/migrations/2026_11_12_000100_seed_role_action_permissions.php'))->up();
        $viewer = Role::create(['name' => 'role-viewer', 'guard_name' => 'web']);
        $viewer->givePermissionTo('view roles');
        $target = Role::create(['name' => 'target', 'guard_name' => 'web']);
        $user = new User;
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$viewer]));
        $this->actingAs($user);

        $this->getJson('/roles/create')->assertForbidden();
        $this->postJson('/roles', [])->assertForbidden();
        $this->putJson('/roles/'.$target->id, [])->assertForbidden();
        $this->deleteJson('/roles/'.$target->id)->assertForbidden();
    }

    public function test_sensitive_routes_require_their_access_permissions(): void
    {
        $routes = app('router')->getRoutes();

        foreach ([
            'payments.index' => 'permission:access management',
            'payments.store' => 'permission:access management',
            'api.payments.index' => 'permission:access management',
            'expense.expensePayments' => 'permission:access management',
            'api.bulk-attendances.index' => 'permission:access operations',
            'api.bulk-attendances.store' => 'permission:manage operations',
            'api.bulk-attendances.update' => 'permission:manage operations',
            'api.bulk-attendances.destroy' => 'permission:manage operations',
            'bulk-attendances.create' => 'permission:manage operations',
            'bulk-attendances.edit' => 'permission:manage operations',
            'invoice-settings.update' => 'permission:edit invoice settings',
            'invoicesettings-update' => 'permission:edit invoice settings',
        ] as $name => $permission) {
            $this->assertContains($permission, $routes->getByName($name)->gatherMiddleware(), $name);
        }

        foreach ([
            '/bulk-attendances' => 'permission:access operations',
            '/add-bulk-attendance' => 'permission:manage operations',
        ] as $path => $permission) {
            $route = $routes->match(Request::create($path, 'GET'));
            $this->assertContains($permission, $route->gatherMiddleware(), $path);
        }

        $expenseTypes = $routes->match(Request::create('/expense-types', 'GET'));
        $this->assertContains('permission:view expense types', $expenseTypes->gatherMiddleware());
    }

    public function test_requests_only_authorize_super_admin(): void
    {
        $this->assertFalse((new StoreRoleRequest())->authorize());
        $this->assertFalse((new UpdateRoleRequest())->authorize());
    }

    public function test_store_rules_require_known_permissions(): void
    {
        $rules = (new StoreRoleRequest())->rules();

        $this->assertTrue(validator(['name' => 'x', 'permissions' => ['nope']], $rules)->errors()->has('permissions.0'));
        $this->assertTrue(validator(['name' => 'cashier', 'permissions' => ['view students']], $rules)->passes());
    }

    public function test_access_permissions_are_granted_when_roles_are_seeded(): void
    {
        (new AccessPermissionsSeeder())->run();

        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('manage administration'));
        $this->assertTrue(Role::findByName('financeAdmin')->hasPermissionTo('manage finance'));
        $this->assertTrue(Role::findByName('instructor')->hasPermissionTo('manage operations'));
        $this->assertTrue(Role::findByName('student')->hasPermissionTo('access portal'));
        $this->assertTrue(Role::findByName('superAdmin')->hasPermissionTo('manage system'));
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $role = Role::create(['name' => 'superAdmin']);

        app(RoleController::class)->destroy($role);

        $this->assertNotNull(Role::where('name', 'superAdmin')->first());
    }

    public function test_legacy_super_admin_role_cannot_be_deleted(): void
    {
        $role = Role::create(['name' => 'Super-Admin']);

        app(RoleController::class)->destroy($role);

        $this->assertNotNull(Role::where('name', 'Super-Admin')->first());
    }

    public function test_unprivileged_user_cannot_send_sms(): void
    {
        $user = new User;
        $user->setRelation('roles', collect());
        $this->actingAs($user);

        $this->expectException(HttpException::class);
        app(NotificationController::class)->sendSMS('message', '123');
    }

    public function test_unprivileged_user_cannot_create_invoice_payment(): void
    {
        $user = new User;
        $user->setRelation('roles', collect());
        $this->actingAs($user);

        $this->expectException(HttpException::class);
        app(PaymentController::class)->store(new Request());
    }

    public function test_unused_role_can_be_deleted(): void
    {
        $role = Role::create(['name' => 'clerk']);
        $role->syncPermissions(['view students', 'edit students']);

        app(RoleController::class)->destroy($role);

        $this->assertNull(Role::where('name', 'clerk')->first());
    }
}
