<?php

namespace Tests\Feature;

use App\Http\Controllers\CourseController;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TeachingResourceRoutesTest extends TestCase
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
        (require base_path('database/migrations/2026_11_11_120000_seed_access_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000300_seed_course_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000400_seed_lesson_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_000500_seed_classroom_fleet_permissions.php'))->up();
    }

    public function test_only_implemented_actions_are_registered_with_authentication(): void
    {
        $resources = [
            'courses' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'],
            'lessons' => ['index', 'create', 'store', 'edit', 'update', 'destroy'],
            'classrooms' => ['index', 'store', 'update', 'destroy'],
            'fleet' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'],
        ];

        foreach ($resources as $resource => $actions) {
            foreach ($actions as $action) {
                $route = Route::getRoutes()->getByName("$resource.$action");
                $this->assertNotNull($route, "$resource.$action");
                $this->assertContains('auth', $route->gatherMiddleware());
                if ($action === 'update') {
                    $this->assertSame(['PUT', 'PATCH'], $route->methods());
                    $this->assertSame([$resource === 'fleet' ? 'fleet' : rtrim($resource, 's')], $route->parameterNames());
                }
            }
        }

        foreach (['lessons.show', 'classrooms.create', 'classrooms.show', 'classrooms.edit'] as $name) {
            $this->assertNull(Route::getRoutes()->getByName($name), $name);
        }
        $this->assertSame(CourseController::class.'@showPage', Route::getRoutes()->getByName('courses.show')->getActionName());
        $this->assertSame(CourseController::class.'@show', $this->match('/course-details/course-id')->getActionName());
        $this->assertNotContains('permission:manage administration', Route::getRoutes()->getByName('courses.show')->gatherMiddleware());
    }

    public function test_static_create_paths_are_not_intercepted_by_show_routes(): void
    {
        $this->assertSame('courses.create', $this->match('/courses/create')->getName());
        $this->assertSame('lessons.create', $this->match('/lessons/create')->getName());

        foreach (['/fleet/edit', '/classrooms/create', '/classrooms/edit', '/lessons/missing'] as $path) {
            try {
                $this->match($path);
                $this->fail("Unexpected route for $path");
            } catch (HttpException $exception) {
                $this->assertContains($exception->getStatusCode(), [404, 405]);
            }
        }
    }

    public function test_legacy_urls_still_resolve_to_existing_actions(): void
    {
        foreach ([
            ['GET', '/addcourse', 'create'],
            ['GET', '/edit-course/example', 'edit'],
            ['PUT', '/updatecourse', 'update'],
            ['DELETE', '/delete-course/example', 'destroy'],
            ['GET', '/addlesson', 'create'],
            ['POST', '/edit-lesson/example', 'edit'],
            ['PUT', '/updatelesson/example', 'update'],
            ['GET', '/classes', 'index'],
            ['POST', '/storeclassroom', 'store'],
            ['PUT', '/updateclassroom/example', 'update'],
            ['GET', '/view-fleet/1', 'show'],
            ['GET', '/editfleet/1', 'edit'],
            ['POST', '/updatefleet', 'update'],
        ] as [$method, $path, $action]) {
            $this->assertSame($action, $this->match($path, $method)->getActionMethod(), $path);
        }
    }

    public function test_guest_access_and_controller_roles_are_preserved(): void
    {
        foreach (['/courses', '/lessons', '/classrooms', '/fleet'] as $path) {
            $this->getJson($path)->assertUnauthorized();
        }

        $this->authenticate('student');
        foreach (['/courses', '/lessons', '/classrooms', '/fleet'] as $path) {
            $this->getJson($path)->assertForbidden();
        }

        $this->authenticate('instructor');
        foreach (['/courses', '/classrooms', '/fleet'] as $path) {
            $this->getJson($path)->assertForbidden();
        }
        $route = $this->match('/lessons');
        $route->gatherMiddleware();
        $action = $route->getAction();
        unset($action['controller']);
        $action['uses'] = static fn () => response()->noContent();
        $route->setAction($action);
        $this->getJson('/lessons')->assertNoContent();
    }

    public function test_course_reader_can_open_the_legacy_page_but_cannot_change_courses(): void
    {
        $role = Role::create(['name' => 'course-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view courses');
        $this->authenticate('course-reader');
        auth()->user()->setRelation('notifications', collect());
        auth()->user()->setRelation('unreadNotifications', collect());

        $this->get('/view-course/example')
            ->assertOk()
            ->assertDontSee('@click="updateCourseLessons"', false);
        $this->getJson('/addcourse')->assertForbidden();
        $this->postJson('/storecourse', [])->assertForbidden();
        $this->putJson('/updatecourse', [])->assertForbidden();
        $this->deleteJson('/delete-course/example')->assertForbidden();
        $this->putJson('/update-course-lesson', [])->assertForbidden();

        $this->authenticate('student');
        $this->getJson('/view-course/example')->assertForbidden();
    }

    public function test_course_permissions_preserve_custom_and_direct_administration_grants(): void
    {
        $role = Role::create(['name' => 'course-manager', 'guard_name' => 'web']);
        $role->givePermissionTo('manage administration');
        $systemOnly = Role::create(['name' => 'system-only', 'guard_name' => 'web']);
        $systemOnly->givePermissionTo('manage system');
        $user = new User();
        $user->id = 'direct-user';
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => \Spatie\Permission\Models\Permission::findByName('manage administration')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);

        (require base_path('database/migrations/2026_11_12_000300_seed_course_permissions.php'))->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('edit course lessons'));
        $this->assertFalse($systemOnly->fresh()->hasPermissionTo('edit course lessons'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => \Spatie\Permission\Models\Permission::findByName('delete courses')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    public function test_course_lesson_editor_cannot_change_course_details(): void
    {
        $role = Role::create(['name' => 'course-lesson-editor', 'guard_name' => 'web']);
        $role->givePermissionTo('edit course lessons');
        $this->authenticate('course-lesson-editor');

        $this->putJson('/update-course-lesson', [])->assertUnprocessable();
        $this->getJson('/lesson-search')->assertOk()->assertExactJson([]);
        $this->putJson('/updatecourse', [])->assertForbidden();
    }

    public function test_lesson_reader_cannot_change_lessons_or_see_action_controls(): void
    {
        $role = Role::create(['name' => 'lesson-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('view lessons');
        $this->createTables();
        $this->authenticate('lesson-reader');
        auth()->user()->setRelation('notifications', collect());
        auth()->user()->setRelation('unreadNotifications', collect());

        $this->get('/lessons')->assertOk()
            ->assertDontSee('@click.prevent="openAddLessonModal(\'null\')"', false)
            ->assertDontSee('@click="openAddLessonModal(lesson.id)"', false)
            ->assertDontSee('@click="confirmDeleteLesson(lesson.id)"', false);
        $this->postJson('/storelesson', [])->assertForbidden();
        $this->putJson('/updatelesson/example', [])->assertForbidden();
        $this->deleteJson('/delete-lesson/example')->assertForbidden();
        $this->getJson('/lesson-search')->assertOk()->assertExactJson([]);
        $this->authenticate('student');
        $this->getJson('/lesson-search')->assertForbidden();
    }

    public function test_lesson_migration_preserves_operational_read_access_without_granting_mutations(): void
    {
        $role = Role::create(['name' => 'operations-reader', 'guard_name' => 'web']);
        $role->givePermissionTo('manage operations');
        $user = new User();
        $user->id = 'direct-reader';
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => \Spatie\Permission\Models\Permission::findByName('manage operations')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);

        (require base_path('database/migrations/2026_11_12_000400_seed_lesson_permissions.php'))->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('view lessons'));
        $this->assertFalse($role->fresh()->hasPermissionTo('delete lessons'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => \Spatie\Permission\Models\Permission::findByName('view lessons')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    public function test_classroom_and_fleet_readers_cannot_perform_mutations(): void
    {
        $role = Role::create(['name' => 'resource-reader', 'guard_name' => 'web']);
        $role->givePermissionTo(['view classrooms', 'view fleet']);
        $this->createTables();
        $this->authenticate('resource-reader');
        auth()->user()->setRelation('notifications', collect());
        auth()->user()->setRelation('unreadNotifications', collect());

        $this->get('/classrooms')->assertOk()
            ->assertDontSee('@click.prevent="openAddclassroomModal(\'null\')"', false)
            ->assertDontSee('@click="openAddclassroomModal(classroom.id)"', false)
            ->assertDontSee('@click="confirmDeleteClassRoom(classroom.id)"', false);
        $this->postJson('/storeclassroom', [])->assertForbidden();
        $this->putJson('/updateclassroom/example', [])->assertForbidden();
        $this->deleteJson('/delete-classroom/example')->assertForbidden();
        $this->postJson('/storefleet', [])->assertForbidden();
        $this->postJson('/updatefleet', [])->assertForbidden();
        $this->deleteJson('/deletefleet/example')->assertForbidden();

        $car = new \App\Models\Fleet();
        $car->id = 'car';
        $html = view('fleet.partials.list-cell', ['car' => $car, 'field' => 'actions'])->render();
        $this->assertStringContainsString('View vehicle', $html);
        $this->assertStringNotContainsString('delete-confirm', $html);
        $this->assertStringNotContainsString(route('fleet.edit', 'car'), $html);
    }

    public function test_classroom_edit_cannot_unassign_a_vehicle_without_fleet_permission(): void
    {
        $role = Role::create(['name' => 'classroom-editor', 'guard_name' => 'web']);
        $role->givePermissionTo('edit classrooms');
        $this->createTables();
        DB::table('classrooms')->insert(['id' => 'classroom', 'name' => 'Before']);
        DB::table('fleets')->insert(['id' => 'vehicle', 'instructor_id' => 'instructor']);
        $payload = ['name' => 'After', 'instructor' => 'instructor'];
        $this->authenticate('classroom-editor');

        $this->putJson('/updateclassroom/classroom', $payload)->assertForbidden();
        $this->assertSame('Before', DB::table('classrooms')->where('id', 'classroom')->value('name'));
        $this->assertSame('instructor', DB::table('fleets')->where('id', 'vehicle')->value('instructor_id'));

        $role->givePermissionTo('edit fleet');
        $this->authenticate('classroom-editor');
        EloquentModel::withoutEvents(fn () => $this->putJson('/updateclassroom/classroom', $payload))->assertOk();
        $this->assertSame('After', DB::table('classrooms')->where('id', 'classroom')->value('name'));
        $this->assertNull(DB::table('fleets')->where('id', 'vehicle')->value('instructor_id'));
    }

    public function test_classroom_fleet_migration_preserves_existing_administration_grants(): void
    {
        $role = Role::create(['name' => 'resource-manager', 'guard_name' => 'web']);
        $role->givePermissionTo('manage administration');
        $user = new User();
        $user->id = 'direct-manager';
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => \Spatie\Permission\Models\Permission::findByName('manage administration')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);

        (require base_path('database/migrations/2026_11_12_000500_seed_classroom_fleet_permissions.php'))->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('delete classrooms'));
        $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
            'permission_id' => \Spatie\Permission\Models\Permission::findByName('edit fleet')->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);
    }

    public function test_updates_use_route_models_instead_of_conflicting_payload_ids(): void
    {
        $this->createTables();
        $this->authenticate('admin');

        $cases = [
            ['courses', 'course_id', [
                'course_name' => 'Changed', 'course_description' => 'Description',
                'course_price' => 50, 'course_code' => 'B',
            ], 'name'],
            ['lessons', 'lesson_id', [
                'lesson_name' => 'Changed', 'lesson_description' => 'Description',
                'lesson_department' => 'department', 'lesson_contents' => '',
            ], 'name'],
            ['classrooms', 'classroom_id', [
                'name' => 'Changed', 'instructor' => 'instructor',
                'description' => 'Description', 'location' => 'Location',
            ], 'name'],
            ['fleet', 'fleet_id', [
                'car_brand_model' => 'Changed', 'reg_number' => 'ABC',
                'engine_capacity_cc' => 1500,
                'licenceClass' => 'licence', 'instructor' => null,
            ], 'car_brand_model'],
        ];

        foreach ($cases as [$resource, $idField, $payload, $column]) {
            $table = $resource === 'fleet' ? 'fleets' : $resource;
            $target = $resource === 'fleet' ? '1' : '00000000-0000-4000-8000-000000000001';
            $other = $resource === 'fleet' ? '2' : '00000000-0000-4000-8000-000000000002';
            DB::table($table)->insert([
                ['id' => $target, $column => 'Original'],
                ['id' => $other, $column => 'Original'],
            ]);
            $payload[$idField] = $other;
            $response = EloquentModel::withoutEvents(fn () => $this->patch("/$resource/$target", $payload));
            $this->assertContains($response->status(), [200, 302], $resource.': '.$response->getContent());
            $this->assertSame('Changed', DB::table($table)->where('id', $target)->value($column), $resource);
            $this->assertSame('Original', DB::table($table)->where('id', $other)->value($column), $resource);

            $this->patchJson("/$resource/missing", $payload)->assertNotFound();
        }
    }

    public function test_legacy_updates_keep_payload_fallback_and_url_precedence(): void
    {
        $this->createTables();
        $this->authenticate('admin');
        DB::table('courses')->insert(['id' => 'legacy-course', 'name' => 'Original']);
        DB::table('fleets')->insert(['id' => 'legacy-fleet', 'car_brand_model' => 'Original']);
        DB::table('lessons')->insert([
            ['id' => 'target', 'name' => 'Original'],
            ['id' => 'other', 'name' => 'Original'],
        ]);
        DB::table('classrooms')->insert([
            ['id' => 'target', 'name' => 'Original'],
            ['id' => 'other', 'name' => 'Original'],
        ]);

        EloquentModel::withoutEvents(function () {
            $this->put('/updatecourse', [
                'course_id' => 'legacy-course', 'course_name' => 'Changed',
                'course_description' => 'Description', 'course_price' => 50, 'course_code' => 'B',
            ])->assertRedirect(route('courses.index'));
            $this->post('/updatefleet', [
                'fleet_id' => 'legacy-fleet', 'car_brand_model' => 'Changed',
                'engine_capacity_cc' => 1500,
                'reg_number' => 'ABC', 'licenceClass' => 'licence', 'instructor' => null,
            ])->assertRedirect(route('fleet.index'));
            $this->putJson('/updatelesson/target', [
                'lesson_id' => 'other', 'lesson_name' => 'Changed',
                'lesson_description' => 'Description', 'lesson_department' => 'department',
            ])->assertOk();
            $this->putJson('/updateclassroom/target', [
                'classroom_id' => 'other', 'name' => 'Changed', 'instructor' => 'instructor',
            ])->assertOk();
        });

        $this->assertSame('Changed', DB::table('courses')->value('name'));
        $this->assertSame('Changed', DB::table('fleets')->value('car_brand_model'));
        foreach (['lessons', 'classrooms'] as $table) {
            $this->assertSame('Changed', DB::table($table)->where('id', 'target')->value('name'));
            $this->assertSame('Original', DB::table($table)->where('id', 'other')->value('name'));
        }

    }

    public function test_course_store_validates_and_redirects_to_the_canonical_index(): void
    {
        $this->createTables();
        $this->authenticate('admin');

        EloquentModel::withoutEvents(fn () => $this->post('/courses', [
            'course_name' => 'New course', 'course_description' => 'Description',
            'course_price' => 50, 'course_code' => 'B',
        ])->assertRedirect(route('courses.index')));
        $this->assertSame('New course', DB::table('courses')->value('name'));
    }

    public function test_related_records_still_prevent_deletion(): void
    {
        $this->createTables();
        $this->authenticate('admin');
        $id = '00000000-0000-4000-8000-000000000001';
        foreach (['courses', 'lessons', 'classrooms'] as $table) {
            DB::table($table)->insert(['id' => $id, 'name' => 'Protected']);
        }
        DB::table('fleets')->insert(['id' => '1', 'car_brand_model' => 'Protected']);
        Schema::create('students', function (Blueprint $blueprint) {
            $blueprint->string('id');
            $blueprint->string('course_id');
            $blueprint->string('classroom_id');
            $blueprint->string('fleet_id');
            $blueprint->string('status');
            $blueprint->softDeletes();
        });
        Schema::create('attendances', function (Blueprint $blueprint) {
            $blueprint->string('id');
            $blueprint->string('lesson_id');
            $blueprint->softDeletes();
        });
        DB::table('students')->insert([
            'id' => 'student', 'course_id' => $id, 'classroom_id' => $id,
            'fleet_id' => '1', 'status' => 'Active',
        ]);
        DB::table('attendances')->insert(['id' => 'attendance', 'lesson_id' => $id]);

        $this->delete("/courses/$id")->assertRedirect(route('courses.index'));
        $this->delete('/fleet/1')->assertRedirect();
        $this->deleteJson("/lessons/$id")->assertStatus(400);
        $this->deleteJson("/classrooms/$id")->assertStatus(400);
        foreach (['courses', 'lessons', 'classrooms', 'fleets'] as $table) {
            $this->assertSame(1, DB::table($table)->count(), $table);
        }
    }

    public function test_course_show_uses_the_existing_view_and_serializes_uuid_for_javascript(): void
    {
        $course = new Course;
        $course->id = '00000000-0000-4000-8000-000000000001';
        $view = (new CourseController)->showPage($course);
        $this->assertSame('courses.viewcourse', $view->name());
        $this->assertSame($course->id, $view->getData()['courseId']);

        $source = file_get_contents(resource_path('views\\courses\\viewcourse.blade.php'));
        preg_match('/const courseId = .*;/', $source, $matches);
        $script = Blade::render($matches[0], $view->getData());
        $this->assertSame('const courseId = '.json_encode($course->id).';', $script);
    }

    private function match(string $path, string $method = 'GET')
    {
        return Route::getRoutes()->match(Request::create($path, $method));
    }

    private function authenticate(string $role): void
    {
        $user = new User(['name' => 'Teaching routes test']);
        $user->id = 'test-user';
        $user->setRelation('roles', collect([Role::findByName($role, 'web')]));
        $this->actingAs($user);
    }

    private function createTables(): void
    {
        foreach (['courses', 'lessons', 'classrooms', 'fleets'] as $table) {
            Schema::create($table, function (Blueprint $blueprint) {
                $blueprint->string('id')->primary();
                foreach (['name', 'class', 'short_description', 'price', 'duration', 'description', 'location',
                    'department_id', 'car_brand_model', 'car_registration_number', 'licence_class_id',
                    'instructor_id', 'car_description', 'tracker_device_id', 'engine_capacity_cc'] as $column) {
                    $blueprint->string($column)->nullable();
                }
                $blueprint->timestamps();
                $blueprint->softDeletes();
            });
        }
        foreach (['departments', 'licence_classes', 'instructors'] as $table) {
            Schema::create($table, fn (Blueprint $blueprint) => $blueprint->string('id')->primary());
        }
        Schema::table('instructors', fn (Blueprint $blueprint) => $blueprint->softDeletes());
        DB::table('departments')->insert(['id' => 'department']);
        DB::table('licence_classes')->insert(['id' => 'licence']);
        DB::table('instructors')->insert(['id' => 'instructor']);

        Schema::create('classroom_instructor', function (Blueprint $blueprint) {
            $blueprint->string('id')->nullable();
            $blueprint->string('classroom_id');
            $blueprint->string('instructor_id');
        });
        Schema::create('rich_texts', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('record_type');
            $blueprint->string('record_id');
            $blueprint->string('field');
            $blueprint->text('body')->nullable();
            $blueprint->timestamps();
        });
    }
}
