<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class AdministratorAccountPasswordTest extends TestCase
{
    private const ADMINISTRATOR_ID = 31;

    private User $manager;
    private User $accountUser;
    private Administrator $administrator;

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
            $table->unsignedBigInteger('administrator_id')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->boolean('is_active')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('administrators', function (Blueprint $table) {
            $table->id();
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
        Permission::create(['name' => 'manage administrator accounts', 'guard_name' => 'web']);

        $this->manager = $this->makeUser('Manager', 'manager@example.test');
        $this->manager->givePermissionTo('manage administrator accounts');

        $this->administrator = new Administrator();
        $this->administrator->id = self::ADMINISTRATOR_ID;
        $this->administrator->fname = 'Admin';
        $this->administrator->sname = 'Target';
        $this->administrator->save();

        $this->accountUser = $this->makeUser('Administrator', 'administrator@example.test');
        $this->accountUser->administrator_id = self::ADMINISTRATOR_ID;
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

    public function test_reset_link_is_sent_to_the_linked_administrator_account(): void
    {
        Notification::fake();

        $this->actingAs($this->manager)
            ->post(route('administrators.account.password-reset-link', self::ADMINISTRATOR_ID))
            ->assertOk()
            ->assertJson(['message' => 'A password reset link was sent to the administrator account email.']);

        Notification::assertSentTo($this->accountUser, ResetPassword::class);
    }

    public function test_reset_email_transport_failure_returns_a_safe_json_error(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andThrow(new TransportException('private SMTP diagnostic'));

        $this->actingAs($this->manager)
            ->post(route('administrators.account.password-reset-link', self::ADMINISTRATOR_ID))
            ->assertStatus(502)
            ->assertJson([
                'message' => 'The reset email could not be delivered. Verify the mail service settings and try again.',
            ])
            ->assertDontSee('private SMTP diagnostic');
    }

    public function test_temporary_password_is_one_time_response_and_requires_a_password_change(): void
    {
        $this->accountUser->createToken('existing-session');

        $response = $this->actingAs($this->manager)
            ->post(route('administrators.account.temporary-password', self::ADMINISTRATOR_ID))
            ->assertOk()
            ->assertJsonStructure(['message', 'temporary_password'])
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache');

        $temporaryPassword = $response->json('temporary_password');
        $this->assertIsString($temporaryPassword);
        $this->assertSame(32, strlen($temporaryPassword));
        $this->assertTrue(Hash::check($temporaryPassword, $this->accountUser->fresh()->password));
        $this->assertTrue($this->accountUser->fresh()->must_change_password);
        $this->assertSame(0, $this->accountUser->fresh()->tokens()->count());
    }

    public function test_password_actions_require_permission_and_a_linked_account(): void
    {
        $unauthorized = $this->makeUser('Unauthorized', 'unauthorized@example.test');
        $this->actingAs($unauthorized)
            ->post(route('administrators.account.temporary-password', self::ADMINISTRATOR_ID))
            ->assertForbidden();

        DB::table('administrators')->insert(['id' => 32]);

        $this->actingAs($this->manager)
            ->post(route('administrators.account.temporary-password', 32))
            ->assertUnprocessable()
            ->assertJson(['message' => 'No administrator account is linked.']);

        $this->assertFalse($this->accountUser->fresh()->must_change_password);
    }
}
