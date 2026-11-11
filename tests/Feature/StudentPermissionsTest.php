<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentPermissionsTest extends TestCase
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
        foreach (['superAdmin', 'Super-Admin', 'admin', 'financeAdmin', 'instructor', 'student'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }
        (require base_path('database/migrations/2026_11_12_000200_seed_student_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000400_seed_lesson_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000500_seed_classroom_fleet_permissions.php'))->up();

        Schema::create('instructors', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->softDeletes();
        });
        Schema::create('fleets', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('instructor_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('classrooms', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->softDeletes();
        });
        Schema::create('classroom_instructor', function (Blueprint $table) {
            $table->string('classroom_id');
            $table->string('instructor_id');
        });
        Schema::create('students', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fname')->nullable();
            $table->string('mname')->nullable();
            $table->string('sname')->nullable();
            $table->string('status')->nullable();
            $table->string('phone')->nullable();
            $table->string('trn')->nullable();
            $table->string('fleet_id')->nullable();
            $table->string('classroom_id')->nullable();
            $table->string('course_id')->nullable();
            $table->string('trainingLevel_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        foreach (['users', 'invoices', 'attendances'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->string('id')->primary();
                $table->string('student_id')->nullable();
                if ($name === 'attendances') {
                    $table->string('lesson_id')->nullable();
                }
                if ($name === 'users') {
                    $table->string('email')->nullable();
                }
                $table->softDeletes();
            });
        }
        Schema::create('training_levels', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('trainingLevel_id')->nullable();
            $table->softDeletes();
        });
        DB::table('training_levels')->insert([['id' => 'theory', 'name' => 'theory'], ['id' => 'practical', 'name' => 'practical']]);
        DB::table('instructors')->insert([['id' => 'one'], ['id' => 'two']]);
        DB::table('fleets')->insert([['id' => 'fleet-one', 'instructor_id' => 'one'], ['id' => 'fleet-two', 'instructor_id' => 'two']]);
        DB::table('classrooms')->insert(['id' => 'class-one']);
        DB::table('classroom_instructor')->insert(['classroom_id' => 'class-one', 'instructor_id' => 'one']);
        DB::table('students')->insert([
            ['id' => 'car', 'fname' => 'Car', 'status' => 'In progress', 'fleet_id' => 'fleet-one', 'classroom_id' => null, 'created_at' => now()],
            ['id' => 'class', 'fname' => 'Class', 'status' => 'In progress', 'fleet_id' => null, 'classroom_id' => 'class-one', 'created_at' => now()],
            ['id' => 'other', 'fname' => 'Other', 'status' => 'In progress', 'fleet_id' => 'fleet-two', 'classroom_id' => null, 'created_at' => now()],
        ]);
    }

    public function test_default_grants_and_seeder_are_idempotent(): void
    {
        (require base_path('database/migrations/2026_11_12_000200_seed_student_permissions.php'))->up();
        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('create students'));
        $this->assertFalse(Role::findByName('admin')->hasPermissionTo('delete students'));
        $this->assertTrue(Role::findByName('financeAdmin')->hasPermissionTo('view all students'));
        $this->assertFalse(Role::findByName('instructor')->hasPermissionTo('view all students'));
        $this->assertFalse(Role::findByName('student')->hasPermissionTo('view students'));
    }

    public function test_instructor_web_and_api_lists_only_include_assigned_students(): void
    {
        $this->authenticate('instructor', 'one');

        foreach (['/fetch-students', '/api/students'] as $url) {
            $response = $this->getJson($url)->assertOk();
            $this->assertArrayNotHasKey('error', $response->json());
            $this->assertEqualsCanonicalizing(['car', 'class'], collect($response->json('data'))->pluck('id')->all());
        }
        $this->getJson('/viewstudent/other')->assertNotFound();
        $this->getJson('/students/other')->assertNotFound();
        $this->getJson('/api/instructor-students?instructorId=two')->assertForbidden();
    }

    public function test_missing_profile_and_student_role_cannot_access_student_lists(): void
    {
        $this->authenticate('instructor');
        $this->getJson('/fetch-students')->assertForbidden();
        $this->getJson('/api/students')->assertForbidden();

        $this->authenticate('student');
        $this->getJson('/fetch-students')->assertForbidden();
        $this->getJson('/api/students')->assertForbidden();
    }

    public function test_finance_administrator_can_read_all_students(): void
    {
        $this->authenticate('financeAdmin');
        $response = $this->getJson('/api/students')->assertOk();
        $this->assertArrayNotHasKey('error', $response->json());
        $this->assertEqualsCanonicalizing(['car', 'class', 'other'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_student_search_renders_for_an_all_students_reader(): void
    {
        $this->authenticate('financeAdmin');
        auth()->user()->setRelation('notifications', collect());
        auth()->user()->setRelation('unreadNotifications', collect());

        $this->get('/search-student?search=Car')->assertOk();
    }

    public function test_custom_role_needs_each_specific_action_permission(): void
    {
        $role = Role::create(['name' => 'reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view students');
        $this->authenticate('reader', 'one');

        $this->postJson('/storestudent', [])->assertForbidden();
        $this->postJson('/assignCar', [])->assertForbidden();
        $this->postJson('/updateStudentStatus/car', [])->assertForbidden();
        $this->deleteJson('/student-delete/car')->assertForbidden();
        $this->postJson('/lesson-report/car')->assertForbidden();
        $this->postJson('/trafic-card-reference-letter/car')->assertForbidden();
    }

    public function test_student_list_hides_actions_without_their_permissions(): void
    {
        $role = Role::create(['name' => 'reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view students');
        $this->authenticate('reader', 'one');
        auth()->user()->setRelation('notifications', collect());
        auth()->user()->setRelation('unreadNotifications', collect());

        $this->view('students.students', ['fleet' => collect(), 'errors' => new ViewErrorBag()])
            ->assertDontSee('Add student')
            ->assertDontSee('Students report')
            ->assertDontSee('id="change-status"', false);
    }

    public function test_lesson_endpoints_enforce_student_self_access_and_instructor_assignments(): void
    {
        $this->authenticate('instructor', 'one');
        $this->getJson('/student-lessons/car')->assertOk()->assertExactJson([]);
        $this->getJson('/student-lessons/other')->assertNotFound();
        $this->getJson('/api/student-lesson-attendances-count?studentId=car&lessonID=lesson')->assertOk()->assertJson(['count' => 0]);
        $this->getJson('/api/student-lesson-attendances-count?studentId=other&lessonID=lesson')->assertNotFound();

        $this->authenticate('student');
        auth()->user()->student_id = 'car';
        $this->getJson('/api/student-lesson-attendances-count?studentId=car&lessonID=lesson')->assertOk()->assertJson(['count' => 0]);
        $this->getJson('/api/student-lesson-attendances-count?studentId=other&lessonID=lesson')->assertForbidden();
        $this->getJson('/api/student-lessons?studentId=other')->assertForbidden();
        $this->getJson('/api/student-lessons?studentId=car')->assertNotFound();
    }

    public function test_classroom_and_fleet_api_details_are_limited_to_assignments(): void
    {
        $this->authenticate('instructor', 'one');
        $this->getJson('/api/classroomDetails/class-one')->assertOk();
        $this->getJson('/api/fleetDetails/fleet-one')->assertOk();
        $this->getJson('/api/fleetDetails/fleet-two')->assertForbidden();

        $this->authenticate('student');
        auth()->user()->student_id = 'car';
        $this->getJson('/api/fleetDetails/fleet-one')->assertOk();
        $this->getJson('/api/fleetDetails/fleet-two')->assertForbidden();
        $this->getJson('/api/classroomDetails/class-one')->assertForbidden();
        auth()->user()->student_id = 'class';
        $this->getJson('/api/classroomDetails/class-one')->assertOk();
    }

    public function test_custom_manage_system_grants_are_preserved_on_migration(): void
    {
        $source = Permission::create(['name' => 'manage system', 'guard_name' => 'web']);
        $access = Permission::create(['name' => 'access operations', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'system-manager', 'guard_name' => 'web']);
        $role->givePermissionTo([$source, $access]);
        $systemOnly = Role::create(['name' => 'system-only', 'guard_name' => 'web']);
        $systemOnly->givePermissionTo($source);
        $user = new User();
        $user->id = 'direct-user';
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => $source->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => $access->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);

        (require base_path('database/migrations/2026_11_12_000200_seed_student_permissions.php'))->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('delete students'));
        $this->assertFalse($systemOnly->fresh()->hasPermissionTo('delete students'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('view all students')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    public function test_report_permission_does_not_bypass_assignment_scope(): void
    {
        $role = Role::create(['name' => 'reporter', 'guard_name' => 'web']);
        $role->givePermissionTo(['view students', 'generate student reports', 'generate student letters']);
        $this->authenticate('reporter', 'one');

        $this->postJson('/lesson-report/other')->assertNotFound();
        $this->postJson('/trafic-card-reference-letter/other')->assertNotFound();
    }

    public function test_action_permission_does_not_bypass_assignment_scope(): void
    {
        $role = Role::create(['name' => 'student-editor', 'guard_name' => 'web']);
        $role->givePermissionTo(['view students', 'edit students', 'change student status', 'assign students']);
        $this->authenticate('student-editor', 'one');

        $this->getJson('/edit-student/other')->assertNotFound();
        $this->postJson('/updateStudentStatus/other', ['status' => 'Finished'])->assertNotFound();
        $this->postJson('/assignCar', ['student' => 'other'])->assertNotFound();
    }

    private function authenticate(string $role, ?string $instructorId = null): void
    {
        $user = new User(['name' => 'Student test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->instructor_id = $instructorId;
        $user->setRelation('roles', collect([Role::findByName($role, 'web')]));
        $this->actingAs($user);
    }
}
