<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InstructorAccountTest extends TestCase
{
    protected string $resource = 'instructors';
    private User $manager;
    private User $target;
    protected string $instructor = '019d05f7-11bb-7295-a756-cc2ae6b10b05';

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
            $table->uuid('administrator_id')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        (require base_path('database/migrations/2026_11_11_163500_add_is_active_to_users_table.php'))->up();
        Schema::create($this->resource, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->softDeletes();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type');
            $table->uuid('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        (require base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'manage administration']);

        DB::table($this->resource)->insert(['id' => $this->instructor]);
        $this->manager = User::withoutEvents(fn () => User::create([
            'name' => 'Manager', 'email' => 'manager@example.test', 'password' => Hash::make('Password123!'),
        ]));
        $this->manager->givePermissionTo('manage administration');
        $this->target = User::withoutEvents(function () {
            $user = new User;
            $user->name = 'Instructor';
            $user->email = 'instructor@example.test';
            $user->password = Hash::make('OldPassword123!');
            $user->{$this->profileColumn()} = $this->instructor;
            $user->email_verified_at = now();
            $user->save();
            return $user;
        });
        Route::middleware('web')->get('/account-status-test', fn () => response()->json(['ok' => true]));
    }

    private function url(): string
    {
        return route($this->resource . '.account.update', $this->instructor);
    }

    private function profileColumn(): string
    {
        return $this->resource === 'instructors' ? 'instructor_id' : 'administrator_id';
    }

    private function data(array $overrides = []): array
    {
        return array_replace([
            'name' => 'New username', 'email' => 'new@example.test', 'is_active' => true,
        ], $overrides);
    }

    public function test_account_updates_require_authentication_and_administration_permission(): void
    {
        $this->putJson($this->url(), $this->data())->assertUnauthorized();
        $this->actingAs($this->target)->putJson($this->url(), $this->data())->assertForbidden();
        $this->assertSame('Instructor', $this->target->fresh()->name);
    }

    public function test_username_email_and_password_are_updated_only_for_the_linked_user(): void
    {
        $this->target->createToken('existing');
        $this->actingAs($this->manager)->putJson($this->url(), $this->data([
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
            'instructor_id' => 'different', 'roles' => ['superAdmin'],
        ]))->assertOk()->assertJson(['message' => 'Account updated successfully.']);

        $user = $this->target->fresh();
        $this->assertSame('New username', $user->name);
        $this->assertSame('new@example.test', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
        $this->assertSame($this->instructor, $user->{$this->profileColumn()});
        $this->assertFalse($user->hasRole('superAdmin'));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame('Manager', $this->manager->fresh()->name);
    }

    public function test_blank_password_preserves_password_and_unchanged_email_verification(): void
    {
        $password = $this->target->password;
        $this->actingAs($this->manager)->putJson($this->url(), $this->data([
            'email' => $this->target->email, 'password' => '', 'password_confirmation' => '',
        ]))->assertOk();
        $this->assertSame($password, $this->target->fresh()->password);
        $this->assertNotNull($this->target->fresh()->email_verified_at);
    }

    /** @dataProvider invalidAccountData */
    public function test_invalid_account_fields_are_rejected(array $data, string $field): void
    {
        $this->actingAs($this->manager)->putJson($this->url(), $this->data($data))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame('Instructor', $this->target->fresh()->name);
    }

    public static function invalidAccountData(): array
    {
        return [
            [['name' => ''], 'name'],
            [['email' => 'invalid'], 'email'],
            [['email' => 'manager@example.test'], 'email'],
            [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
            [['password' => 'Password123!', 'password_confirmation' => 'different'], 'password'],
            [['is_active' => 'invalid'], 'is_active'],
        ];
    }

    public function test_deactivation_revokes_tokens_and_reactivation_preserves_the_profile(): void
    {
        $this->target->createToken('existing');
        $this->actingAs($this->manager)->putJson($this->url(), $this->data(['is_active' => false]))->assertOk();
        $this->assertFalse($this->target->fresh()->is_active);
        $this->assertSame(0, $this->target->tokens()->count());
        $this->assertDatabaseHas($this->resource, ['id' => $this->instructor, 'deleted_at' => null]);
        $this->actingAs($this->manager)->putJson($this->url(), $this->data(['is_active' => true]))->assertOk();
        $this->assertTrue($this->target->fresh()->is_active);
    }

    public function test_cannot_deactivate_own_account(): void
    {
        $this->target->givePermissionTo('manage administration');
        $this->actingAs($this->target)->putJson($this->url(), $this->data(['is_active' => false]))
            ->assertUnprocessable()->assertJsonValidationErrors('is_active');
        $this->assertTrue($this->target->fresh()->is_active);
    }

    public function test_missing_linked_user_returns_not_found_without_creating_an_account(): void
    {
        DB::table('users')->where('id', $this->target->id)->update(['deleted_at' => now()]);
        $this->actingAs($this->manager)->putJson($this->url(), $this->data())->assertNotFound();
    }

    public function test_disabled_users_cannot_use_existing_web_sessions_or_api_tokens(): void
    {
        $this->target->is_active = false;
        $this->target->saveQuietly();
        $this->actingAs($this->target)->getJson('/account-status-test')->assertForbidden();
        $this->assertGuest('web');

        Sanctum::actingAs($this->target, ['*']);
        $this->getJson('/api/user')->assertForbidden();
    }

    public function test_disabled_users_cannot_log_in_through_web_or_api(): void
    {
        $this->target->is_active = false;
        $this->target->saveQuietly();
        $this->postJson('/login', ['email' => $this->target->email, 'password' => 'OldPassword123!'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
        $this->postJson('/api/login', ['login' => $this->target->email, 'password' => 'OldPassword123!'])
            ->assertForbidden();
        $this->assertSame(0, $this->target->tokens()->count());
    }
}
