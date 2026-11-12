<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdministratorPermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'activitylog.enabled' => false,
        ]);
        (require base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();
        foreach (['superAdmin', 'Super-Admin', 'admin', 'financeAdmin', 'instructor', 'student'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }
        (require base_path('database/migrations/2026_11_11_120000_seed_access_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_002000_seed_administrator_permissions.php'))->up();
        Schema::create('administrators', function (Blueprint $table) {
            $table->id();
            foreach (['fname', 'sname', 'gender', 'phone', 'address', 'date_of_birth', 'district_id'] as $name) {
                $table->string($name)->nullable();
            }
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->integer('administrator_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('districts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
        });
        DB::table('districts')->insert(['id' => 'central', 'name' => 'Central']);
        DB::table('administrators')->insert([
            'id' => 1, 'fname' => 'Example', 'sname' => 'Administrator',
        ]);
        DB::table('users')->insert([
            'id' => 'target', 'administrator_id' => 1, 'name' => 'Target',
            'email' => 'target@example.test', 'password' => 'original',
        ]);
        $this->target()->assignRole(['admin', 'instructor']);
    }

    public function test_viewer_sees_read_only_profile_and_role_based_navigation(): void
    {
        $role = Role::create(['name' => 'administrator-viewer', 'guard_name' => 'web']);
        $role->givePermissionTo('view administrators');
        $this->authenticate($role);
        $this->get('/administrators')->assertOk()->assertDontSee('Add Administrator');
        $row = $this->getJson('/administrators', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data.0');
        $this->assertStringContainsString('View Profile', $row['actions']);
        $this->assertStringNotContainsString('Edit Profile', $row['actions']);
        $this->assertStringNotContainsString('Delete', $row['actions']);
        $this->get('/administrators/1')->assertOk()->assertSee('Example Administrator')
            ->assertDontSee('Edit Profile')->assertDontSee('id="account-tab"', false)
            ->assertDontSee('const account =', false);
        $this->getJson('/administrators/create')->assertForbidden();
        $this->postJson('/storeadministrator')->assertForbidden();
        $this->getJson('/editadministrator/1')->assertForbidden();
        $this->postJson('/updateadministrator')->assertForbidden();
        $this->deleteJson('/deleteadministrator/1')->assertForbidden();
        $this->putJson('/administrators/1/account')->assertForbidden();
        $this->assertStringContainsString('Administrators', view('layouts.partials.main_nav')->render());
    }

    public function test_editing_profile_cannot_change_account_or_roles_without_separate_permissions(): void
    {
        $role = Role::create(['name' => 'administrator-editor', 'guard_name' => 'web']);
        $role->givePermissionTo('edit administrators');
        $this->authenticate($role);
        $this->get('/administrators/1/edit')->assertOk()
            ->assertSee('readonly')->assertDontSee('name="role"', false);
        $data = $this->profileData();
        $this->putJson('/administrators/1', array_merge($data, ['email' => 'other@example.test']))->assertForbidden();
        $this->putJson('/administrators/1', array_merge($data, ['password' => 'new-password']))->assertForbidden();
        $this->putJson('/administrators/1', array_merge($data, ['role' => 'superAdmin']))->assertForbidden();
        $this->putJson('/administrators/1', array_merge($data, ['role' => 'admin']))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => 'target', 'email' => 'target@example.test', 'password' => 'original']);
        $this->assertEqualsCanonicalizing(['admin', 'instructor'], $this->target()->getRoleNames()->all());
        $this->post('/updateadministrator', array_merge($data, ['administrator_id' => 1]))->assertRedirect(route('administrators.index'));
        $this->assertDatabaseHas('administrators', ['id' => 1, 'fname' => 'Updated']);
        $this->assertEqualsCanonicalizing(['admin', 'instructor'], $this->target()->getRoleNames()->all());
    }

    public function test_role_change_requires_explicit_permission_and_valid_supported_role(): void
    {
        $role = Role::create(['name' => 'administrator-role-editor', 'guard_name' => 'web']);
        $role->givePermissionTo(['edit administrators', 'assign administrator roles']);
        $this->authenticate($role);
        $this->get('/administrators/1/edit')->assertOk()
            ->assertSee('<option value="" selected>Keep current roles</option>', false)
            ->assertDontSee('value="instructor"', false);
        $this->put('/administrators/1', array_merge($this->profileData(), ['role' => '']))
            ->assertRedirect(route('administrators.index'));
        $this->assertEqualsCanonicalizing(['admin', 'instructor'], $this->target()->getRoleNames()->all());
        $this->putJson('/administrators/1', array_merge($this->profileData(), ['role' => 'instructor']))
            ->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->put('/administrators/1', array_merge($this->profileData(), ['role' => 'superAdmin']))
            ->assertRedirect(route('administrators.index'));
        $this->assertSame(['superAdmin'], $this->target()->getRoleNames()->all());
    }

    public function test_accountless_profile_can_be_edited_without_creating_a_login(): void
    {
        DB::table('administrators')->insert(['id' => 2, 'fname' => 'No', 'sname' => 'Account']);
        $role = Role::create(['name' => 'accountless-editor', 'guard_name' => 'web']);
        $role->givePermissionTo('edit administrators');
        $this->authenticate($role);
        $data = $this->profileData();
        unset($data['email']);
        $this->put('/administrators/2', $data)->assertRedirect(route('administrators.index'));
        $this->assertDatabaseHas('administrators', ['id' => 2, 'fname' => 'Updated']);
        $this->assertDatabaseMissing('users', ['administrator_id' => 2]);
        $this->putJson('/administrators/2', array_merge($data, [
            'email' => 'new-account@example.test', 'role' => 'admin',
        ]))->assertForbidden();
        $this->assertDatabaseMissing('users', ['administrator_id' => 2]);
        $role->givePermissionTo('assign administrator roles');
        $this->authenticate($role);
        $this->putJson('/administrators/2', array_merge($data, [
            'email' => 'new-account@example.test', 'role' => 'financeAdmin',
        ]))->assertForbidden();
        $role->givePermissionTo('manage administrator accounts');
        $this->authenticate($role);
        $this->put('/administrators/2', array_merge($data, [
            'email' => 'new-account@example.test', 'role' => 'financeAdmin',
        ]))->assertRedirect(route('administrators.index'));
        $this->assertTrue(User::where('administrator_id', 2)->firstOrFail()->hasRole('financeAdmin'));
    }

    public function test_broad_administration_permission_alone_no_longer_authorizes_account_updates(): void
    {
        $role = Role::create(['name' => 'old-admin-manager', 'guard_name' => 'web']);
        $role->givePermissionTo('manage administration');
        $this->authenticate($role);
        $this->putJson('/administrators/1/account', [])->assertForbidden();
    }

    public function test_guests_cannot_access_administrator_actions(): void
    {
        $this->getJson('/administrators')->assertUnauthorized();
        $this->getJson('/viewadministrator')->assertUnauthorized();
        $this->putJson('/administrators/1', [])->assertUnauthorized();
        $this->deleteJson('/deleteadministrator/1')->assertUnauthorized();
    }

    public function test_creation_requires_role_assignment_and_deletion_has_its_own_permission(): void
    {
        $role = Role::create(['name' => 'administrator-creator', 'guard_name' => 'web']);
        $role->givePermissionTo('create administrators');
        $this->authenticate($role);
        $this->getJson('/addadministrator')->assertForbidden();
        $this->postJson('/administrators')->assertForbidden();
        $role->givePermissionTo('assign administrator roles');
        $this->authenticate($role);
        $this->get('/administrators/create')->assertOk()
            ->assertDontSee('value="instructor"', false);
        $data = array_merge($this->profileData(), [
            'email' => 'new@example.test', 'username' => 'new-admin',
            'password' => 'password123', 'role' => 'financeAdmin',
        ]);
        $this->postJson('/administrators', array_merge($data, ['role' => 'student']))
            ->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->post('/administrators', $data)->assertRedirect(route('administrators.index'));
        $newUser = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertTrue($newUser->hasRole('financeAdmin'));
        $this->deleteJson('/administrators/'.$newUser->administrator_id)->assertForbidden();
        $role->givePermissionTo('delete administrators');
        $this->authenticate($role);
        $this->delete('/administrators/'.$newUser->administrator_id)->assertRedirect(route('administrators.index'));
        $this->assertSoftDeleted('administrators', ['id' => $newUser->administrator_id]);
        $this->assertSoftDeleted('users', ['id' => $newUser->id]);
    }

    public function test_migration_preserves_legacy_role_and_direct_grants_idempotently(): void
    {
        $role = Role::create(['name' => 'legacy-admin-manager', 'guard_name' => 'web']);
        $role->givePermissionTo('manage administration');
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => Permission::findByName('manage administration', 'web')->id,
            'model_type' => (new User())->getMorphClass(), 'model_id' => 'direct-manager',
        ]);
        $migration = require base_path('database/migrations/2026_11_12_002000_seed_administrator_permissions.php');
        $migration->up();
        $migration->up();
        foreach ([
            'view administrators', 'create administrators', 'edit administrators', 'delete administrators',
            'manage administrator accounts', 'assign administrator roles',
        ] as $name) {
            $this->assertTrue($role->fresh()->hasPermissionTo($name));
            $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
                'permission_id' => Permission::findByName($name, 'web')->id,
                'model_type' => (new User())->getMorphClass(), 'model_id' => 'direct-manager',
            ]);
        }
    }

    private function profileData(): array
    {
        return [
            'first_name' => 'Updated', 'sir_name' => 'Administrator', 'email' => 'target@example.test',
            'address' => 'Address', 'gender' => 'male', 'date_of_birth' => '1990-01-01',
            'district' => 'Central', 'phone' => '123',
        ];
    }

    private function target(): User
    {
        return User::where('id', 'target')->firstOrFail();
    }

    private function authenticate(Role $role): void
    {
        $user = new User(['name' => 'Administrator permissions test']);
        $user->id = 'manager';
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}
