<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpensePaymentPermissionsTest extends TestCase
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
        (require base_path('database/migrations/2026_11_12_000900_seed_expense_payment_permissions.php'))->up();
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('expense_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
        });
        Schema::create('expense_type_options', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('expense_type_id');
        });
        Schema::create('expense_payments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('student_id');
            $table->string('expense_id');
            $table->decimal('amount');
            $table->timestamps();
        });
        Schema::create('expense_student', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->string('expense_id');
            $table->decimal('paid');
            $table->decimal('balance');
            $table->boolean('status');
            $table->timestamps();
        });
    }

    public function test_reader_cannot_record_delete_or_download_reports(): void
    {
        $role = Role::create(['name' => 'payment-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view expense payments');
        $this->authenticate($role);

        $this->get('/expense-payments')->assertOk()->assertDontSee('id="customDateModal"', false);
        $this->get('/scan-to-pay')->assertForbidden();
        $this->postJson('/api/studentExpensePayment/student/expense', [])->assertForbidden();
        $this->postJson('/api/delete-payment/missing')->assertForbidden();
        $this->get('/expense-payment-receipt/missing')->assertForbidden();
        $this->get('/expensePaymentReportSummary?period=today')->assertForbidden();
        $this->assertStringContainsString('Expense payments', view('layouts.partials.main_nav')->render());
    }

    public function test_recording_and_deletion_are_independent(): void
    {
        $role = Role::create(['name' => 'payment-clerk', 'guard_name' => 'web']);
        $role->givePermissionTo('record expense payments');
        $this->authenticate($role);

        $this->get('/scan-to-pay')->assertOk();
        $this->getJson('/api/fetch-expense-types')->assertOk()->assertExactJson([]);
        $this->postJson('/api/studentExpensePayment/student/expense', [])->assertUnprocessable();
        $this->postJson('/api/delete-payment/payment')->assertForbidden();

        DB::table('expense_payments')->insert(['id' => 'payment', 'student_id' => 'student', 'expense_id' => 'expense', 'amount' => 25]);
        DB::table('expense_student')->insert(['student_id' => 'student', 'expense_id' => 'expense', 'paid' => 25, 'balance' => 0, 'status' => 1]);
        $role->givePermissionTo('delete expense payments');
        $this->authenticate($role);
        $this->postJson('/api/delete-payment/payment')->assertOk();
        $this->assertDatabaseMissing('expense_payments', ['id' => 'payment']);
        $this->assertDatabaseHas('expense_student', ['student_id' => 'student', 'paid' => 0, 'balance' => 25, 'status' => 0]);
    }

    public function test_report_and_receipt_permissions_work_without_broad_management_access(): void
    {
        $role = Role::create(['name' => 'payment-auditor', 'guard_name' => 'web']);
        $role->givePermissionTo(['view expense payments', 'report expense payments', 'download expense payment receipts']);
        $this->authenticate($role);

        $this->get('/expense-payments')->assertOk()->assertSee('id="customDateModal"', false);
        $this->get('/expensePaymentReportSummary')->assertRedirect();
        $this->get('/expense-payment-receipt/missing')->assertNotFound();
        $this->postJson('/api/delete-payment/missing')->assertForbidden();
    }

    public function test_migration_preserves_only_qualified_legacy_grants(): void
    {
        $role = Role::create(['name' => 'legacy-finance', 'guard_name' => 'web']);
        $role->givePermissionTo(['access management', 'manage finance']);
        $partial = Role::create(['name' => 'partial-finance', 'guard_name' => 'web']);
        $partial->givePermissionTo('manage finance');
        $user = new User();
        $user->id = 'legacy-direct';
        foreach (['access management', 'manage finance'] as $name) {
            DB::table(config('permission.table_names.model_has_permissions'))->insert([
                'permission_id' => Permission::findByName($name)->id,
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->id,
            ]);
        }
        (require base_path('database/migrations/2026_11_12_000900_seed_expense_payment_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000900_seed_expense_payment_permissions.php'))->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('record expense payments'));
        $this->assertFalse($partial->fresh()->hasPermissionTo('record expense payments'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('record expense payments')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
        $this->assertFalse(Role::findByName('financeAdmin')->hasPermissionTo('delete expense payments'));
    }

    private function authenticate(Role $role): void
    {
        $user = new User(['name' => 'Payment test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}
