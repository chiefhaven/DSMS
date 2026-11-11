<?php

namespace Tests\Feature;

use App\Http\Controllers\InstructorController;
use App\Http\Controllers\StudentController;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class InstructorProfileTest extends TestCase
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

        foreach (['instructors', 'departments', 'users', 'fleets', 'classrooms', 'students', 'lessons', 'attendances', 'schedule_lessons'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->string('id')->primary();
                $table->timestamps();
                $table->softDeletes();
                if (in_array($name, ['instructors', 'students'])) {
                    $table->string('fname')->nullable();
                    $table->string('mname')->nullable();
                    $table->string('sname')->nullable();
                    $table->string('status')->nullable();
                    $table->string('department_id')->nullable();
                }
                if (in_array($name, ['users', 'fleets', 'attendances', 'schedule_lessons'])) {
                    $table->string('instructor_id')->nullable();
                }
                if ($name === 'students') {
                    $table->string('fleet_id')->nullable();
                    $table->string('classroom_id')->nullable();
                }
                if (in_array($name, ['departments', 'classrooms', 'lessons'])) {
                    $table->string('name')->nullable();
                }
                if ($name === 'attendances') {
                    $table->string('student_id')->nullable();
                    $table->string('lesson_id')->nullable();
                    $table->dateTime('attendance_date')->nullable();
                }
                if ($name === 'schedule_lessons') {
                    $table->dateTime('start_time')->nullable();
                }
            });
        }
        Schema::create('classroom_instructor', function (Blueprint $table) {
            $table->string('classroom_id');
            $table->string('instructor_id');
        });

        DB::table('instructors')->insert([['id' => 'instructor'], ['id' => 'other'], ['id' => 'unassigned']]);
        DB::table('fleets')->insert(['id' => 'fleet', 'instructor_id' => 'instructor']);
        DB::table('classrooms')->insert(['id' => 'classroom']);
        DB::table('classroom_instructor')->insert(['classroom_id' => 'classroom', 'instructor_id' => 'instructor']);
        DB::table('students')->insert([
            ['id' => 'both', 'fname' => '<script>alert(1)</script>', 'mname' => 'Middle', 'sname' => 'Alpha', 'fleet_id' => 'fleet', 'classroom_id' => 'classroom', 'status' => 'In progress'],
            ['id' => 'classroom', 'fname' => 'Class', 'mname' => null, 'sname' => 'Beta', 'fleet_id' => null, 'classroom_id' => 'classroom', 'status' => 'Finished'],
            ['id' => 'outside', 'fname' => 'Outside', 'mname' => null, 'sname' => 'Gamma', 'fleet_id' => null, 'classroom_id' => null, 'status' => 'In progress'],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fetch(array $params): array
    {
        $request = Request::create('/instructors/instructor', 'GET', $params, [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
        app()->instance('request', $request);
        app()->forgetInstance('datatables.request');

        return app(InstructorController::class)->show('instructor', $request)->getData(true);
    }

    public function test_profile_counts_unique_assigned_students_without_loading_student_collections(): void
    {
        $view = app(InstructorController::class)->show('instructor', Request::create('/instructors/instructor'));

        $this->assertSame('instructors.viewinstructor', $view->name());
        $this->assertSame(1, $view->getData()['activeStudents']);
        $this->assertSame(1, $view->getData()['completedStudents']);
        $instructor = $view->getData()['instructor'];
        $this->assertFalse($instructor->fleet->relationLoaded('student'));
        $this->assertFalse($instructor->classrooms->first()->relationLoaded('students'));
        $this->assertEqualsCanonicalizing(['both', 'classroom'], $instructor->assignedStudents()->pluck('id')->all());
        $this->assertSame(0, Instructor::findOrFail('unassigned')->assignedStudents()->count());
    }

    public function test_attendances_are_scoped_paged_searchable_sorted_and_escaped(): void
    {
        DB::table('lessons')->insert(['id' => 'lesson', 'name' => '<b>Parking</b>']);
        DB::table('attendances')->insert([
            ['id' => '1', 'instructor_id' => 'instructor', 'student_id' => 'both', 'lesson_id' => 'lesson', 'created_at' => '2026-11-01 10:00:00'],
            ['id' => '2', 'instructor_id' => 'instructor', 'student_id' => null, 'lesson_id' => null, 'created_at' => '2026-11-02 10:00:00'],
            ['id' => '3', 'instructor_id' => 'other', 'student_id' => 'outside', 'lesson_id' => 'lesson', 'created_at' => '2026-11-03 10:00:00'],
        ]);
        DB::table('attendances')->insert(['id' => 'deleted', 'instructor_id' => 'instructor', 'deleted_at' => now()]);

        $params = [
            'section' => 'attendances', 'draw' => 4, 'start' => 0, 'length' => 1,
            'columns' => [
                ['data' => 'created_at', 'name' => 'attendances.created_at', 'orderable' => 'true'],
                ['data' => 'student_name', 'name' => 'student.fname', 'orderable' => 'true'],
                ['data' => 'lesson_name', 'name' => 'lesson.name', 'orderable' => 'true'],
            ],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ];
        $first = $this->fetch($params);
        $this->assertArrayNotHasKey('error', $first);
        $this->assertSame(4, $first['draw']);
        $this->assertSame(2, $first['recordsTotal']);
        $this->assertCount(1, $first['data']);
        $this->assertSame('Unavailable', $first['data'][0]['student_name']);
        $this->assertSame('Unavailable', $first['data'][0]['lesson_name']);
        $this->assertStringStartsWith('2026-11-02T10:00:00', $first['data'][0]['created_at']);
        $this->assertEqualsCanonicalizing(['created_at', 'student_name', 'lesson_name'], array_keys($first['data'][0]));

        $params['start'] = 1;
        $second = $this->fetch($params);
        $this->assertCount(1, $second['data'], json_encode($second));
        $this->assertStringContainsString('&lt;script&gt;', $second['data'][0]['student_name']);
        $this->assertStringContainsString('Middle Alpha', $second['data'][0]['student_name']);
        $this->assertSame('&lt;b&gt;Parking&lt;/b&gt;', $second['data'][0]['lesson_name']);

        $params['start'] = 0;
        foreach (['Parking', 'Middle', 'Alpha', '2026-11-01'] as $search) {
            $params['search'] = ['value' => $search];
            $result = $this->fetch($params);
            $this->assertSame(2, $result['recordsTotal']);
            $this->assertSame(1, $result['recordsFiltered']);
        }
        $params['search'] = ['value' => 'Outside'];
        $this->assertSame(0, $this->fetch($params)['recordsFiltered']);
        foreach ([1, 2] as $column) {
            $params['order'] = [['column' => $column, 'dir' => 'asc']];
            $params['search'] = ['value' => 'Parking'];
            $sorted = $this->fetch($params);
            $this->assertArrayNotHasKey('error', $sorted);
            $this->assertSame(1, $sorted['recordsFiltered']);
        }
    }

    public function test_chart_returns_only_current_month_daily_counts_for_this_instructor(): void
    {
        Carbon::setTestNow('2026-11-11 12:00:00');
        DB::table('attendances')->insert([
            ['id' => '1', 'instructor_id' => 'instructor', 'attendance_date' => '2026-11-01 10:00:00', 'deleted_at' => null],
            ['id' => '2', 'instructor_id' => 'instructor', 'attendance_date' => '2026-11-01 11:00:00', 'deleted_at' => null],
            ['id' => '3', 'instructor_id' => 'other', 'attendance_date' => '2026-11-01 10:00:00', 'deleted_at' => null],
            ['id' => '4', 'instructor_id' => 'instructor', 'attendance_date' => '2026-10-31 23:59:59', 'deleted_at' => null],
            ['id' => '5', 'instructor_id' => 'instructor', 'attendance_date' => '2026-12-01 00:00:00', 'deleted_at' => null],
            ['id' => '6', 'instructor_id' => 'instructor', 'attendance_date' => '2026-11-01 10:00:00', 'deleted_at' => '2026-11-02 00:00:00'],
        ]);
        DB::table('schedule_lessons')->insert([
            ['id' => '1', 'instructor_id' => 'instructor', 'start_time' => '2026-11-02 10:00:00'],
            ['id' => '2', 'instructor_id' => 'other', 'start_time' => '2026-11-03 10:00:00'],
            ['id' => '3', 'instructor_id' => 'instructor', 'start_time' => '2026-12-01 00:00:00'],
        ]);

        $this->assertSame([
            'labels' => ['2026-11-01', '2026-11-02'],
            'attendances' => [2, 0],
            'schedules' => [0, 1],
        ], $this->fetch(['section' => 'chart']));

        $this->assertSame([
            'labels' => ['2026-10-31'],
            'attendances' => [1],
            'schedules' => [0],
        ], $this->fetch(['section' => 'chart', 'month' => '2026-10']));
        $this->assertSame([
            'labels' => ['2026-12-01'],
            'attendances' => [1],
            'schedules' => [1],
        ], $this->fetch(['section' => 'chart', 'month' => '2026-12']));
        $this->assertSame(
            ['labels' => [], 'attendances' => [], 'schedules' => []],
            $this->fetch(['section' => 'chart', 'month' => '2025-11'])
        );

        DB::table('attendances')->delete();
        DB::table('schedule_lessons')->delete();
        $this->assertSame(['labels' => [], 'attendances' => [], 'schedules' => []], $this->fetch(['section' => 'chart']));
    }

    /** @dataProvider invalidChartMonths */
    public function test_chart_rejects_invalid_months(string $month): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->fetch(['section' => 'chart', 'month' => $month]);
    }

    public static function invalidChartMonths(): array
    {
        return [[''], ['2026-13'], ['2026-00'], ['2026-1'], ['not-a-month'], ['2026-11-01']];
    }

    public function test_student_datatable_uses_the_same_assignment_scope_and_status_filters(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('course_id')->nullable();
            $table->string('phone')->nullable();
        });
        Schema::table('users', fn (Blueprint $table) => $table->string('student_id')->nullable());
        Schema::create('courses', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('student_id');
            $table->softDeletes();
        });
        DB::table('students')->update(['created_at' => '2026-11-01 10:00:00']);
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->andReturnUsing(fn ($permission) => $permission === 'view all students');
        $this->actingAs($user);

        $fetch = function (string $status, string $instructor = 'instructor') {
            $request = Request::create('/api/instructor-students', 'GET', [
                'instructorId' => $instructor, 'status' => $status, 'draw' => 1,
                'start' => 0, 'length' => 10,
            ]);
            app()->instance('request', $request);
            app()->forgetInstance('datatables.request');

            return app(StudentController::class)->fetchInstructorStudents($request)->getData(true);
        };
        $active = $fetch('active');
        $this->assertArrayNotHasKey('error', $active);
        $this->assertSame(1, $active['recordsTotal']);
        $this->assertCount(1, $active['data']);
        $this->assertSame('both', $active['data'][0]['id']);
        $this->assertStringContainsString('&lt;script&gt;', $active['data'][0]['full_name']);
        $finished = $fetch('finished');
        $this->assertSame(1, $finished['recordsTotal']);
        $this->assertSame('classroom', $finished['data'][0]['id']);
        $this->assertSame(0, $fetch('active', 'unassigned')['recordsTotal']);
    }
}
