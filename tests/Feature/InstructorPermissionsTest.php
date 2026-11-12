<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstructorPermissionsTest extends TestCase
{
    private const INSTRUCTOR = 'f2638868-b1d0-4c92-b71d-59a1f01e6e22';

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
        foreach (['superAdmin', 'Super-Admin', 'admin', 'instructor', 'student'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }
        (require base_path('database/migrations/2026_11_11_120000_seed_access_permissions.php'))->up();
        foreach ([
            'view students', 'view all students', 'view invoices', 'view attendances', 'view all attendances',
            'view schedules', 'view all schedules', 'view fleet', 'view lessons', 'view classrooms',
            'edit fleet', 'create classrooms', 'edit classrooms',
        ] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        (require base_path('database/migrations/2026_11_12_001900_seed_instructor_permissions.php'))->up();
        Schema::create('instructors', function (Blueprint $table) {
            $table->string('id')->primary();
            foreach (['fname', 'sname', 'gender', 'phone', 'address', 'status', 'date_of_birth', 'district_id', 'department_id', 'signature'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('instructor_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
        });
        Schema::create('districts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
        });
        Schema::create('fleets', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('instructor_id')->nullable();
            $table->string('car_registration_number')->nullable();
            $table->string('car_brand_model')->nullable();
            $table->timestamps();
        });
        Schema::create('classrooms', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->nullable();
            $table->string('location')->nullable();
            $table->softDeletes();
        });
        Schema::create('classroom_instructor', function (Blueprint $table) {
            $table->string('classroom_id');
            $table->string('instructor_id');
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->nullable();
            $table->softDeletes();
        });
        Schema::create('instructor_lesson', function (Blueprint $table) {
            $table->string('instructor_id');
            $table->string('lesson_id');
        });
        Schema::create('students', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('status')->nullable();
            $table->string('fleet_id')->nullable();
            $table->string('classroom_id')->nullable();
            $table->softDeletes();
        });
        DB::table('departments')->insert([['id' => 'one', 'name' => 'Practical'], ['id' => 'two', 'name' => 'Theory']]);
        DB::table('districts')->insert(['id' => 'district', 'name' => 'Central']);
        DB::table('instructors')->insert([
            'id' => self::INSTRUCTOR, 'fname' => 'Example', 'sname' => 'Instructor',
            'department_id' => 'one', 'status' => 'active',
        ]);
        DB::table('users')->insert([
            'id' => 'teacher-user', 'instructor_id' => self::INSTRUCTOR, 'name' => 'Teacher',
            'email' => 'teacher@example.test', 'password' => 'original',
        ]);
    }

    public function test_viewer_can_read_profiles_but_not_actions_or_unrelated_student_data(): void
    {
        $role = Role::create(['name' => 'instructor-viewer', 'guard_name' => 'web']);
        $role->givePermissionTo('view instructors');
        $this->authenticate($role);
        $this->get('/instructors')->assertOk()->assertDontSee('Add Instructor');
        $row = $this->getJson('/instructors', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data.0');
        $this->assertEqualsCanonicalizing(
            ['actions', 'instructor', 'department_name', 'assignment', 'email', 'phone', 'status_badge'],
            array_keys($row)
        );
        $this->assertStringContainsString('View Profile', $row['actions']);
        $this->assertStringNotContainsString('Edit Profile', $row['actions']);
        $this->assertStringNotContainsString('Delete', $row['actions']);
        $this->get('/viewinstructor/'.self::INSTRUCTOR)->assertOk()
            ->assertSee('Example Instructor')->assertDontSee('Edit Profile')
            ->assertDontSee('id="account-tab"', false)->assertDontSee('Assigned students')
            ->assertDontSee('Active students')->assertDontSee('Finished students')
            ->assertDontSee('id="attendancesChart"', false)->assertDontSee('const account =', false);
        $this->getJson('/instructor-data/'.self::INSTRUCTOR)->assertForbidden();
        $this->getJson('/instructors-json')->assertForbidden();
        $this->getJson('/instructors/create')->assertForbidden();
        $this->postJson('/storeinstructor')->assertForbidden();
        $this->getJson('/editinstructor/'.self::INSTRUCTOR)->assertForbidden();
        $this->postJson('/updateinstructor', [])->assertForbidden();
        $this->deleteJson('/deleteinstructor/'.self::INSTRUCTOR)->assertForbidden();
        $this->putJson('/instructors/'.self::INSTRUCTOR.'/account', [])->assertForbidden();
        $this->assertStringContainsString('Instructors', view('layouts.partials.main_nav')->render());
    }

    public function test_classroom_assignment_options_are_minimal_and_available_without_instructor_view(): void
    {
        DB::table('instructors')->insert([
            'id' => 'theory-instructor', 'fname' => 'Theory', 'sname' => 'Teacher',
            'department_id' => 'two', 'status' => 'active', 'phone' => 'private',
        ]);
        $role = Role::create(['name' => 'classroom-editor', 'guard_name' => 'web']);
        $role->givePermissionTo('edit classrooms');
        $this->authenticate($role);
        $this->getJson('/instructor-options?status=active')->assertOk()->assertExactJson([
            ['id' => 'theory-instructor', 'fname' => 'Theory', 'sname' => 'Teacher'],
        ]);
        $this->getJson('/instructors-json')->assertForbidden();
        $this->getJson('/instructors')->assertForbidden();
    }

    public function test_detailed_instructor_list_requires_its_related_permissions(): void
    {
        DB::table('instructors')->where('id', self::INSTRUCTOR)->update(['department_id' => 'two']);
        $role = Role::create(['name' => 'detailed-instructor-viewer', 'guard_name' => 'web']);
        $role->givePermissionTo(['view instructors', 'view fleet', 'view lessons']);
        $this->authenticate($role);
        $this->getJson('/instructors-json')->assertForbidden();
        $role->givePermissionTo('manage instructor accounts');
        $this->authenticate($role);
        $this->getJson('/instructors-json')->assertOk()
            ->assertJsonPath('0.id', self::INSTRUCTOR)
            ->assertJsonPath('0.user.email', 'teacher@example.test')
            ->assertJsonStructure([['lesson', 'fleet']]);
    }

    public function test_action_permissions_are_independent_on_canonical_and_legacy_routes(): void
    {
        $role = Role::create(['name' => 'instructor-editor', 'guard_name' => 'web']);
        $role->givePermissionTo('edit instructors');
        $this->authenticate($role);
        $this->get('/instructors/'.self::INSTRUCTOR.'/edit')->assertOk()->assertSee('readonly');
        $this->postJson('/instructors', [])->assertForbidden();
        $this->putJson('/instructors/'.self::INSTRUCTOR, [])->assertUnprocessable();
        $this->postJson('/updateinstructor', ['instructor_id' => self::INSTRUCTOR])->assertUnprocessable();
        $this->deleteJson('/instructors/'.self::INSTRUCTOR)->assertForbidden();
        $this->getJson('/instructors')->assertForbidden();
        $this->assertStringNotContainsString('Instructors', view('layouts.partials.main_nav')->render());

        $role->givePermissionTo('create instructors');
        $this->authenticate($role);
        $this->postJson('/storeinstructor', [])->assertUnprocessable();
        $this->postJson('/instructors', [])->assertUnprocessable();
    }

    public function test_profile_edit_cannot_change_account_or_remove_assignments_without_their_permissions(): void
    {
        $role = Role::create(['name' => 'profile-editor', 'guard_name' => 'web']);
        $role->givePermissionTo('edit instructors');
        $this->authenticate($role);
        $data = [
            'first_name' => 'Updated', 'sir_name' => 'Instructor', 'email' => 'teacher@example.test',
            'address' => 'Address', 'gender' => 'male', 'date_of_birth' => '1990-01-01',
            'district' => 'Central', 'phone' => '123', 'department' => 'one', 'status' => 'active',
        ];
        $this->putJson('/instructors/'.self::INSTRUCTOR, array_merge($data, ['email' => 'changed@example.test']))->assertForbidden();
        $this->putJson('/instructors/'.self::INSTRUCTOR, array_merge($data, ['password' => 'new-password']))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => 'teacher-user', 'email' => 'teacher@example.test', 'password' => 'original']);

        DB::table('fleets')->insert(['id' => 'fleet', 'instructor_id' => self::INSTRUCTOR]);
        DB::table('classrooms')->insert(['id' => 'class']);
        DB::table('classroom_instructor')->insert(['classroom_id' => 'class', 'instructor_id' => self::INSTRUCTOR]);
        $data['department'] = 'two';
        $this->putJson('/instructors/'.self::INSTRUCTOR, $data)->assertForbidden();
        $role->givePermissionTo('edit fleet');
        $this->authenticate($role);
        $this->putJson('/instructors/'.self::INSTRUCTOR, $data)->assertForbidden();
        $this->assertDatabaseHas('fleets', ['id' => 'fleet', 'instructor_id' => self::INSTRUCTOR]);
        $this->assertDatabaseHas('classroom_instructor', ['classroom_id' => 'class', 'instructor_id' => self::INSTRUCTOR]);
        $role->givePermissionTo('edit classrooms');
        $this->authenticate($role);
        $this->put('/instructors/'.self::INSTRUCTOR, $data)->assertRedirect(route('instructors.index'));
        $this->assertDatabaseHas('instructors', ['id' => self::INSTRUCTOR, 'department_id' => 'two', 'fname' => 'Updated']);
        $this->assertDatabaseHas('fleets', ['id' => 'fleet', 'instructor_id' => null]);
        $this->assertDatabaseMissing('classroom_instructor', ['classroom_id' => 'class', 'instructor_id' => self::INSTRUCTOR]);
    }

    public function test_legacy_full_data_requires_all_related_view_permissions(): void
    {
        $role = Role::create(['name' => 'full-data-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view instructors');
        $this->authenticate($role);
        $this->getJson('/instructor-data/missing')->assertForbidden();
        $role->givePermissionTo([
            'view students', 'view all students', 'view invoices', 'view attendances', 'view all attendances',
            'view schedules', 'view all schedules', 'view fleet', 'view classrooms',
        ]);
        $this->authenticate($role);
        $this->getJson('/instructor-data/missing')->assertNotFound();
    }

    public function test_create_and_delete_keep_instructor_and_account_together(): void
    {
        $role = Role::create(['name' => 'instructor-creator', 'guard_name' => 'web']);
        $role->givePermissionTo('create instructors');
        $this->authenticate($role);
        $this->post('/instructors', [
            'first_name' => 'New', 'sir_name' => 'Teacher', 'email' => 'new@example.test',
            'address' => 'Address', 'gender' => 'male', 'date_of_birth' => '1990-01-01',
            'district' => 'Central', 'phone' => '123', 'department' => 'one', 'status' => 'active',
            'username' => 'new-teacher', 'password' => 'new-password',
        ])->assertRedirect(route('instructors.index'));
        $id = DB::table('instructors')->where('fname', 'New')->value('id');
        $this->assertNotNull($id);
        $this->assertDatabaseHas('users', ['instructor_id' => $id, 'email' => 'new@example.test']);
        $role->givePermissionTo('delete instructors');
        $this->authenticate($role);
        $this->delete('/instructors/'.$id)->assertRedirect(route('instructors.index'));
        $this->assertSoftDeleted('instructors', ['id' => $id]);
        $this->assertSoftDeleted('users', ['instructor_id' => $id]);
    }

    public function test_legacy_role_and_direct_grants_are_preserved_idempotently(): void
    {
        $role = Role::create(['name' => 'legacy-staff', 'guard_name' => 'web']);
        $role->givePermissionTo('manage administration');
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => Permission::findByName('manage administration', 'web')->id,
            'model_type' => (new User())->getMorphClass(), 'model_id' => 'direct-manager',
        ]);
        $migration = require base_path('database/migrations/2026_11_12_001900_seed_instructor_permissions.php');
        $migration->up();
        $migration->up();
        foreach (['view instructors', 'create instructors', 'edit instructors', 'delete instructors', 'manage instructor accounts'] as $name) {
            $this->assertTrue($role->fresh()->hasPermissionTo($name));
            $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
                'permission_id' => Permission::findByName($name, 'web')->id,
                'model_type' => (new User())->getMorphClass(), 'model_id' => 'direct-manager',
            ]);
        }
    }

    private function authenticate(Role $role): void
    {
        $user = new User(['name' => 'Instructor permissions test']);
        $user->id = 'manager';
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }
}
