<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseTypePermissionsTest extends TestCase
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
        foreach (['superAdmin', 'Super-Admin', 'admin', 'financeAdmin', 'instructor', 'student'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }
        (require base_path('database/migrations/2026_11_11_120000_seed_access_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000600_seed_expense_type_permissions.php'))->up();
        Schema::create('expense_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function test_reader_can_access_web_and_api_but_cannot_change_types(): void
    {
        $role = Role::create(['name' => 'expense-type-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view expense types');
        $this->authenticate('expense-type-reader');

        $this->get('/expense-types')->assertOk()
            ->assertDontSee('Add expense type')
            ->assertDontSee('id="expenseTypeModal"', false);
        $this->getJson('/api/expense-types')->assertOk()->assertJson(['data' => []]);
        $this->getJson('/api/fetch-expense-types')->assertOk()->assertExactJson([]);
        $this->postJson('/api/expense-types', [])->assertForbidden();
        $this->putJson('/api/expense-types/missing', [])->assertForbidden();
        $this->deleteJson('/api/expense-types/missing')->assertForbidden();
    }

    public function test_create_and_delete_permissions_are_independent(): void
    {
        $role = Role::create(['name' => 'expense-type-creator', 'guard_name' => 'web']);
        $role->givePermissionTo('create expense types');
        $this->authenticate('expense-type-creator');

        $this->postJson('/api/expense-types', [])->assertUnprocessable();
        $this->deleteJson('/api/expense-types/39d3f058-4f04-11f0-aa86-52540066f921')->assertForbidden();

        $role->givePermissionTo('delete expense types');
        $this->authenticate('expense-type-creator');
        $this->deleteJson('/api/expense-types/39d3f058-4f04-11f0-aa86-52540066f921')->assertStatus(400);
    }

    public function test_migration_preserves_only_existing_qualified_role_and_direct_grants(): void
    {
        $role = Role::create(['name' => 'legacy-manager', 'guard_name' => 'web']);
        $role->givePermissionTo(['access management', 'manage administration']);
        $systemOnly = Role::create(['name' => 'system-only', 'guard_name' => 'web']);
        $systemOnly->givePermissionTo('manage system');
        $user = new User();
        $user->id = 'legacy-direct';
        foreach (['access management', 'manage administration'] as $name) {
            DB::table(config('permission.table_names.model_has_permissions'))->insert([
                'permission_id' => Permission::findByName($name)->id,
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->id,
            ]);
        }

        (require base_path('database/migrations/2026_11_12_000600_seed_expense_type_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000600_seed_expense_type_permissions.php'))->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('edit expense types'));
        $this->assertFalse($role->fresh()->hasPermissionTo('delete expense types'));
        $this->assertFalse($systemOnly->fresh()->hasPermissionTo('delete expense types'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('create expense types')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
        $this->assertFalse(Role::findByName('admin')->hasPermissionTo('delete expense types'));
        $this->assertTrue(Role::findByName('financeAdmin')->hasPermissionTo('view expense types'));
    }

    private function authenticate(string $role): void
    {
        $user = new User(['name' => 'Expense type test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([Role::findByName($role, 'web')]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}
