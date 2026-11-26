<?php

namespace Tests\Feature;

use App\Models\Fleet;
use App\Models\User;
use App\Notifications\FleetDueNotification;
use App\Services\FleetDueNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FleetDueNotificationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'database.connections.fleet_due_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::setDefaultConnection('fleet_due_test');

        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('instructor_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('instructors', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fname');
            $table->string('sname');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('fleets', function (Blueprint $table) {
            $table->id();
            $table->string('car_brand_model');
            $table->string('car_registration_number');
            $table->string('instructor_id')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('roadworthiness_expiry')->nullable();
            $table->unsignedInteger('odometer_km')->nullable();
            $table->unsignedInteger('next_service_km')->nullable();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        $this->createPermissionTables();
        (require database_path('migrations/2026_11_26_000100_create_fleet_due_notification_logs_and_permission.php'))->up();
        Notification::fake();
    }

    public function test_daily_due_alerts_go_to_the_assigned_instructor_and_permitted_users_once_per_day(): void
    {
        $this->travelTo(Carbon::parse('2026-11-26 07:00:00', 'Africa/Harare'));

        $instructorId = (string) Str::uuid();
        DB::table('instructors')->insert([
            'id' => $instructorId,
            'fname' => 'Alex',
            'sname' => 'Moyo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $instructor = $this->createUser('Assigned instructor', 'instructor@example.test', $instructorId);
        $permittedAdmin = $this->createUser('Permitted admin', 'admin@example.test');
        $unpermittedAdmin = $this->createUser('Other admin', 'other@example.test');
        $permittedAdmin->givePermissionTo(Permission::findByName('receive fleet due notifications', 'web'));
        $role = Role::create(['name' => 'manual-assignment-only', 'guard_name' => 'web']);
        $this->assertFalse($role->hasPermissionTo('receive fleet due notifications'));

        $fleet = $this->createFleet($instructorId, [
            'insurance_expiry' => '2026-12-06',
            'roadworthiness_expiry' => '2026-11-25',
            'odometer_km' => 50000,
            'next_service_km' => 50000,
        ]);
        $this->createFleet(null, [
            'insurance_expiry' => '2026-12-18',
            'roadworthiness_expiry' => null,
            'odometer_km' => 10000,
            'next_service_km' => 15000,
        ]);

        $this->artisan('fleet:notify-dues')->assertExitCode(0);

        Notification::assertSentToTimes($instructor, FleetDueNotification::class, 6);
        Notification::assertSentToTimes($permittedAdmin, FleetDueNotification::class, 6);
        Notification::assertNotSentTo($unpermittedAdmin, FleetDueNotification::class);
        Notification::assertSentTo($instructor, FleetDueNotification::class, function ($notification, $channels) use ($instructor) {
            $data = $notification->toDatabase($instructor);

            return $channels === ['database']
                && $data['due_type'] === 'insurance_expiry'
                && str_contains($data['due_item'], 'Insurance due by');
        });
        Notification::assertSentTo($instructor, FleetDueNotification::class, function ($notification, $channels) {
            return $channels === ['mail'];
        });
        $this->assertSame(6, DB::table('fleet_due_notification_logs')->count());
        $this->assertSame(6, DB::table('fleet_due_notification_logs')->where('fleet_id', $fleet->id)->count());

        $this->artisan('fleet:notify-dues')->assertExitCode(0);
        Notification::assertSentToTimes($instructor, FleetDueNotification::class, 6);
        Notification::assertSentToTimes($permittedAdmin, FleetDueNotification::class, 6);
        $this->assertSame(6, DB::table('fleet_due_notification_logs')->count());

        $this->travelTo(Carbon::parse('2026-11-27 07:00:00', 'Africa/Harare'));
        $this->artisan('fleet:notify-dues')->assertExitCode(0);
        Notification::assertSentToTimes($instructor, FleetDueNotification::class, 12);
        Notification::assertSentToTimes($permittedAdmin, FleetDueNotification::class, 12);
        $this->assertSame(12, DB::table('fleet_due_notification_logs')->count());
    }

    public function test_email_failure_is_logged_without_undoing_the_in_app_notification(): void
    {
        $recipient = $this->createUser('Permitted admin', 'admin@example.test');
        $fleet = $this->createFleet(null, [
            'insurance_expiry' => '2026-11-26',
            'roadworthiness_expiry' => null,
            'odometer_km' => null,
            'next_service_km' => null,
        ]);
        $exception = new \RuntimeException('SMTP unavailable');

        Notification::shouldReceive('sendNow')
            ->once()
            ->with($recipient, \Mockery::type(FleetDueNotification::class), ['database']);
        Notification::shouldReceive('sendNow')
            ->once()
            ->with($recipient, \Mockery::type(FleetDueNotification::class), ['mail'])
            ->andThrow($exception);
        Log::shouldReceive('error')
            ->once()
            ->with('Fleet due email notification failed.', \Mockery::on(function ($context) use ($recipient, $fleet, $exception) {
                return $context['recipient_id'] === $recipient->id
                    && $context['fleet_id'] === $fleet->id
                    && $context['exception'] === $exception;
            }));

        $sent = app(FleetDueNotificationService::class)->notify(
            $fleet,
            $recipient,
            'insurance_expiry',
            'Insurance due by 26 Nov 2026',
            '2026-11-26'
        );

        $this->assertTrue($sent);
        $this->assertSame(1, DB::table('fleet_due_notification_logs')->count());
    }

    private function createUser(string $name, string $email, ?string $instructorId = null): User
    {
        $id = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'password' => 'test-password',
            'instructor_id' => $instructorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function createFleet(?string $instructorId, array $attributes): Fleet
    {
        $id = DB::table('fleets')->insertGetId(array_merge([
            'car_brand_model' => 'Toyota Corolla',
            'car_registration_number' => 'ABC 123',
            'instructor_id' => $instructorId,
            'insurance_expiry' => null,
            'roadworthiness_expiry' => null,
            'odometer_km' => null,
            'next_service_km' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return Fleet::findOrFail($id);
    }

    private function createPermissionTables(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->string('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->string('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }
}
