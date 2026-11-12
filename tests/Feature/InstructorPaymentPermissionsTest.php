<?php

namespace Tests\Feature;

use App\Http\Controllers\InstructorPaymentController;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstructorPaymentPermissionsTest extends TestCase
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
        (require base_path('database/migrations/2026_11_12_001700_seed_instructor_payment_permissions.php'))->up();
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('bonus')->nullable();
        });
        Schema::create('instructors', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fname');
            $table->string('sname');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('administrator_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('administrators', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fname');
            $table->softDeletes();
        });
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('instructor_id');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('instructor_payments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('instructor_id');
            $table->integer('attendances_count');
            $table->decimal('pay_per_attendance');
            $table->decimal('total_payment');
            $table->date('payment_date');
            $table->string('payment_month');
            $table->string('status');
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
        DB::table('settings')->insert(['id' => 1, 'bonus' => 25]);
        DB::table('instructors')->insert([
            'id' => 'instructor-one', 'fname' => '<Test>', 'sname' => 'Instructor',
            'status' => 'active', 'created_at' => '2026-10-01',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-11-27 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_viewer_can_read_list_but_cannot_process_or_access_stub_actions(): void
    {
        DB::table('instructor_payments')->insert([
            'id' => 'payment-one', 'instructor_id' => 'instructor-one', 'attendances_count' => 2,
            'pay_per_attendance' => 25, 'total_payment' => 50, 'payment_date' => '2026-10-28',
            'payment_month' => '2026-10', 'status' => 'paid',
        ]);
        $role = Role::create(['name' => 'bonus-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view instructor payments');
        $this->authenticate($role);

        $this->get('/instructor-payments')->assertOk();
        $row = $this->getJson('/fetchPayments')->assertOk()->json('data.0');
        $this->assertSame('&lt;Test&gt; Instructor', $row['instructor']);
        $this->assertArrayNotHasKey('created_by', $row);
        $this->postJson('/instructor-payments')->assertForbidden();
        $this->postJson('/api/bonuses/pay-early')->assertForbidden();
        $this->getJson('/instructors/payment/payment-one')->assertForbidden();
        $this->assertStringContainsString('Bonus Payments', view('layouts.partials.main_nav')->render());
    }

    public function test_processing_requires_permission_even_when_controller_is_called_directly(): void
    {
        $this->authenticate(Role::findByName('admin', 'web'));
        $this->assertSame(403, app(InstructorPaymentController::class)->store()->getStatusCode());
        $this->postJson('/instructor-payments')->assertForbidden();
        $this->assertDatabaseCount('instructor_payments', 0);

        $role = Role::create(['name' => 'bonus-processor', 'guard_name' => 'web']);
        $role->givePermissionTo('process instructor payments');
        $this->authenticate($role);
        $this->getJson('/fetchPayments')->assertForbidden();
        DB::table('attendances')->insert([
            'instructor_id' => 'instructor-one', 'created_at' => '2026-11-20 12:00:00',
        ]);
        $this->postJson('/api/bonuses/pay-early')->assertOk();
        $this->assertDatabaseHas('instructor_payments', [
            'instructor_id' => 'instructor-one', 'attendances_count' => 1,
            'total_payment' => 25, 'payment_month' => '2026-11',
            'created_by' => '00000000-0000-4000-8000-000000000001',
        ]);
        $this->postJson('/instructor-payments')->assertStatus(409);
        $this->assertDatabaseCount('instructor_payments', 1);
    }

    public function test_guests_and_students_cannot_read_or_process_payments(): void
    {
        $this->getJson('/fetchPayments')->assertUnauthorized();
        $this->getJson('/instructors/payment/payment-one')->assertUnauthorized();
        $this->authenticate(Role::findByName('student', 'web'));
        $this->getJson('/fetchPayments')->assertForbidden();
        $this->postJson('/api/bonuses/pay-early')->assertForbidden();
        $this->assertStringNotContainsString('Bonus Payments', view('layouts.partials.main_nav')->render());
    }

    public function test_dashboard_button_is_shown_only_to_payment_processors(): void
    {
        $role = Role::create(['name' => 'dashboard-reader', 'guard_name' => 'web']);
        $this->authenticate($role);
        $data = [
            'students' => collect(), 'activities' => collect(),
            'instructors' => collect(), 'settings' => (object) ['bonus' => 25],
        ];
        $this->assertStringNotContainsString('id="bonuses"', view('dashboard_partials.super_admin', $data)->render());
        $role->givePermissionTo('process instructor payments');
        $this->authenticate($role);
        $this->assertStringContainsString('id="bonuses"', view('dashboard_partials.super_admin', $data)->render());
    }

    public function test_scheduled_command_can_still_process_with_super_admin_account(): void
    {
        $user = new User();
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->save();
        $user->assignRole('superAdmin');
        DB::table('attendances')->insert([
            'instructor_id' => 'instructor-one', 'created_at' => '2026-11-20 12:00:00',
        ]);
        $this->artisan('pay:early-bonuses')->expectsOutput('Early bonus payment processed successfully.')->assertExitCode(0);
        $this->assertDatabaseHas('instructor_payments', [
            'instructor_id' => 'instructor-one', 'total_payment' => 25,
            'payment_month' => '2026-11', 'created_by' => $user->id,
        ]);
    }

    public function test_migration_preserves_existing_role_and_direct_grants_idempotently(): void
    {
        $manager = Role::create(['name' => 'legacy-hr', 'guard_name' => 'web']);
        $manager->givePermissionTo('manage administration');
        $user = new User();
        $user->id = 'direct-system';
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => Permission::findByName('manage system', 'web')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
        (require base_path('database/migrations/2026_11_12_001700_seed_instructor_payment_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001700_seed_instructor_payment_permissions.php'))->up();

        $this->assertTrue($manager->fresh()->hasPermissionTo('view instructor payments'));
        $this->assertFalse($manager->fresh()->hasPermissionTo('process instructor payments'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('process instructor payments', 'web')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    private function authenticate(Role $role): void
    {
        $user = new User(['name' => 'Bonus test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}
