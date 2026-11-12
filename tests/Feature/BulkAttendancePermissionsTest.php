<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BulkAttendancePermissionsTest extends TestCase
{
    private const OWN = '00000000-0000-4000-8000-000000000011';
    private const OTHER = '00000000-0000-4000-8000-000000000012';
    private const OWN_GROUP = '00000000-0000-4000-8000-000000000021';
    private const MIXED_GROUP = '00000000-0000-4000-8000-000000000022';
    private const OTHER_GROUP = '00000000-0000-4000-8000-000000000023';

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
        (require base_path('database/migrations/2026_11_12_000200_seed_student_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001500_seed_bulk_attendance_permissions.php'))->up();

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
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('sname');
            $table->string('fleet_id')->nullable();
            $table->string('classroom_id')->nullable();
            $table->string('course_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('administrators', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fname');
            $table->string('sname');
            $table->softDeletes();
        });
        Schema::create('bulk_attendances', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('administrator_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->string('id')->primary();
        });
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->string('bulk_attendance_id')->nullable();
            $table->string('administrator_id')->nullable();
            $table->string('instructor_id')->nullable();
            $table->string('lesson_id')->nullable();
            $table->dateTime('attendance_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('settings', fn (Blueprint $table) => $table->id());
        DB::table('instructors')->insert([['id' => 'one'], ['id' => 'two']]);
        DB::table('fleets')->insert([['id' => 'one', 'instructor_id' => 'one'], ['id' => 'two', 'instructor_id' => 'two']]);
        DB::table('students')->insert([
            ['id' => self::OWN, 'fname' => 'Assigned', 'sname' => 'Student', 'fleet_id' => 'one', 'course_id' => 'course'],
            ['id' => self::OTHER, 'fname' => 'Private', 'sname' => 'Student', 'fleet_id' => 'two', 'course_id' => 'course'],
        ]);
        DB::table('lessons')->insert(['id' => 'lesson']);
        DB::table('bulk_attendances')->insert([
            ['id' => self::OWN_GROUP, 'description' => 'Own group', 'created_at' => now(), 'updated_at' => now()],
            ['id' => self::MIXED_GROUP, 'description' => 'Mixed group', 'created_at' => now(), 'updated_at' => now()],
            ['id' => self::OTHER_GROUP, 'description' => 'Other group', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('attendances')->insert([
            ['student_id' => self::OWN, 'bulk_attendance_id' => self::OWN_GROUP, 'lesson_id' => 'lesson'],
            ['student_id' => self::OWN, 'bulk_attendance_id' => self::MIXED_GROUP, 'lesson_id' => 'lesson'],
            ['student_id' => self::OTHER, 'bulk_attendance_id' => self::MIXED_GROUP, 'lesson_id' => 'lesson'],
            ['student_id' => self::OTHER, 'bulk_attendance_id' => self::OTHER_GROUP, 'lesson_id' => 'lesson'],
        ]);
    }

    public function test_instructor_sees_only_assigned_students_and_mixed_groups_are_read_only(): void
    {
        $this->authenticate(Role::findByName('instructor', 'web'), 'one');
        $rows = collect($this->getJson('/api/bulk-attendances')->assertOk()->json('data'))->keyBy('id');
        $this->assertEqualsCanonicalizing([self::OWN_GROUP, self::MIXED_GROUP], $rows->keys()->all());
        $this->assertSame('Assigned Student', $rows[self::MIXED_GROUP]['students']);
        $this->assertSame('', $rows[self::MIXED_GROUP]['actions']);
        $this->assertArrayNotHasKey('inaccessible_attendances_count', $rows[self::MIXED_GROUP]);
        $this->assertStringContainsString('Edit', $rows[self::OWN_GROUP]['actions']);
        $this->assertStringContainsString('openDeleteBulkAttendance', $rows[self::OWN_GROUP]['actions']);
        $this->getJson('/api/bulk-attendances?search[value]=Private')->assertOk()->assertJsonCount(0, 'data');
        $this->get('/bulk-attendances/'.self::MIXED_GROUP.'/edit')->assertNotFound();
        $this->get('/edit-bulk-attendance/'.self::OTHER_GROUP)->assertNotFound();
        $this->deleteJson('/api/bulk-attendances/'.self::MIXED_GROUP)->assertNotFound();
        $this->deleteJson('/api/bulk-attendance/'.self::OTHER_GROUP)->assertNotFound();
        $this->putJson('/api/bulk-attendances/'.self::MIXED_GROUP, [
            'students' => [['studentId' => self::OWN, 'lessonId' => 'lesson']],
        ])->assertNotFound();
        $this->assertDatabaseHas('attendances', ['student_id' => self::OTHER, 'bulk_attendance_id' => self::MIXED_GROUP]);
    }

    public function test_assigned_student_scope_applies_to_creation_and_student_lookup(): void
    {
        $this->authenticate(Role::findByName('instructor', 'web'), 'one');
        $this->getJson('/expense-student-search?student=Student')->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.id', self::OWN);
        $this->postJson('/api/store-bulk-attendance', [
            'students' => [['studentId' => self::OTHER, 'lessonId' => 'lesson']],
        ])->assertNotFound();
        $this->postJson('/api/bulk-attendances', [
            'students' => [['studentId' => self::OWN, 'lessonId' => 'lesson']],
        ])->assertOk();
        $this->assertSame(3, DB::table('attendances')->where('student_id', self::OWN)->count());
    }

    public function test_full_access_and_independent_action_permissions(): void
    {
        $role = Role::create(['name' => 'bulk-reader', 'guard_name' => 'web']);
        $role->givePermissionTo(['view bulk attendances', 'view students', 'view all students']);
        $this->authenticate($role);
        $this->get('/bulk-attendances')->assertOk();
        $rows = $this->getJson('/api/bulk-attendances')->assertOk()->json('data');
        $this->assertCount(3, $rows);
        $this->assertSame('', $rows[0]['actions']);
        $this->get('/bulk-attendances/create')->assertForbidden();
        $this->postJson('/api/bulk-attendances', [])->assertForbidden();
        $this->putJson('/api/bulk-attendances/'.self::OWN_GROUP, [])->assertForbidden();
        $this->deleteJson('/api/bulk-attendances/'.self::OWN_GROUP)->assertForbidden();
        $this->assertStringContainsString(url('/bulk-attendances'), view('layouts.partials.main_nav')->render());

        $role->givePermissionTo('delete bulk attendances');
        $this->authenticate($role);
        $this->deleteJson('/api/bulk-attendances/'.self::MIXED_GROUP)->assertOk();
        $this->assertDatabaseMissing('bulk_attendances', ['id' => self::MIXED_GROUP]);
    }

    public function test_edit_preserves_existing_attendance_and_protects_inaccessible_history(): void
    {
        DB::table('attendances')->where('bulk_attendance_id', self::OWN_GROUP)
            ->update(['attendance_date' => '2025-01-02 10:00:00']);
        $this->authenticate(Role::findByName('instructor', 'web'), 'one');
        $this->get('/bulk-attendances/'.self::OWN_GROUP.'/edit')->assertOk();
        $this->putJson('/api/bulk-attendances/'.self::OWN_GROUP, [
            'bulkAttendanceDescription' => 'Updated',
            'students' => [
                ['studentId' => self::OWN, 'lessonId' => 'lesson'],
                ['studentId' => self::OWN, 'lessonId' => 'lesson'],
            ],
        ])->assertOk();
        $this->assertDatabaseHas('attendances', [
            'student_id' => self::OWN,
            'bulk_attendance_id' => self::OWN_GROUP,
            'attendance_date' => '2025-01-02 10:00:00',
            'deleted_at' => null,
        ]);
        $this->assertSame(2, DB::table('attendances')->where('bulk_attendance_id', self::OWN_GROUP)->whereNull('deleted_at')->count());
        DB::table('attendances')->insert([
            'student_id' => self::OTHER, 'bulk_attendance_id' => self::OWN_GROUP,
            'lesson_id' => 'lesson', 'deleted_at' => now(),
        ]);
        $this->deleteJson('/api/bulk-attendances/'.self::OWN_GROUP)->assertNotFound();
        $this->assertDatabaseHas('bulk_attendances', ['id' => self::OWN_GROUP]);
    }

    public function test_migration_preserves_only_qualified_legacy_grants(): void
    {
        $role = Role::create(['name' => 'legacy-bulk', 'guard_name' => 'web']);
        $role->givePermissionTo(['access operations', 'manage operations']);
        $partial = Role::create(['name' => 'legacy-read-only', 'guard_name' => 'web']);
        $partial->givePermissionTo('access operations');
        $user = new User();
        $user->id = 'direct-bulk';
        foreach (['access operations', 'manage operations'] as $name) {
            DB::table(config('permission.table_names.model_has_permissions'))->insert([
                'permission_id' => Permission::findByName($name, 'web')->id,
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->id,
            ]);
        }
        (require base_path('database/migrations/2026_11_12_001500_seed_bulk_attendance_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001500_seed_bulk_attendance_permissions.php'))->up();
        $this->assertTrue($role->fresh()->hasPermissionTo('delete bulk attendances'));
        $this->assertTrue($partial->fresh()->hasPermissionTo('view bulk attendances'));
        $this->assertFalse($partial->fresh()->hasPermissionTo('create bulk attendances'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => Permission::findByName('edit bulk attendances', 'web')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    public function test_bulk_permissions_require_student_access_and_an_instructor_profile(): void
    {
        $role = Role::create(['name' => 'bulk-only', 'guard_name' => 'web']);
        $role->givePermissionTo(['view bulk attendances', 'create bulk attendances']);
        $this->authenticate($role, 'one');
        $this->getJson('/api/bulk-attendances')->assertForbidden();
        $this->get('/add-bulk-attendance')->assertForbidden();
        $this->getJson('/expense-student-search?student=Student')->assertForbidden();

        $role->givePermissionTo('view students');
        $this->authenticate($role);
        $this->getJson('/api/bulk-attendances')->assertForbidden();
        $this->getJson('/expense-student-search?student=Student')->assertForbidden();
    }

    private function authenticate(Role $role, ?string $instructor = null): void
    {
        $user = new User(['name' => 'Bulk attendance test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->instructor_id = $instructor;
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}
