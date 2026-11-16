<?php

namespace Tests\Feature;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InstructorAccountPasswordTest extends TestCase
{
    private const INSTRUCTOR_ID = '3f1f9c38-8a7e-4a79-9a39-7a1a1e2b4c41';
    private const UNLINKED_INSTRUCTOR_ID = '9d2f4b6a-1c3e-4f5d-8b7a-2e4c6a8d0f42';

    private User $manager;
    private User $accountUser;

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
            $table->string('instructor_id')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->boolean('is_active')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('instructors', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fname')->nullable();
            $table->string('sname')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
        Permission::create(['name' => 'manage instructor accounts', 'guard_name' => 'web']);

        $this->manager = $this->makeUser('Manager', 'manager@example.test');
        $this->manager->givePermissionTo('manage instructor accounts');

        $instructor = new Instructor();
        $instructor->id = self::INSTRUCTOR_ID;
        $instructor->fname = 'Instructor';
        $instructor->sname = 'Target';
        $instructor->save();

        $this->accountUser = $this->makeUser('Instructor', 'instructor@example.test');
        $this->accountUser->instructor_id = self::INSTRUCTOR_ID;
        $this->accountUser->email_verified_at = now();
        $this->accountUser->save();
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

    public function test_reset_link_is_sent_to_the_linked_instructor_account(): void
    {
        Notification::fake();

        $this->actingAs($this->manager)
            ->post(route('instructors.account.password-reset-link', self::INSTRUCTOR_ID))
            ->assertOk()
            ->assertJson(['message' => 'A password reset link was sent to the instructor account email.']);

        Notification::assertSentTo($this->accountUser, ResetPassword::class);
    }

    public function test_temporary_password_is_one_time_response_and_requires_a_password_change(): void
    {
        $this->accountUser->createToken('existing-session');

        $response = $this->actingAs($this->manager)
            ->post(route('instructors.account.temporary-password', self::INSTRUCTOR_ID))
            ->assertOk()
            ->assertJsonStructure(['message', 'temporary_password'])
            ->assertHeader('Cache-Control', 'no-store, private');

        $temporaryPassword = $response->json('temporary_password');
        $this->assertIsString($temporaryPassword);
        $this->assertTrue(Hash::check($temporaryPassword, $this->accountUser->fresh()->password));
        $this->assertTrue($this->accountUser->fresh()->must_change_password);
        $this->assertSame(0, $this->accountUser->fresh()->tokens()->count());
    }

    public function test_password_actions_require_permission_and_a_linked_account(): void
    {
        $unauthorized = $this->makeUser('Unauthorized', 'unauthorized@example.test');
        $this->actingAs($unauthorized)
            ->post(route('instructors.account.temporary-password', self::INSTRUCTOR_ID))
            ->assertForbidden();

        DB::table('instructors')->insert(['id' => self::UNLINKED_INSTRUCTOR_ID]);

        $this->actingAs($this->manager)
            ->post(route('instructors.account.temporary-password', self::UNLINKED_INSTRUCTOR_ID))
            ->assertUnprocessable()
            ->assertJson(['message' => 'No instructor account is linked.']);

        $this->assertFalse($this->accountUser->fresh()->must_change_password);
    }
}
