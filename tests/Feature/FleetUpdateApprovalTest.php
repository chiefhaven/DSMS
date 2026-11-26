<?php

namespace Tests\Feature;

use App\Models\Fleet;
use App\Models\FleetTracker;
use App\Models\FleetUpdateSubmission;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\FleetOdometerService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FleetUpdateApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'database.connections.fleet_update_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::setDefaultConnection('fleet_update_test');

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->uuid('instructor_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('instructors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fname');
            $table->string('sname');
            $table->uuid('department_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('fleets', function (Blueprint $table) {
            $table->id();
            $table->string('car_brand_model');
            $table->string('car_registration_number');
            $table->string('instructor_id')->nullable();
            $table->unsignedBigInteger('tracker_device_id')->nullable();
            $table->unsignedInteger('odometer_km')->nullable();
            $table->date('service_date')->nullable();
            $table->date('roadworthiness_expiry')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->string('insurance_provider')->nullable();
            $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fleet_id')->nullable();
            $table->string('status');
            $table->softDeletes();
        });
        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->string('causer_id')->nullable();
            $table->string('event')->nullable();
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
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
        (require database_path('migrations/2026_11_26_000200_create_fleet_update_submissions.php'))->up();
        (require database_path('migrations/2026_11_26_000300_add_service_date_to_fleet_update_submissions.php'))->up();
        (require database_path('migrations/2026_11_26_000400_add_evidence_paths_to_fleet_update_submissions.php'))->up();
        (require database_path('migrations/2026_11_26_000500_add_odometer_reading_date_to_fleets.php'))->up();
        (require database_path('migrations/2026_11_26_000600_add_odometer_reading_date_to_fleet_update_submissions.php'))->up();

        Schema::create('fleet_trackers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mbirasync_id')->unique();
            $table->unsignedBigInteger('device_id');
            $table->timestamp('fix_time')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->boolean('valid')->default(true);
            $table->timestamps();
        });
    }

    public function test_practical_instructors_can_submit_only_their_assigned_fleet_data(): void
    {
        [$instructor, $fleet] = $this->createPracticalInstructorAndFleet('ABC 123');
        [$otherInstructor, $otherFleet] = $this->createPracticalInstructorAndFleet('XYZ 987');
        [$theoryInstructor] = $this->createPracticalInstructorAndFleet('THEORY 1', 'Theory');
        Storage::fake('local');

        $this->actingAs($instructor)
            ->get(route('my-vehicle'))
            ->assertOk()
            ->assertSee('Vehicle details')
            ->assertSee('My vehicle')
            ->assertSee('Location & distance')
            ->assertSee('Specifications & maintenance')
            ->assertSee('Insurance & service')
            ->assertDontSee('<span class="text-muted">Instructor</span>')
            ->assertDontSee('Haven Chikumba')
            ->assertDontSee('Active students')
            ->assertDontSee('Actions')
            ->assertDontSee('Edit on dashboard')
            ->assertDontSee('Edit vehicle details')
            ->assertDontSee('Current odometer (km)')
            ->assertDontSee('Current last service date')
            ->assertDontSee('Current COF due date')
            ->assertDontSee('Current insurance due date')
            ->assertDontSee('Proposed values for approval');

        $this->get(route('my-vehicle.locations', $fleet))->assertOk()->assertExactJson([]);
        $this->get(route('my-vehicle.locations', $otherFleet))->assertNotFound();

        $this->get('/my-fleet-updates')->assertRedirect(route('my-vehicle'));

        $this->post(route('my-vehicle.store', $fleet), [
            'odometer_km' => 52340,
            'service_date' => '2027-01-31',
            'roadworthiness_expiry' => '2027-02-28',
            'insurance_expiry' => '2027-03-31',
        ])->assertSessionHasErrors([
            'odometer_reading_date',
            'odometer_picture',
            'service_date_picture',
            'roadworthiness_picture',
            'insurance_picture',
        ]);
        $this->assertSame(0, FleetUpdateSubmission::count());

        $this->post(route('my-vehicle.store', $fleet), [
            'odometer_km' => 52340,
            'odometer_reading_date' => now()->subDay()->toDateString(),
            'service_date' => '2027-01-31',
            'roadworthiness_expiry' => '2027-02-28',
            'insurance_expiry' => '2027-03-31',
            'odometer_picture' => UploadedFile::fake()->image('odometer.jpg'),
            'service_date_picture' => UploadedFile::fake()->image('service.jpg'),
            'roadworthiness_picture' => UploadedFile::fake()->image('cof.jpg'),
            'insurance_picture' => UploadedFile::fake()->image('insurance.jpg'),
        ])->assertRedirect(route('my-vehicle'))
            ->assertSessionHas('message');

        $submission = FleetUpdateSubmission::query()->firstOrFail();
        $this->assertSame(FleetUpdateSubmission::STATUS_PENDING, $submission->status);
        $this->assertSame($instructor->id, $submission->submitted_by);
        $this->assertSame(10000, $fleet->fresh()->odometer_km);
        $this->assertSame('2026-10-15', $fleet->fresh()->service_date->format('Y-m-d'));
        $this->assertSame('2026-12-01', $fleet->fresh()->roadworthiness_expiry->format('Y-m-d'));
        $this->assertSame('2026-12-15', $fleet->fresh()->insurance_expiry->format('Y-m-d'));

        $this->post(route('my-vehicle.store', $fleet), [
            'odometer_km' => 53000,
            'odometer_reading_date' => now()->toDateString(),
            'service_date' => '2027-03-01',
            'roadworthiness_expiry' => '2027-03-01',
            'insurance_expiry' => '2027-04-01',
            'odometer_picture' => UploadedFile::fake()->image('pending-odometer.jpg'),
            'service_date_picture' => UploadedFile::fake()->image('pending-service.jpg'),
            'roadworthiness_picture' => UploadedFile::fake()->image('pending-cof.jpg'),
            'insurance_picture' => UploadedFile::fake()->image('pending-insurance.jpg'),
        ])->assertSessionHasErrors('fleet');
        $this->assertSame(1, FleetUpdateSubmission::count());

        $this->actingAs($otherInstructor)
            ->post(route('my-vehicle.store', $fleet), [
                'odometer_km' => 60000,
                'odometer_reading_date' => now()->toDateString(),
                'service_date' => '2027-03-01',
                'roadworthiness_expiry' => '2027-03-01',
                'insurance_expiry' => '2027-04-01',
                'odometer_picture' => UploadedFile::fake()->image('unauthorized-odometer.jpg'),
                'service_date_picture' => UploadedFile::fake()->image('unauthorized-service.jpg'),
                'roadworthiness_picture' => UploadedFile::fake()->image('unauthorized-cof.jpg'),
                'insurance_picture' => UploadedFile::fake()->image('unauthorized-insurance.jpg'),
            ])
            ->assertNotFound();

        $this->actingAs($theoryInstructor)
            ->get(route('my-vehicle'))
            ->assertNotFound();

        $this->assertSame(
            (int) $otherFleet->id,
            (int) Fleet::where('instructor_id', $otherInstructor->instructor_id)->value('id')
        );
    }

    public function test_current_odometer_adds_tracked_distance_since_reading_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-26 12:00:00'));

        try {
            [$instructor, $fleet] = $this->createPracticalInstructorAndFleet('ABC 123');
            $fleet->forceFill([
                'tracker_device_id' => 42,
                'odometer_km' => 100000,
                'odometer_reading_date' => now()->subDay()->toDateString(),
                'odometer_reading_at' => '2026-11-25 00:00:00',
            ])->save();

            foreach ([
                [1, '2026-11-24 23:00:00', 0, -0.01],
                [2, '2026-11-25 00:01:00', 0, 0],
                [3, '2026-11-26 00:01:00', 0, 0],
                [4, '2026-11-26 10:00:00', 0, 0.01],
                [5, '2026-11-27 10:00:00', 0, 0.02],
            ] as [$id, $fixTime, $latitude, $longitude]) {
                FleetTracker::create([
                    'mbirasync_id' => $id,
                    'device_id' => 42,
                    'fix_time' => $fixTime,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'valid' => true,
                ]);
            }

            $this->assertSame(
                100001,
                app(FleetOdometerService::class)->currentReading($fleet->fresh())
            );
            $this->actingAs($instructor)
                ->get(route('my-vehicle'))
                ->assertOk()
                ->assertSee('100,001');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_practical_instructor_login_marks_dashboard_modal_to_open(): void
    {
        [$instructor] = $this->createPracticalInstructorAndFleet('ABC 123');
        $instructorRole = Role::create(['name' => 'instructor', 'guard_name' => 'web']);
        $instructor->assignRole($instructorRole);
        $instructor->forceFill(['password' => Hash::make('correct-password')])->save();

        $this->post(route('login'), [
            'email' => $instructor->email,
            'password' => 'correct-password',
        ])->assertRedirect(RouteServiceProvider::HOME)
            ->assertSessionHas('open_vehicle_update_modal_after_login', true);
    }

    public function test_dashboard_edit_modal_contains_all_required_vehicle_fields(): void
    {
        [$instructor, $fleet] = $this->createPracticalInstructorAndFleet('ABC 123');
        $this->actingAs($instructor);

        $modal = view('fleet.updates.partials.edit-modal', [
            'fleet' => $fleet,
            'currentOdometerKm' => 15001,
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('Current odometer (km)', $modal);
        $this->assertStringContainsString('Last service date', $modal);
        $this->assertStringContainsString('COF due date', $modal);
        $this->assertStringContainsString('Insurance due date', $modal);
        $this->assertStringContainsString('Odometer reading date', $modal);
        $this->assertStringContainsString('value="15001"', $modal);
        $this->assertStringContainsString('name="odometer_picture"', $modal);
        $this->assertStringContainsString('name="service_date_picture"', $modal);
        $this->assertStringContainsString('name="roadworthiness_picture"', $modal);
        $this->assertStringContainsString('name="insurance_picture"', $modal);
        $this->assertStringNotContainsString('photos are optional', $modal);
        $this->assertMatchesRegularExpression('/name="odometer_picture"[^>]*required/', $modal);
        $this->assertMatchesRegularExpression('/name="service_date_picture"[^>]*required/', $modal);
        $this->assertMatchesRegularExpression('/name="roadworthiness_picture"[^>]*required/', $modal);
        $this->assertMatchesRegularExpression('/name="insurance_picture"[^>]*required/', $modal);
        $this->assertMatchesRegularExpression('/class="[^"]*js-datepicker[^"]*"[^>]*name="odometer_reading_date" type="text"/', $modal);
        $this->assertMatchesRegularExpression('/class="[^"]*js-datepicker[^"]*"[^>]*name="service_date" type="text"/', $modal);
        $this->assertMatchesRegularExpression('/class="[^"]*js-datepicker[^"]*"[^>]*name="roadworthiness_expiry" type="text"/', $modal);
        $this->assertMatchesRegularExpression('/class="[^"]*js-datepicker[^"]*"[^>]*name="insurance_expiry" type="text"/', $modal);

        $autoOpenedModal = view('fleet.updates.partials.edit-modal', [
            'fleet' => $fleet,
            'currentOdometerKm' => 15001,
            'autoOpenVehicleModal' => true,
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->render();
        $this->assertStringContainsString('data-open-on-load="true"', $autoOpenedModal);
    }

    public function test_evidence_images_are_private_and_only_available_to_reviewers(): void
    {
        [$instructor, $fleet] = $this->createPracticalInstructorAndFleet('ABC 123');
        Storage::fake('local');

        $this->actingAs($instructor)
            ->post(route('my-vehicle.store', $fleet), [
                'fleet_id' => $fleet->id,
                'odometer_km' => 52340,
                'odometer_reading_date' => now()->subDay()->toDateString(),
                'service_date' => '2027-01-31',
                'roadworthiness_expiry' => '2027-02-28',
                'insurance_expiry' => '2027-03-31',
                'odometer_picture' => UploadedFile::fake()->image('odometer.jpg'),
                'service_date_picture' => UploadedFile::fake()->image('service.png'),
                'roadworthiness_picture' => UploadedFile::fake()->image('cof.jpg'),
                'insurance_picture' => UploadedFile::fake()->image('insurance.png'),
            ])
            ->assertRedirect(route('my-vehicle'));

        $submission = FleetUpdateSubmission::query()->firstOrFail();
        $evidencePaths = [
            $submission->odometer_picture_path,
            $submission->service_date_picture_path,
            $submission->roadworthiness_picture_path,
            $submission->insurance_picture_path,
        ];
        $this->assertNotContains(null, $evidencePaths);
        foreach ($evidencePaths as $path) {
            Storage::disk('local')->assertExists($path);
        }

        $this->actingAs($instructor)
            ->post(route('my-vehicle.store', $fleet), [
                'fleet_id' => $fleet->id,
                'odometer_km' => 52341,
                'odometer_reading_date' => now()->toDateString(),
                'service_date' => '2027-02-01',
                'roadworthiness_expiry' => '2027-03-01',
                'insurance_expiry' => '2027-04-01',
                'odometer_picture' => UploadedFile::fake()->image('duplicate.jpg'),
                'service_date_picture' => UploadedFile::fake()->image('duplicate-service.jpg'),
                'roadworthiness_picture' => UploadedFile::fake()->image('duplicate-cof.jpg'),
                'insurance_picture' => UploadedFile::fake()->image('duplicate-insurance.jpg'),
            ])
            ->assertSessionHasErrors('fleet');
        $this->assertCount(4, Storage::disk('local')->allFiles('fleet-update-evidence'));

        $reviewer = $this->createUser('Fleet reviewer', 'reviewer@example.test');
        $reviewerRole = Role::create(['name' => 'fleet-data-reviewer', 'guard_name' => 'web']);
        $reviewerRole->givePermissionTo(Permission::findByName('approve fleet updates', 'web'));
        $reviewer->assignRole($reviewerRole);

        $this->actingAs($reviewer)
            ->get(route('fleet-update-approvals.index'))
            ->assertOk()
            ->assertSee('Odometer photo')
            ->assertSee('Service record photo')
            ->assertSee('COF photo')
            ->assertSee('Insurance photo');

        $evidenceUrl = route('fleet-update-approvals.evidence', [
            'submission' => $submission,
            'field' => 'odometer',
        ]);
        $this->actingAs($instructor)->get($evidenceUrl)->assertNotFound();
        $this->actingAs($reviewer)->get($evidenceUrl)->assertOk();
    }

    public function test_only_users_with_the_manual_approval_permission_can_review_updates(): void
    {
        [$instructor, $fleet] = $this->createPracticalInstructorAndFleet('ABC 123');
        [$secondInstructor, $secondFleet] = $this->createPracticalInstructorAndFleet('XYZ 987');
        $firstSubmission = $this->createSubmission($fleet, $instructor);
        $secondSubmission = $this->createSubmission($secondFleet, $secondInstructor, [
            'odometer_km' => 72000,
            'roadworthiness_expiry' => '2027-05-01',
            'insurance_expiry' => '2027-06-01',
        ]);

        $ordinaryRole = Role::create(['name' => 'fleet-data-viewer', 'guard_name' => 'web']);
        $this->assertFalse($ordinaryRole->hasPermissionTo('approve fleet updates'));

        $reviewer = $this->createUser('Fleet reviewer', 'reviewer@example.test');
        $reviewerRole = Role::create(['name' => 'fleet-data-reviewer', 'guard_name' => 'web']);
        $reviewerRole->givePermissionTo(Permission::findByName('approve fleet updates', 'web'));
        $reviewer->assignRole($reviewerRole);

        $this->actingAs($instructor)
            ->get(route('fleet-update-approvals.index'))
            ->assertNotFound();

        $this->actingAs($reviewer)
            ->get(route('fleet-update-approvals.index'))
            ->assertOk()
            ->assertSee('Pending fleet updates')
            ->assertSee('ABC 123')
            ->assertSee('XYZ 987');

        $this->post(route('fleet-update-approvals.approve', $firstSubmission))
            ->assertRedirect(route('fleet-update-approvals.index'))
            ->assertSessionHas('message');

        $this->assertSame(62340, $fleet->fresh()->odometer_km);
        $this->assertSame(now()->toDateString(), $fleet->fresh()->odometer_reading_date->format('Y-m-d'));
        $this->assertSame(
            $firstSubmission->fresh()->created_at->format('Y-m-d H:i:s'),
            $fleet->fresh()->odometer_reading_at->format('Y-m-d H:i:s')
        );
        $this->assertSame('2027-01-31', $fleet->fresh()->service_date->format('Y-m-d'));
        $this->assertSame('2027-02-28', $fleet->fresh()->roadworthiness_expiry->format('Y-m-d'));
        $this->assertSame('2027-03-31', $fleet->fresh()->insurance_expiry->format('Y-m-d'));
        $this->assertSame(FleetUpdateSubmission::STATUS_APPROVED, $firstSubmission->fresh()->status);
        $this->assertSame($reviewer->id, $firstSubmission->fresh()->reviewed_by);

        $this->post(route('fleet-update-approvals.reject', $secondSubmission), [
            'review_note' => 'Please verify the certificate dates.',
        ])->assertRedirect(route('fleet-update-approvals.index'))
            ->assertSessionHas('message');

        $this->assertSame(10000, $secondFleet->fresh()->odometer_km);
        $this->assertSame(FleetUpdateSubmission::STATUS_REJECTED, $secondSubmission->fresh()->status);
        $this->assertSame('Please verify the certificate dates.', $secondSubmission->fresh()->review_note);
    }

    private function createPracticalInstructorAndFleet(string $registration, string $department = 'Practical'): array
    {
        $departmentId = (string) Str::uuid();
        $instructorId = (string) Str::uuid();
        DB::table('departments')->insert([
            'id' => $departmentId,
            'name' => $department,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('instructors')->insert([
            'id' => $instructorId,
            'fname' => 'Practical',
            'sname' => 'Instructor',
            'department_id' => $departmentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = $this->createUser('Instructor '.$registration, strtolower(str_replace(' ', '', $registration)).'@example.test', $instructorId);
        $fleet = $this->createFleet($instructorId, $registration);

        return [$user, $fleet];
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

    private function createFleet(string $instructorId, string $registration): Fleet
    {
        $id = DB::table('fleets')->insertGetId([
            'car_brand_model' => 'Toyota Corolla',
            'car_registration_number' => $registration,
            'instructor_id' => $instructorId,
            'odometer_km' => 10000,
            'service_date' => '2026-10-15',
            'roadworthiness_expiry' => '2026-12-01',
            'insurance_expiry' => '2026-12-15',
            'insurance_provider' => 'Prime Insurance',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Fleet::findOrFail($id);
    }

    private function createSubmission(Fleet $fleet, User $submitter, array $attributes = []): FleetUpdateSubmission
    {
        return FleetUpdateSubmission::create(array_merge([
            'fleet_id' => $fleet->id,
            'submitted_by' => $submitter->id,
            'odometer_km' => 62340,
            'odometer_reading_date' => now()->toDateString(),
            'service_date' => '2027-01-31',
            'roadworthiness_expiry' => '2027-02-28',
            'insurance_expiry' => '2027-03-31',
            'status' => FleetUpdateSubmission::STATUS_PENDING,
        ], $attributes));
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
