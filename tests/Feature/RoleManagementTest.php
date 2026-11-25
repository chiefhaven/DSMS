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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\AccessPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
            'payments.index' => 'permission:view fee payments',
            'payments.store' => 'permission:record fee payments',
            'api.payments.index' => 'permission:view fee payments',
            'expense.expensePayments' => 'permission:view expense payments',
            'api.bulk-attendances.index' => 'permission:view bulk attendances',
            'api.bulk-attendances.store' => 'permission:create bulk attendances',
            'api.bulk-attendances.update' => 'permission:edit bulk attendances',
            'api.bulk-attendances.destroy' => 'permission:delete bulk attendances',
            'bulk-attendances.create' => 'permission:create bulk attendances',
            'bulk-attendances.edit' => 'permission:edit bulk attendances',
            'attendances.index' => 'permission:view attendances',
            'fetch-attendances' => 'permission:view attendances',
            'attendances.store' => 'permission:record attendances',
            'attendances.update' => 'permission:edit attendances',
            'attendances.destroy' => 'permission:delete attendances',
            'attendanceSummary' => 'permission:download attendance reports',
            'invoice-settings.update' => 'permission:edit invoice settings',
            'invoicesettings-update' => 'permission:edit invoice settings',
        ] as $name => $permission) {
            $this->assertContains($permission, $routes->getByName($name)->gatherMiddleware(), $name);
        }

        foreach ([
            '/bulk-attendances' => 'permission:view bulk attendances',
            '/add-bulk-attendance' => 'permission:create bulk attendances',
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

    public function test_role_forms_and_requests_accept_only_web_guard_permissions(): void
    {
        Permission::create(['name' => 'create roles', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit roles', 'guard_name' => 'web']);
        Permission::create(['name' => 'api-only', 'guard_name' => 'api']);

        $form = app(RoleController::class)->create();
        $this->assertNotContains('api-only', $form->getData()['permissionGroups']->flatten()->all());

        $target = Role::create(['name' => 'target', 'guard_name' => 'web']);
        $editorRole = Role::create(['name' => 'role-editor', 'guard_name' => 'web']);
        $editorRole->givePermissionTo(['create roles', 'edit roles']);
        $user = new User;
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$editorRole]));

        $this->actingAs($user)
            ->postJson('/roles', ['name' => 'api-permission-role', 'permissions' => ['api-only']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissions.0');

        $this->putJson('/roles/'.$target->id, ['name' => 'target', 'permissions' => ['api-only']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissions.0');
    }

    public function test_role_management_is_scoped_to_the_web_guard(): void
    {
        $apiRole = Role::create(['name' => 'api-role', 'guard_name' => 'api']);
        Role::create(['name' => 'web-role', 'guard_name' => 'web']);

        $roles = app(RoleController::class)->index()->getData()['roles'];
        $this->assertSame(['web-role'], $roles->pluck('name')->all());
        $this->assertTrue(validator(
            ['name' => 'api-role'],
            (new StoreRoleRequest())->rules()
        )->passes());

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        app(RoleController::class)->edit($apiRole);
    }

    public function test_access_permissions_are_granted_when_roles_are_seeded(): void
    {
        (new AccessPermissionsSeeder())->run();

        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('manage administration'));
        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('manage student tickets'));
        foreach ([
            'view all student tickets',
            'assign student tickets',
            'reply to student tickets',
            'close student tickets',
            'reopen student tickets',
        ] as $permission) {
            $this->assertTrue(Role::findByName('admin')->hasPermissionTo($permission));
        }
        $this->assertTrue(Role::findByName('student')->hasPermissionTo('create student tickets'));
        $this->assertTrue(Role::findByName('financeAdmin')->hasPermissionTo('manage finance'));
        $this->assertTrue(Role::findByName('instructor')->hasPermissionTo('manage operations'));
        $this->assertTrue(Role::findByName('student')->hasPermissionTo('access portal'));
        $this->assertTrue(Role::findByName('superAdmin')->hasPermissionTo('manage system'));

        foreach ([
            'view hr departments',
            'manage hr departments',
            'view designations',
            'manage designations',
            'view salary grades',
            'manage salary grades',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $groups = app(RoleController::class)->create()->getData()['permissionGroups'];
        $groupNames = $groups->keys()->all();
        $sortedGroupNames = $groupNames;
        sort($sortedGroupNames, SORT_NATURAL | SORT_FLAG_CASE);
        $this->assertSame($sortedGroupNames, $groupNames);
        $this->assertContains('manage student tickets', $groups->get('Student tickets')->all());
        foreach ([
            'view student tickets',
            'view all student tickets',
            'create student tickets',
            'assign student tickets',
            'reply to student tickets',
            'close student tickets',
            'reopen student tickets',
        ] as $permission) {
            $this->assertContains($permission, $groups->get('Student tickets')->all());
        }
        $this->assertContains('view all students', $groups->get('Students')->all());
        $this->assertContains('approve student TRN', $groups->get('Students')->all());
        $this->assertContains('view bulk attendances', $groups->get('Attendance')->all());
        $this->assertContains('view payroll', $groups->get('HR & Payroll')->all());
        $this->assertContains('view leaves', $groups->get('HR & Payroll')->all());
        $this->assertContains('manage hr departments', $groups->get('HR & Payroll')->all());
        $this->assertContains('manage designations', $groups->get('HR & Payroll')->all());
        $this->assertContains('manage salary grades', $groups->get('HR & Payroll')->all());
        $groupedPermissions = $groups->flatten()->all();
        $this->assertCount(Permission::where('guard_name', 'web')->count(), $groupedPermissions);
        $this->assertCount(count(array_unique($groupedPermissions)), $groupedPermissions);
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

    public function test_sms_transport_helper_does_not_require_an_unrelated_admin_permission(): void
    {
        config([
            'services.smsApi.url' => 'https://sms.example.test',
            'services.smsApi.token' => 'test-token',
            'services.smsApi.from' => 'Test',
        ]);
        Http::fake(['https://sms.example.test/*' => Http::response(['sent' => true], 200)]);

        $response = app(NotificationController::class)->sendSMS('message', '123');

        $this->assertSame(200, $response['statusCode']);
        Http::assertSent(fn ($request) => $request->url() === 'https://sms.example.test/sms/send');
    }

    public function test_unprivileged_user_cannot_create_invoice_payment(): void
    {
        $user = new User;
        $user->setRelation('roles', collect());
        $this->actingAs($user);

        $this->postJson('/payments', [])->assertForbidden();
    }

    public function test_unused_role_can_be_deleted(): void
    {
        $role = Role::create(['name' => 'clerk']);
        $role->syncPermissions(['view students', 'edit students']);

        app(RoleController::class)->destroy($role);

        $this->assertNull(Role::where('name', 'clerk')->first());
    }
}
