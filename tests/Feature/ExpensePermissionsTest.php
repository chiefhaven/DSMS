<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpensePermissionsTest extends TestCase
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
        (require base_path('database/migrations/2026_11_12_001000_seed_expense_permissions.php'))->up();
        Schema::create('settings', fn (Blueprint $table) => $table->id());
        Schema::create('administrators', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fname');
            $table->string('sname');
            $table->softDeletes();
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('group');
            $table->string('added_by');
            $table->boolean('approved')->default(false);
            $table->string('group_type')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount')->default(0);
            $table->string('edited_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('date_approved')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
        Schema::create('expense_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
        });
        Schema::create('expense_type_options', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('expense_type_id');
        });
        Schema::create('students', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('course_id')->nullable();
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('sname');
            $table->softDeletes();
        });
        Schema::create('expense_student', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->string('expense_id');
            $table->string('expense_type')->nullable();
            $table->decimal('amount')->nullable();
            $table->boolean('repeat')->nullable();
            $table->boolean('status')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_entered_by')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('balance')->nullable();
            $table->decimal('paid')->nullable();
            $table->timestamps();
        });
        DB::table('administrators')->insert(['id' => 'admin-1', 'fname' => 'Test', 'sname' => 'Admin']);
        DB::table('expenses')->insert([
            ['id' => '00000000-0000-4000-8000-000000000011', 'group' => '01/01/2026', 'added_by' => 'admin-1', 'approved' => 0],
            ['id' => '00000000-0000-4000-8000-000000000012', 'group' => '02/01/2026', 'added_by' => 'admin-1', 'approved' => 1],
        ]);
        DB::table('expense_student')->insert([
            ['student_id' => 'student-1', 'expense_id' => '00000000-0000-4000-8000-000000000011'],
            ['student_id' => 'student-1', 'expense_id' => '00000000-0000-4000-8000-000000000012'],
        ]);
    }

    public function test_reader_can_view_but_not_mutate_or_review_expenses(): void
    {
        $role = Role::create(['name' => 'expense-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view expenses');
        $this->authenticate($role);

        $this->get('/expenses')->assertOk()->assertDontSee('Add expense');
        $response = $this->getJson('/api/expenses')->assertOk()->json('data');
        $this->assertCount(2, $response);
        $this->assertStringContainsString('View', $response[0]['actions']);
        $this->assertStringNotContainsString('Edit', $response[0]['actions']);
        $this->assertStringNotContainsString('Delete', $response[0]['actions']);
        $this->get('/expenses/create')->assertForbidden();
        $this->get('/review-expense/00000000-0000-4000-8000-000000000011')->assertForbidden();
        $this->postJson('/expenses', [])->assertForbidden();
        $this->putJson('/expenses/00000000-0000-4000-8000-000000000011', [])->assertForbidden();
        $this->deleteJson('/expenses/00000000-0000-4000-8000-000000000011')->assertForbidden();
        $this->postJson('/removeStudent', ['student' => 'student-1', 'expenseId' => '00000000-0000-4000-8000-000000000011'])->assertForbidden();
        $this->postJson('/approveList', [])->assertForbidden();
        $this->get('/expensedownload/00000000-0000-4000-8000-000000000011')->assertForbidden();
        $this->get('/expense-payment-report/00000000-0000-4000-8000-000000000011')->assertForbidden();
        $html = view('layouts.partials.main_nav')->render();
        $this->assertStringContainsString('All expenses', $html);
        $this->assertStringNotContainsString('Add expense', $html);
    }

    public function test_creator_and_reviewer_have_independent_actions(): void
    {
        $role = Role::create(['name' => 'expense-creator', 'guard_name' => 'web']);
        $role->givePermissionTo('create expenses');
        $this->authenticate($role);
        $this->get('/expenses/create')->assertOk();
        $this->getJson('/api/fetch-expense-types')->assertOk()->assertExactJson([]);
        $this->postJson('/checkStudent', [])->assertUnprocessable();
        $this->getJson('/expense-student-search?student=Test')->assertOk()->assertExactJson([]);
        $this->postJson('/approveList', [])->assertForbidden();
        $this->assertStringContainsString('Add expense', view('layouts.partials.main_nav')->render());

        $reviewer = Role::create(['name' => 'expense-reviewer', 'guard_name' => 'web']);
        $reviewer->givePermissionTo('review expenses');
        $this->authenticate($reviewer);
        $this->getJson('/api/fetch-expense-types')->assertOk()->assertExactJson([]);
        $this->get('/review-expense/00000000-0000-4000-8000-000000000011')->assertOk()
            ->assertDontSee('@click="approveList"', false)
            ->assertDontSee('Edit Expense');
        $this->postJson('/approveList', [])->assertForbidden();
        $this->postJson('/removeStudent', ['student' => 'student-1', 'expenseId' => '00000000-0000-4000-8000-000000000011'])->assertOk();
        $this->assertDatabaseMissing('expense_student', ['student_id' => 'student-1', 'expense_id' => '00000000-0000-4000-8000-000000000011']);
        $reviewer->givePermissionTo('approve expenses');
        $this->authenticate($reviewer);
        $this->get('/review-expense/00000000-0000-4000-8000-000000000011')->assertOk()->assertSee('@click="approveList"', false);
        $this->postJson('/approveList', [])->assertUnprocessable();
    }

    public function test_approved_expenses_cannot_be_changed_or_deleted(): void
    {
        $role = Role::create(['name' => 'expense-editor', 'guard_name' => 'web']);
        $role->givePermissionTo(['edit expenses', 'delete expenses']);
        $this->authenticate($role);

        $this->put('/expenses/00000000-0000-4000-8000-000000000012', ['expenseGroupName' => 'changed', 'students' => [['id' => 'student-1']]])->assertRedirect();
        $this->postJson('/removeStudent', ['student' => 'student-1', 'expenseId' => '00000000-0000-4000-8000-000000000012'])->assertStatus(409);
        $this->deleteJson('/expenses/00000000-0000-4000-8000-000000000012')->assertStatus(409);
        $this->assertDatabaseHas('expenses', ['id' => '00000000-0000-4000-8000-000000000012', 'group' => '02/01/2026', 'approved' => 1]);
        $this->assertDatabaseHas('expense_student', ['student_id' => 'student-1', 'expense_id' => '00000000-0000-4000-8000-000000000012']);
    }

    public function test_bulk_attendance_lookup_keeps_its_existing_access_boundary(): void
    {
        $role = Role::create(['name' => 'attendance-manager', 'guard_name' => 'web']);
        $role->givePermissionTo(['access management', 'manage operations']);
        $this->authenticate($role);
        $this->getJson('/expense-student-search?student=Test')->assertOk()->assertExactJson([]);
        $role->revokePermissionTo('access management');
        $this->authenticate($role);
        $this->getJson('/expense-student-search?student=Test')->assertForbidden();
    }

    public function test_migration_preserves_qualified_role_and_direct_grants(): void
    {
        $role = Role::create(['name' => 'legacy-expense-manager', 'guard_name' => 'web']);
        $role->givePermissionTo(['access management', 'manage administration']);
        $partial = Role::create(['name' => 'system-only', 'guard_name' => 'web']);
        $partial->givePermissionTo('manage system');
        $user = new User();
        $user->id = 'legacy-direct';
        foreach (['access management', 'manage system'] as $name) {
            DB::table(config('permission.table_names.model_has_permissions'))->insert([
                'permission_id' => Permission::findByName($name)->id,
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->id,
            ]);
        }
        (require base_path('database/migrations/2026_11_12_001000_seed_expense_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001000_seed_expense_permissions.php'))->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('edit expenses'));
        $this->assertFalse($role->fresh()->hasPermissionTo('approve expenses'));
        $this->assertFalse($partial->fresh()->hasPermissionTo('delete expenses'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('approve expenses')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    private function authenticate(Role $role): void
    {
        $user = new User(['name' => 'Expense test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}
