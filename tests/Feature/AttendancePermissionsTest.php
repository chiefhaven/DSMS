<?php

namespace Tests\Feature;

use App\Http\Controllers\AttendanceController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendancePermissionsTest extends TestCase
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
        (require base_path('database/migrations/2026_11_12_000000_seed_schedule_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000200_seed_student_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001600_seed_attendance_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001900_seed_instructor_permissions.php'))->up();

        Schema::create('instructors', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('department_id')->nullable();
            $table->string('fname')->nullable();
            $table->string('sname')->nullable();
            $table->softDeletes();
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
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
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('sname');
            $table->string('fleet_id')->nullable();
            $table->string('classroom_id')->nullable();
            $table->string('course_id')->nullable();
            $table->string('status')->nullable();
            $table->string('phone')->nullable();
            $table->string('trn')->nullable();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('student_id')->nullable();
            $table->string('email')->nullable();
            $table->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->decimal('invoice_balance')->default(0);
            $table->softDeletes();
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
        });
        Schema::create('rich_texts', function (Blueprint $table) {
            $table->id();
            $table->string('record_type');
            $table->string('record_id');
            $table->string('field');
            $table->text('body')->nullable();
            $table->timestamps();
        });
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->string('lesson_id');
            $table->string('instructor_id')->nullable();
            $table->string('administrator_id')->nullable();
            $table->string('bulk_attendance_id')->nullable();
            $table->dateTime('attendance_date');
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('departments')->insert(['id' => 'practical', 'name' => 'practical']);
        DB::table('instructors')->insert([
            ['id' => 'one', 'department_id' => 'practical', 'fname' => 'First'],
            ['id' => 'two', 'department_id' => 'practical', 'fname' => 'Second'],
        ]);
        DB::table('fleets')->insert([['id' => 'one', 'instructor_id' => 'one'], ['id' => 'two', 'instructor_id' => 'two']]);
        DB::table('students')->insert([
            ['id' => 'own', 'fname' => 'Assigned', 'sname' => 'Student', 'fleet_id' => 'one', 'status' => 'Inprogress'],
            ['id' => 'other', 'fname' => 'Private', 'sname' => 'Student', 'fleet_id' => 'two', 'status' => 'Inprogress'],
        ]);
        DB::table('lessons')->insert(['id' => 'lesson', 'name' => 'First']);
        DB::table('attendances')->insert([
            ['id' => 1, 'student_id' => 'own', 'lesson_id' => 'lesson', 'instructor_id' => 'one', 'bulk_attendance_id' => null, 'attendance_date' => '2026-01-01'],
            ['id' => 2, 'student_id' => 'other', 'lesson_id' => 'lesson', 'instructor_id' => 'two', 'bulk_attendance_id' => null, 'attendance_date' => '2026-01-01'],
            ['id' => 3, 'student_id' => 'own', 'lesson_id' => 'lesson', 'instructor_id' => 'one', 'bulk_attendance_id' => 'mixed', 'attendance_date' => '2026-01-01'],
        ]);
    }

    public function test_instructor_list_is_limited_to_own_records_and_hides_mutations(): void
    {
        $this->authenticate(Role::findByName('instructor', 'web'), 'one');
        $rows = $this->getJson('/api/fetch-attendances')->assertOk()->json('data');
        $this->assertEqualsCanonicalizing([1, 3], collect($rows)->pluck('id')->all());
        $this->assertSame('', $rows[0]['actions']);
        $this->deleteJson('/attendances/1')->assertForbidden();
        $this->getJson('/attendanceSummary/two?period=today')->assertForbidden();
        $this->getJson('/attendanceSummary/one?period=invalid')->assertUnprocessable();
    }

    public function test_edit_delete_and_report_permissions_respect_scope_and_bulk_boundary(): void
    {
        $role = Role::create(['name' => 'attendance-editor', 'guard_name' => 'web']);
        $role->givePermissionTo(['view students', 'edit attendances', 'delete attendances']);
        $this->authenticate($role, 'one');
        $payload = ['student' => 'Assigned Student', 'lesson' => 'First', 'date' => '2026-02-01'];

        $this->putJson('/attendances/2', $payload)->assertNotFound();
        $this->putJson('/attendances/3', $payload)->assertForbidden();
        $this->deleteJson('/deleteattendance/2')->assertNotFound();
        $this->deleteJson('/attendances/3')->assertForbidden();
        $this->put('/attendances/1', $payload)->assertRedirect('/attendances');
        $this->assertDatabaseHas('attendances', ['id' => 1, 'attendance_date' => '2026-02-01 00:00:00']);
        $this->delete('/deleteattendance/1')->assertRedirect();
        $this->assertSoftDeleted('attendances', ['id' => 1]);
        $this->getJson('/attendanceSummary/one?period=today')->assertForbidden();
        $this->assertDatabaseHas('attendances', ['id' => 3, 'deleted_at' => null]);
    }

    public function test_all_attendance_access_requires_the_specific_action(): void
    {
        $role = Role::create(['name' => 'attendance-reader', 'guard_name' => 'web']);
        $role->givePermissionTo(['view attendances', 'view all attendances']);
        $this->authenticate($role);
        $this->getJson('/api/fetch-attendances')->assertOk()->assertJsonCount(3, 'data');
        $this->get('/attendances')->assertOk();
        $this->postJson('/storeattendance', [])->assertForbidden();
        $this->deleteJson('/attendances/2')->assertForbidden();
        $this->getJson('/attendanceSummary/two?period=today')->assertForbidden();
        $this->assertStringContainsString('All attendances', view('layouts.partials.main_nav')->render());
        $this->assertStringNotContainsString('Download Report', view('attendances.partials.reportSummary')->with('instructor', (object) ['id' => 'two'])->render());
    }

    public function test_qr_dispatch_checks_record_permission_and_preserves_guest_redirect(): void
    {
        $role = Role::findByName('instructor', 'web');
        $role->revokePermissionTo('record attendances');
        $this->authenticate($role, 'one');
        $this->getJson('/e8704ed2-d90e-41ca-9143-ceb2bb517cc7/own')->assertForbidden();
        $this->postJson('/addattendance/own')->assertForbidden();

        $role->givePermissionTo('record attendances');
        $this->authenticate($role, 'one');
        $controller = Mockery::mock(AttendanceController::class)->makePartial();
        $controller->shouldReceive('create')->once()->andReturn(response('QR allowed'));
        app()->instance(AttendanceController::class, $controller);
        $this->get('/e8704ed2-d90e-41ca-9143-ceb2bb517cc7/own')->assertOk()->assertSee('QR allowed');

        auth()->logout();
        $this->get('/e8704ed2-d90e-41ca-9143-ceb2bb517cc7/own')->assertRedirect(route('docsQrCode', ['token' => 'own']));
    }

    public function test_student_self_service_remains_available_and_scoped(): void
    {
        $this->authenticate(Role::findByName('student', 'web'));
        auth()->user()->student_id = 'own';
        foreach (['/api/attendance', '/api/attendances'] as $url) {
            $response = $this->getJson($url)->assertOk()->assertJsonCount(2);
            $this->assertEqualsCanonicalizing([1, 3], collect($response->json())->pluck('id')->all());
        }
        $this->getJson('/api/fetch-attendances')->assertForbidden();
    }

    public function test_scheduling_lookup_respects_student_assignment(): void
    {
        $role = Role::create(['name' => 'schedule-creator', 'guard_name' => 'web']);
        Permission::findOrCreate('create schedules', 'web');
        $role->givePermissionTo(['view students', 'create schedules']);
        $this->authenticate($role, 'one');
        $this->getJson('/attendance-student-search?search=Student')->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.id', 'own');
        $this->authenticate($role);
        $this->getJson('/attendance-student-search?search=Student')->assertForbidden();
    }

    public function test_instructor_profile_attendance_endpoints_require_view_and_scope(): void
    {
        $role = Role::create(['name' => 'profile-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view instructors');
        $this->authenticate($role, 'one');
        $headers = ['X-Requested-With' => 'XMLHttpRequest'];
        $this->getJson('/instructors/one?section=attendances', $headers)->assertForbidden();
        $this->getJson('/viewinstructor/two?section=chart&month=invalid', $headers)->assertForbidden();

        $role->givePermissionTo('view attendances');
        $this->authenticate($role, 'one');
        $this->getJson('/instructors/two?section=attendances', $headers)->assertForbidden();
        $this->getJson('/viewinstructor/one?section=chart&month=invalid', $headers)->assertForbidden();
        $role->givePermissionTo('view schedules');
        $this->authenticate($role, 'one');
        $this->getJson('/viewinstructor/one?section=chart&month=invalid', $headers)->assertUnprocessable();
        $role->givePermissionTo('view all attendances');
        $this->authenticate($role, 'one');
        $this->getJson('/viewinstructor/two?section=chart&month=invalid', $headers)->assertForbidden();
        $role->givePermissionTo('view all schedules');
        $this->authenticate($role, 'one');
        $this->getJson('/viewinstructor/two?section=chart&month=invalid', $headers)->assertUnprocessable();
    }

    public function test_migration_preserves_legacy_grants_and_does_not_grant_view_all_to_custom_roles(): void
    {
        $role = Role::create(['name' => 'legacy-operations', 'guard_name' => 'web']);
        $role->givePermissionTo('access operations');
        $user = new User();
        $user->id = 'direct-system';
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => Permission::findByName('manage system', 'web')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
        (require base_path('database/migrations/2026_11_12_001600_seed_attendance_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001600_seed_attendance_permissions.php'))->up();
        $this->assertTrue($role->fresh()->hasPermissionTo('view attendances'));
        $this->assertTrue($role->fresh()->hasPermissionTo('download attendance reports'));
        $this->assertFalse($role->fresh()->hasPermissionTo('view all attendances'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('delete attendances', 'web')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('view all attendances', 'web')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    private function authenticate(Role $role, ?string $instructor = null): void
    {
        $user = new User(['name' => 'Attendance test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->instructor_id = $instructor;
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}
