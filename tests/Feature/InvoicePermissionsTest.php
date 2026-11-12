<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Administrator;
use App\Models\Student;
use App\Notifications\StudentAccountSetup;
use App\Services\StudentAccountSetupDelivery;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoicePermissionsTest extends TestCase
{
    private const STUDENT = '00000000-0000-4000-8000-000000000011';

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
        (require base_path('database/migrations/2026_11_12_001100_seed_invoice_permissions.php'))->up();
        Schema::create('settings', fn (Blueprint $table) => $table->id());
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->softDeletes();
        });
        foreach (['classrooms', 'fleets'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->string('id')->primary();
                $table->softDeletes();
            });
        }
        Schema::create('students', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('sname');
            $table->string('course_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->string('student_id');
            $table->string('course_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('invoice_total');
            $table->decimal('invoice_discount')->default(0);
            $table->decimal('invoice_amount_paid')->default(0);
            $table->decimal('invoice_balance');
            $table->date('invoice_payment_due_date')->nullable();
            $table->date('date_created')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('courses')->insert(['id' => 'course-1', 'name' => 'Driving']);
        DB::table('students')->insert([
            'id' => self::STUDENT, 'fname' => 'Test', 'sname' => 'Student', 'course_id' => 'course-1',
        ]);
        DB::table('invoices')->insert([
            'id' => 1, 'invoice_number' => 'INV-1', 'student_id' => self::STUDENT,
            'course_id' => 'course-1', 'invoice_total' => 100, 'invoice_amount_paid' => 40,
            'invoice_balance' => 60, 'date_created' => '2026-01-01',
        ]);
        DB::table('payments')->insert(['id' => 1, 'student_id' => self::STUDENT]);
    }

    public function test_invoice_reader_can_use_web_and_api_without_other_actions(): void
    {
        $role = Role::create(['name' => 'invoice-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view invoices');
        $this->authenticate($role);

        $this->get('/invoices')->assertOk();
        $rows = $this->getJson('/api/fetchInvoices')->assertOk()->json('data');
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('K60.00', $rows[0]['balance']);
        $this->assertStringContainsString('View', $rows[0]['actions']);
        $this->assertStringNotContainsString('Edit', $rows[0]['actions']);
        $this->assertStringNotContainsString('Print Invoice', $rows[0]['actions']);
        $this->assertStringNotContainsString('Delete', $rows[0]['actions']);
        $this->getJson('/api/invoices')->assertOk()->assertJsonCount(1);
        $this->getJson('/api/invoicesHome')->assertOk()->assertJsonCount(1);
        $this->getJson('/api/invoice-view/INV-1')->assertOk()->assertJsonPath('invoice_number', 'INV-1');
        $this->get('/view-invoice/999')->assertNotFound();
        $this->get('/invoice-pdf/1')->assertForbidden();
        $this->get('/addinvoice/'.self::STUDENT)->assertForbidden();
        $this->postJson('/store-invoice', [])->assertForbidden();
        $this->putJson('/invoices/1', [])->assertForbidden();
        $this->postJson('/invoice-update', [])->assertForbidden();
        $this->deleteJson('/invoice-delete/1')->assertForbidden();
        $html = view('layouts.partials.main_nav')->render();
        $this->assertStringContainsString('Invoices', $html);
        $this->assertStringNotContainsString('All students', $html);
    }

    public function test_create_and_download_permissions_are_independent(): void
    {
        $role = Role::create(['name' => 'invoice-clerk', 'guard_name' => 'web']);
        $role->givePermissionTo(['create invoices', 'download invoices']);
        $this->authenticate($role);

        $this->get('/invoices/create?student='.self::STUDENT)->assertOk();
        $this->get('/addinvoice/'.self::STUDENT)->assertOk();
        $this->postJson('/store-invoice', [])->assertUnprocessable();
        $this->get('/invoice-pdf/999')->assertNotFound();
        $this->getJson('/api/invoices')->assertForbidden();
        $this->get('/edit-invoice/1')->assertForbidden();
        $this->deleteJson('/invoices/1')->assertForbidden();
    }

    public function test_first_enrollment_sends_a_working_setup_link_without_resetting_existing_password(): void
    {
        $this->prepareEnrollment();
        $data = ['student' => self::STUDENT, 'course' => 'Driving', 'discount' => 0, 'paid_amount' => 0];
        $this->post('/store-invoice', $data)->assertRedirect(route('students.show', self::STUDENT));

        $user = User::where('student_id', self::STUDENT)->firstOrFail();
        $this->assertTrue(Hash::check('existing-password', $user->password));
        $this->assertNotNull(Student::findOrFail(self::STUDENT)->student_number);
        $url = null;
        Notification::assertSentTo($user, StudentAccountSetup::class, function ($notification) use ($user, &$url) {
            $mail = $notification->toMail($user);
            $url = $mail->actionUrl;
            return str_contains(implode(' ', $mail->introLines), $user->email);
        });
        $this->assertStringStartsWith('https://school.example/reset-password/', $url);
        Http::assertSent(function ($request) use ($url, $user) {
            return $request->url() === 'https://sms.example/sms/send'
                && $request['recipient'] === '265991234567'
                && str_contains($request['message'], $user->email)
                && str_contains($request['message'], $url);
        });
        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->assertTrue(Password::broker()->tokenExists($user, $token));

        DB::table('invoices')->where('student_id', self::STUDENT)->delete();
        $this->post('/store-invoice', $data)->assertRedirect();
        Notification::assertSentToTimes($user, StudentAccountSetup::class, 1);
        Http::assertSentCount(1);
        $this->assertTrue(Password::broker()->tokenExists($user, $token));

        auth()->logout();
        $this->get($url)->assertOk()->assertSee('student@example.test');
        $this->post('/password/reset', [
            'token' => $token, 'email' => $user->email,
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertRedirect();
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertFalse(Password::broker()->tokenExists($user, $token));

        auth()->logout();
        $this->get(route('password.request'))->assertOk();
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->get($notification->toMail($user)->actionUrl)->assertOk();
            return true;
        });
    }

    public function test_enrollment_warns_when_text_fails_but_still_sends_email(): void
    {
        $this->prepareEnrollment(500);
        $response = $this->post('/store-invoice', [
            'student' => self::STUDENT, 'course' => 'Driving', 'discount' => 0, 'paid_amount' => 0,
        ])->assertRedirect();
        $alert = json_decode($response->getSession()->get('alert.config'), true);
        $this->assertSame('warning', $alert['icon']);
        $this->assertStringContainsString('could not be sent by text', $alert['text']);
        $student = Student::findOrFail(self::STUDENT);
        Notification::assertSentTo($student->user, StudentAccountSetup::class);
        $this->assertTrue(Hash::check('existing-password', $student->user->password));
    }

    public function test_setup_delivery_does_not_log_setup_links_when_mail_uses_log_transport(): void
    {
        $this->prepareEnrollment();
        config(['mail.default' => 'log']);
        $student = Student::findOrFail(self::STUDENT);
        $this->assertSame(['email'], app(StudentAccountSetupDelivery::class)->send($student));
        Notification::assertNotSentTo($student->user, StudentAccountSetup::class);
        Http::assertSentCount(1);
    }

    private function prepareEnrollment(int $smsStatus = 200): void
    {
        config([
            'app.url' => 'https://school.example',
            'services.smsApi.url' => 'https://sms.example',
            'services.smsApi.token' => 'test-token',
            'services.smsApi.from' => 'school',
            'mail.default' => 'smtp',
        ]);
        Notification::fake();
        Http::fake(['*' => Http::response([], $smsStatus)]);
        Schema::table('courses', fn (Blueprint $table) => $table->decimal('price')->default(0));
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_number')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('course_price')->default(0);
            $table->string('invoice_payment_method')->nullable();
            $table->string('invoice_terms')->nullable();
        });
        Schema::create('invoice_settings', fn (Blueprint $table) => $table->id());
        Schema::create('student_number_sequences', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedInteger('last_number');
        });
        Schema::create('administrators', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
        });
        (require base_path('database/migrations/2014_10_12_100000_create_password_reset_tokens_table.php'))->up();
        DB::table('invoices')->delete();
        DB::table('students')->where('id', self::STUDENT)->update(['phone' => '0991234567', 'course_id' => null]);
        DB::table('users')->insert([
            'id' => 1, 'student_id' => self::STUDENT, 'name' => 'Test Student',
            'email' => 'student@example.test', 'password' => Hash::make('existing-password'),
        ]);
        $role = Role::create(['name' => 'enrollment-clerk', 'guard_name' => 'web']);
        $role->givePermissionTo('create invoices');
        $this->authenticate($role);
        $administrator = new Administrator;
        $administrator->fname = 'Test';
        $administrator->sname = 'Admin';
        auth()->user()->setRelation('Administrator', $administrator);
    }

    public function test_dashboard_hides_invoice_details_without_view_access(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('schedule_lessons', function (Blueprint $table) {
            $table->id();
            $table->timestamp('start_time')->nullable();
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->decimal('approved_amount')->nullable();
            $table->timestamp('date_approved')->nullable();
        });
        Schema::create('expense_payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount')->nullable();
            $table->timestamps();
        });
        $role = Role::create(['name' => 'dashboard-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('access management');
        $this->authenticate($role);

        $this->getJson('/api/dashboardSummary?filter=alltime')->assertOk()->assertJsonCount(0, 'recentInvoices');
        $html = view('dashboard_partials.admin', ['students' => collect()])->render();
        $this->assertStringNotContainsString('id="invoicesBlock"', $html);
        $this->assertStringNotContainsString("axios.get('/api/invoices')", $html);

        $role->givePermissionTo('view invoices');
        $this->authenticate($role);
        $this->getJson('/api/dashboardSummary?filter=alltime')->assertOk()->assertJsonCount(1, 'recentInvoices');
        $this->assertStringContainsString('id="invoicesBlock"', view('dashboard_partials.admin', ['students' => collect()])->render());
    }

    public function test_deletion_rolls_back_payments_if_unenrollment_fails(): void
    {
        $role = Role::create(['name' => 'invoice-deleter', 'guard_name' => 'web']);
        $role->givePermissionTo('delete invoices');
        $this->authenticate($role);

        $this->deleteJson('/invoices/1')->assertStatus(500);
        $this->assertDatabaseHas('payments', ['id' => 1, 'deleted_at' => null]);
        $this->assertDatabaseHas('invoices', ['id' => 1, 'deleted_at' => null]);
        $this->assertDatabaseHas('students', ['id' => self::STUDENT, 'course_id' => 'course-1']);

        Schema::table('students', fn (Blueprint $table) => $table->string('fleet_id')->nullable());
        $this->delete('/invoice-delete/1')->assertRedirect();
        $this->assertSoftDeleted('payments', ['id' => 1]);
        $this->assertSoftDeleted('invoices', ['id' => 1]);
        $this->assertDatabaseHas('students', ['id' => self::STUDENT, 'course_id' => null, 'fleet_id' => null]);
    }

    public function test_migration_preserves_legacy_role_and_direct_grants_idempotently(): void
    {
        $role = Role::create(['name' => 'legacy-finance', 'guard_name' => 'web']);
        $role->givePermissionTo('manage finance');
        $user = new User();
        $user->id = 'legacy-direct';
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => Permission::findByName('manage system')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
        (require base_path('database/migrations/2026_11_12_001100_seed_invoice_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001100_seed_invoice_permissions.php'))->up();
        $this->assertTrue($role->fresh()->hasPermissionTo('create invoices'));
        $this->assertFalse($role->fresh()->hasPermissionTo('delete invoices'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('delete invoices')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    private function authenticate(Role $role): void
    {
        $user = new User(['name' => 'Invoice test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}
