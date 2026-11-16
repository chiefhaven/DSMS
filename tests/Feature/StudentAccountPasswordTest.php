<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class StudentAccountPasswordTest extends TestCase
{
    private const TARGET_STUDENT = '00000000-0000-4000-8000-000000000022';
    private const OTHER_STUDENT = '00000000-0000-4000-8000-000000000023';
    private const INSTRUCTOR = '00000000-0000-4000-8000-000000000024';

    private User $manager;
    private User $portalUser;

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

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->uuid('instructor_id')->nullable();
            $table->uuid('student_id')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->boolean('is_active')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fleet_id')->nullable();
            $table->string('classroom_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('instructors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('fleets', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('instructor_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('classrooms', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('classroom_instructor', function (Blueprint $table) {
            $table->uuid('instructor_id');
            $table->string('classroom_id');
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        (require base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['manage student accounts', 'view students'] as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }

        DB::table('instructors')->insert(['id' => self::INSTRUCTOR]);
        DB::table('fleets')->insert([
            ['id' => 'assigned-fleet', 'instructor_id' => self::INSTRUCTOR],
            ['id' => 'other-fleet', 'instructor_id' => '00000000-0000-4000-8000-000000000025'],
        ]);
        DB::table('students')->insert([
            ['id' => self::TARGET_STUDENT, 'fleet_id' => 'assigned-fleet', 'created_at' => now(), 'updated_at' => now()],
            ['id' => self::OTHER_STUDENT, 'fleet_id' => 'other-fleet', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->manager = $this->makeUser('Manager', 'manager@example.test');
        $this->manager->instructor_id = self::INSTRUCTOR;
        $this->manager->save();
        $this->manager->givePermissionTo(['manage student accounts', 'view students']);

        $this->portalUser = $this->makeUser('Student', 'student@example.test');
        $this->portalUser->student_id = self::TARGET_STUDENT;
        $this->portalUser->save();

        Route::middleware('web')->get('/password-gate-test', fn () => response('Accessible'));
        Route::middleware(['api', 'auth:sanctum'])
            ->get('/api/password-gate-test', fn () => response()->json(['ok' => true]));
    }

    private function makeUser(string $name, string $email): User
    {
        return User::withoutEvents(function () use ($name, $email) {
            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = Hash::make('InitialPassword123!');
            $user->save();

            return $user;
        });
    }

    public function test_reset_link_is_sent_to_the_linked_student_account(): void
    {
        Notification::fake();

        $this->actingAs($this->manager)
            ->post(route('students.account.password-reset-link', self::TARGET_STUDENT))
            ->assertOk()
            ->assertJson(['message' => 'A password reset link was sent to the student account email.']);

        Notification::assertSentTo($this->portalUser, ResetPassword::class);
    }

    public function test_reset_link_mail_failure_returns_a_safe_json_error(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andThrow(new TransportException('private SMTP diagnostic'));

        $this->actingAs($this->manager)
            ->post(route('students.account.password-reset-link', self::TARGET_STUDENT))
            ->assertStatus(502)
            ->assertJson([
                'message' => 'The reset email could not be delivered. Verify the mail service settings and try again.',
            ])
            ->assertDontSee('private SMTP diagnostic');
    }

    public function test_email_password_reset_clears_the_temporary_password_requirement(): void
    {
        $this->portalUser->forceFill(['must_change_password' => true])->save();
        $token = Password::broker()->createToken($this->portalUser);

        $this->post(route('session.password.update'), [
            'token' => $token,
            'email' => $this->portalUser->email,
            'password' => 'ResetPassword123!',
            'password_confirmation' => 'ResetPassword123!',
        ])->assertRedirect(route('login'));

        $this->assertFalse($this->portalUser->fresh()->must_change_password);
        $this->assertTrue(Hash::check('ResetPassword123!', $this->portalUser->fresh()->password));
    }

    public function test_temporary_password_is_hashed_shown_once_and_requires_a_change(): void
    {
        $this->portalUser->createToken('existing-session');

        $response = $this->actingAs($this->manager)
            ->post(route('students.account.temporary-password', self::TARGET_STUDENT))
            ->assertOk()
            ->assertJsonStructure(['message', 'temporary_password'])
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache');

        $temporaryPassword = $response->json('temporary_password');
        $this->assertIsString($temporaryPassword);
        $this->assertSame(32, strlen($temporaryPassword));
        $this->assertFalse(session()->has('temporary_password'));
        $this->assertTrue(Hash::check($temporaryPassword, $this->portalUser->fresh()->password));
        $this->assertTrue($this->portalUser->fresh()->must_change_password);
        $this->assertSame(0, $this->portalUser->fresh()->tokens()->count());

        $student = Student::findOrFail(self::TARGET_STUDENT);
        $this->view('students.partials.studentAccount', ['student' => $student])
            ->assertSee('Send password reset link')
            ->assertSee('Generate temporary password')
            ->assertSee('submitStudentAccountAction($event, \'temporary-password\')', false)
            ->assertDontSee($temporaryPassword);
    }

    public function test_account_password_actions_require_permission_and_preserve_assignment_scope(): void
    {
        $unauthorized = $this->makeUser('Unauthorized', 'unauthorized@example.test');
        $this->actingAs($unauthorized)
            ->post(route('students.account.temporary-password', self::TARGET_STUDENT))
            ->assertForbidden();
        $this->view('students.partials.studentAccount', [
            'student' => Student::findOrFail(self::TARGET_STUDENT),
        ])->assertDontSee('Generate temporary password')
            ->assertDontSee('Send password reset link');

        $this->actingAs($this->manager)
            ->post(route('students.account.temporary-password', self::OTHER_STUDENT))
            ->assertNotFound();

        $this->assertFalse($this->portalUser->fresh()->must_change_password);
    }

    public function test_temporary_password_gates_web_access_until_the_student_changes_it(): void
    {
        $this->portalUser->forceFill(['must_change_password' => true])->save();

        $this->actingAs($this->portalUser)
            ->get('/password-gate-test')
            ->assertRedirect(route('students.password.force-change'));

        $this->get(route('students.password.force-change'))
            ->assertOk()
            ->assertSee('Set a new password');

        $this->put(route('students.password.force-change.update'), [
            'password' => 'ChangedPassword123!',
            'password_confirmation' => 'ChangedPassword123!',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($this->portalUser->fresh()->must_change_password);
        $this->assertTrue(Hash::check('ChangedPassword123!', $this->portalUser->fresh()->password));
    }

    public function test_temporary_password_also_blocks_authenticated_api_access(): void
    {
        $this->portalUser->forceFill(['must_change_password' => true])->save();
        $token = $this->portalUser->createToken('student-api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/password-gate-test')
            ->assertStatus(423)
            ->assertJson(['message' => 'A password change is required before continuing.']);
    }
}
