<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchedulePermissionsTest extends TestCase
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
        foreach (['superAdmin', 'Super-Admin', 'admin', 'instructor', 'student'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }
        (require base_path('database/migrations/2026_11_12_000000_seed_schedule_permissions.php'))->up();

        Schema::create('schedule_lessons', function ($table) {
            $table->string('id')->primary();
            $table->string('instructor_id');
            $table->string('lesson_id')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('finish_time');
            $table->text('comments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('instructors', function ($table) {
            $table->string('id')->primary();
            $table->softDeletes();
        });
        Schema::create('lessons', function ($table) {
            $table->string('id')->primary();
            $table->softDeletes();
        });
        Schema::create('students', function ($table) {
            $table->string('id')->primary();
            $table->softDeletes();
        });
        Schema::create('schedule_lesson_students', function ($table) {
            $table->string('schedule_id');
            $table->string('student_id');
            $table->string('lesson_id')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function test_default_roles_receive_only_their_schedule_permissions(): void
    {
        $admin = Role::findByName('admin');
        $instructor = Role::findByName('instructor');
        $student = Role::findByName('student');

        $this->assertTrue($admin->hasPermissionTo('view all schedules'));
        $this->assertFalse($admin->hasPermissionTo('edit schedules'));
        $this->assertTrue($instructor->hasPermissionTo('view schedules'));
        $this->assertTrue($instructor->hasPermissionTo('edit schedules'));
        $this->assertFalse($instructor->hasPermissionTo('view all schedules'));
        $this->assertFalse($student->hasPermissionTo('view schedules'));
    }

    public function test_student_cannot_read_the_general_schedule_list(): void
    {
        $this->authenticate('student');

        $this->getJson('/schedule-lessons')->assertForbidden();
        $this->getJson('/schedules')->assertForbidden();
    }

    public function test_custom_role_can_view_only_its_instructors_schedules(): void
    {
        $role = Role::create(['name' => 'schedule-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view schedules');
        $this->authenticate('schedule-reader', 'instructor-1');
        DB::table('schedule_lessons')->insert([
            [
                'id' => 'own-schedule',
                'instructor_id' => 'instructor-1',
                'start_time' => now()->addDay(),
                'finish_time' => now()->addDay()->addHour(),
            ],
            [
                'id' => 'other-schedule',
                'instructor_id' => 'instructor-2',
                'start_time' => now()->addDay(),
                'finish_time' => now()->addDay()->addHour(),
            ],
        ]);

        $response = $this->getJson('/schedule-lessons')->assertOk();
        $this->assertSame(['own-schedule'], collect($response->json())->pluck('id')->all());
    }

    public function test_instructor_cannot_edit_or_delete_another_instructors_schedule(): void
    {
        $this->authenticate('instructor', 'instructor-1');
        DB::table('schedule_lessons')->insert([
            'id' => 'other-schedule',
            'instructor_id' => 'instructor-2',
            'start_time' => now()->addDay(),
            'finish_time' => now()->addDay()->addHour(),
        ]);

        $this->putJson('/schedules/other-schedule', [])->assertNotFound();
        $this->deleteJson('/schedules/other-schedule')->assertNotFound();
        $this->assertDatabaseHas('schedule_lessons', ['id' => 'other-schedule', 'deleted_at' => null]);
    }

    public function test_instructor_can_reach_validation_for_their_own_schedule(): void
    {
        $this->authenticate('instructor', 'instructor-1');
        DB::table('schedule_lessons')->insert([
            'id' => 'own-schedule',
            'instructor_id' => 'instructor-1',
            'start_time' => now()->addDay(),
            'finish_time' => now()->addDay()->addHour(),
        ]);

        $this->putJson('/schedules/own-schedule', [])->assertUnprocessable();
    }

    public function test_admin_can_view_all_schedules(): void
    {
        $this->authenticate('admin');
        DB::table('schedule_lessons')->insert([
            [
                'id' => 'schedule-1',
                'instructor_id' => 'instructor-1',
                'start_time' => now()->addDay(),
                'finish_time' => now()->addDay()->addHour(),
            ],
            [
                'id' => 'schedule-2',
                'instructor_id' => 'instructor-2',
                'start_time' => now()->addDay(),
                'finish_time' => now()->addDay()->addHour(),
            ],
        ]);

        $response = $this->getJson('/schedule-lessons')->assertOk();
        $this->assertEqualsCanonicalizing(['schedule-1', 'schedule-2'], collect($response->json())->pluck('id')->all());
    }

    public function test_custom_role_needs_the_specific_schedule_action_permission(): void
    {
        $role = Role::create(['name' => 'schedule-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view schedules');
        $this->authenticate('schedule-reader', 'instructor-1');

        $this->postJson('/schedules', [])->assertForbidden();
        $this->putJson('/schedules/any', [])->assertForbidden();
        $this->deleteJson('/schedules/any')->assertForbidden();
    }

    public function test_admin_cannot_create_or_edit_schedules_without_action_permissions(): void
    {
        $this->authenticate('admin');

        $this->postJson('/schedules', [])->assertForbidden();
        $this->putJson('/schedules/any', [])->assertForbidden();
        $this->deleteJson('/schedules/any')->assertForbidden();
    }

    public function test_missing_instructor_profile_does_not_expose_all_schedules(): void
    {
        $this->authenticate('instructor');

        $this->getJson('/schedule-lessons')->assertForbidden();
    }

    public function test_schedule_actions_are_visible_only_with_their_permissions(): void
    {
        $role = Role::create(['name' => 'schedule-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view schedules');
        $this->authenticate('schedule-reader', 'instructor-1');
        $user = auth()->user();
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());

        $this->get('/schedules')
            ->assertOk()
            ->assertDontSee('data-bs-target="#createScheduleModal"', false)
            ->assertDontSee('Edit schedule')
            ->assertDontSee('Delete Schedule');
    }

    private function authenticate(string $role, ?string $instructorId = null): void
    {
        $user = new User(['name' => 'Schedule test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->instructor_id = $instructorId;
        $user->setRelation('roles', collect([Role::findByName($role, 'web')]));
        $this->actingAs($user);
    }
}
