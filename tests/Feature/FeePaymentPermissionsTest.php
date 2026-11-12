<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeePaymentPermissionsTest extends TestCase
{
    private const USER_ID = '00000000-0000-4000-8000-000000000001';
    private const OTHER_ID = '00000000-0000-4000-8000-000000000002';
    private const STUDENT_ID = '00000000-0000-4000-8000-000000000011';

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
        (require base_path('database/migrations/2026_11_12_000200_seed_student_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001300_seed_fee_payment_permissions.php'))->up();
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('student_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('sname');
            $table->string('phone')->nullable();
            $table->string('course_id')->nullable();
            $table->string('classroom_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->decimal('invoice_total');
            $table->decimal('invoice_amount_paid');
            $table->decimal('invoice_balance');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->string('transaction_id');
            $table->decimal('amount_paid');
            $table->unsignedBigInteger('payment_method_id');
            $table->string('payment_proof')->nullable();
            $table->string('entered_by');
            $table->timestamps();
            $table->softDeletes();
        });
        (require base_path('database/migrations/2026_11_12_001200_add_payment_recorder.php'))->up();
        (require base_path('database/migrations/2026_11_12_001200_add_payment_recorder.php'))->up();
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
        });
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->string('lesson_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('department_id')->nullable();
        });
        Schema::create('departments', fn (Blueprint $table) => $table->string('id')->primary());
        DB::table('students')->insert([
            'id' => self::STUDENT_ID, 'fname' => '<Test>', 'sname' => 'Student',
        ]);
        DB::table('invoices')->insert([
            'id' => 1, 'student_id' => self::STUDENT_ID,
            'invoice_total' => 100, 'invoice_amount_paid' => 40, 'invoice_balance' => 60,
        ]);
        DB::table('payment_methods')->insert(['id' => 1, 'name' => 'Cash']);
        DB::table('payments')->insert([
            ['id' => 1, 'student_id' => self::STUDENT_ID, 'transaction_id' => 'OWN', 'amount_paid' => 10, 'payment_method_id' => 1, 'entered_by' => 'Same name', 'recorded_by_user_id' => self::USER_ID],
            ['id' => 2, 'student_id' => self::STUDENT_ID, 'transaction_id' => 'OTHER', 'amount_paid' => 10, 'payment_method_id' => 1, 'entered_by' => 'Same name', 'recorded_by_user_id' => self::OTHER_ID],
            ['id' => 3, 'student_id' => self::STUDENT_ID, 'transaction_id' => 'LEGACY', 'amount_paid' => 20, 'payment_method_id' => 1, 'entered_by' => 'Same name', 'recorded_by_user_id' => null],
        ]);
    }

    public function test_own_scope_covers_list_search_and_student_profile(): void
    {
        $role = Role::create(['name' => 'payment-owner', 'guard_name' => 'web']);
        $role->givePermissionTo(['view fee payments', 'view students', 'view all students']);
        $this->authenticate($role);

        $this->get('/payments')->assertOk();
        $rows = $this->getJson('/api/payments?search[value]=Student')->assertOk()->json('data');
        $this->assertCount(1, $rows);
        $this->assertSame('OWN', $rows[0]['transaction_id']);
        $this->assertStringContainsString('&lt;Test&gt;', $rows[0]['student']);
        $this->assertSame('', $rows[0]['actions']);
        $this->getJson('/students/'.self::STUDENT_ID)->assertOk()
            ->assertJsonCount(1, 'student.payment')
            ->assertJsonPath('student.payment.0.transaction_id', 'OWN');
        $this->assertStringContainsString('Fees payments', view('layouts.partials.main_nav')->render());
        $this->deleteJson('/delete-payment/1')->assertForbidden();

        $role->givePermissionTo('view all fee payments');
        $this->authenticate($role);
        $this->getJson('/api/payments')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/students/'.self::STUDENT_ID)->assertOk()->assertJsonCount(3, 'student.payment');
    }

    public function test_payment_deletion_respects_owner_scope(): void
    {
        $role = Role::create(['name' => 'payment-deleter', 'guard_name' => 'web']);
        $role->givePermissionTo('delete fee payments');
        $this->authenticate($role);
        $this->deleteJson('/payments/2')->assertNotFound();
        $this->deleteJson('/delete-payment/3')->assertNotFound();
        $this->delete('/payments/1')->assertRedirect();
        $this->assertSoftDeleted('payments', ['id' => 1]);
        $this->assertDatabaseHas('payments', ['id' => 2, 'deleted_at' => null]);
        $this->assertDatabaseHas('invoices', ['id' => 1, 'invoice_amount_paid' => 30, 'invoice_balance' => 70]);
    }

    public function test_default_administrator_and_finance_roles_use_the_expected_scope(): void
    {
        $this->authenticate(Role::findByName('admin', 'web'));
        $rows = $this->getJson('/api/payments')->assertOk()->json('data');
        $this->assertCount(1, $rows);
        $this->assertSame('OWN', $rows[0]['transaction_id']);

        $this->authenticate(Role::findByName('financeAdmin', 'web'));
        $this->getJson('/api/payments')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_student_profile_does_not_expose_payments_without_permission(): void
    {
        $role = Role::create(['name' => 'student-only-reader', 'guard_name' => 'web']);
        $role->givePermissionTo(['view students', 'view all students']);
        $this->authenticate($role);
        $response = $this->getJson('/students/'.self::STUDENT_ID)->assertOk();
        $this->assertArrayNotHasKey('payment', $response->json('student'));
    }

    public function test_recording_is_atomic_and_tracks_the_user(): void
    {
        $role = Role::create(['name' => 'payment-recorder', 'guard_name' => 'web']);
        $role->givePermissionTo('record fee payments');
        $this->authenticate($role);
        $this->postJson('/add-payment', ['invoice_id' => 1, 'payment_method' => 1, 'paid_amount' => 61])->assertStatus(422);
        $this->postJson('/payments', ['invoice_id' => 1, 'payment_method' => 1, 'paid_amount' => 20])->assertOk();
        $this->assertDatabaseHas('payments', ['student_id' => self::STUDENT_ID, 'amount_paid' => 20, 'recorded_by_user_id' => self::USER_ID]);
        $this->assertDatabaseHas('invoices', ['id' => 1, 'invoice_amount_paid' => 60, 'invoice_balance' => 40]);

        DB::statement("CREATE TRIGGER fail_invoice_update BEFORE UPDATE ON invoices BEGIN SELECT RAISE(FAIL, 'test failure'); END");
        $this->postJson('/add-payment', ['invoice_id' => 1, 'payment_method' => 1, 'paid_amount' => 10])->assertStatus(500);
        $this->assertSame(4, DB::table('payments')->count());
        $this->assertDatabaseHas('invoices', ['id' => 1, 'invoice_amount_paid' => 60, 'invoice_balance' => 40]);
    }

    public function test_legacy_grants_are_migrated_without_expanding_admin_scope(): void
    {
        $role = Role::create(['name' => 'legacy-finance', 'guard_name' => 'web']);
        $role->givePermissionTo(['access management', 'manage finance']);
        $partial = Role::create(['name' => 'legacy-partial', 'guard_name' => 'web']);
        $partial->givePermissionTo('manage finance');
        $user = new User();
        $user->id = self::USER_ID;
        foreach (['access management', 'manage finance'] as $name) {
            DB::table(config('permission.table_names.model_has_permissions'))->insert([
                'permission_id' => Permission::findByName($name)->id,
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->id,
            ]);
        }
        (require base_path('database/migrations/2026_11_12_001300_seed_fee_payment_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001300_seed_fee_payment_permissions.php'))->up();
        $this->assertTrue($role->fresh()->hasPermissionTo('view all fee payments'));
        $this->assertFalse($partial->fresh()->hasPermissionTo('record fee payments'));
        $this->assertFalse(Role::findByName('admin')->hasPermissionTo('view all fee payments'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('record fee payments')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    private function authenticate(Role $role): void
    {
        $user = new User(['name' => 'Same name']);
        $user->id = self::USER_ID;
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}
