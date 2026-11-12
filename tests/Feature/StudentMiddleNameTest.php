<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentMiddleNameTest extends TestCase
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
        Http::fake();

        (require base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();
        $role = Role::create(['name' => 'student-manager', 'guard_name' => 'web']);
        Role::create(['name' => 'student', 'guard_name' => 'web']);
        Role::create(['name' => 'superAdmin', 'guard_name' => 'web']);
        foreach (['create students', 'edit students', 'view all students'] as $name) {
            $role->givePermissionTo(Permission::create(['name' => $name, 'guard_name' => 'web']));
        }
        Schema::create('administrators', function (Blueprint $table) {
            $table->id();
            $table->string('fname');
            $table->string('sname');
            $table->softDeletes();
        });
        Schema::create('districts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
        });
        Schema::create('training_levels', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->string('id')->primary();
            foreach (['fname', 'mname', 'sname', 'gender', 'trn', 'phone', 'address', 'date_of_birth', 'district_id', 'trainingLevel_id', 'signature', 'added_by', 'course_id'] as $name) {
                $table->string($name)->nullable();
            }
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('student_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->softDeletes();
        });
        DB::table('administrators')->insert(['id' => 1, 'fname' => 'Test', 'sname' => 'Administrator']);
        DB::table('districts')->insert(['id' => 'central', 'name' => 'Central']);
        DB::table('training_levels')->insert(['id' => 'registered', 'name' => 'registered']);

        $user = new User(['name' => 'Student manager']);
        $user->id = 'manager';
        $user->administrator_id = 1;
        $user->setRelation('roles', collect([$role]));
        $this->actingAs($user);
    }

    public function test_creation_accepts_omitted_blank_and_supplied_middle_names(): void
    {
        foreach ([
            'omitted' => [],
            'blank' => ['mname' => ''],
            'supplied' => ['mname' => 'Middle'],
        ] as $case => $middle) {
            $signature = Mockery::mock(UploadedFile::fake()->image('signature.png'))->makePartial();
            $signature->shouldReceive('move')->once();
            $data = array_merge($this->studentData(), [
                'email' => $case.'@example.test', 'phone' => $case,
                'signature' => $signature,
            ], $middle);
            $this->post($case === 'blank' ? '/storestudent' : '/students', $data)->assertRedirect();
            $studentId = DB::table('users')->where('email', $data['email'])->value('student_id');
            $this->assertNotNull($studentId);
            $this->assertDatabaseHas('users', ['student_id' => $studentId, 'name' => 'First Last']);
            $this->assertDatabaseHas('students', [
                'id' => $studentId, 'fname' => 'First', 'sname' => 'Last',
                'mname' => $case === 'supplied' ? 'Middle' : null,
            ]);
        }
        Http::assertNothingSent();
    }

    public function test_edit_preserves_omitted_middle_name_and_accepts_blank_or_supplied_values(): void
    {
        $id = 'f2638868-b1d0-4c92-b71d-59a1f01e6e22';
        DB::table('students')->insert(['id' => $id, 'fname' => 'First', 'mname' => 'Previous', 'sname' => 'Last']);
        DB::table('users')->insert(['id' => 'student-user', 'student_id' => $id, 'email' => 'student@example.test']);
        $data = $this->studentData();

        $this->put('/students/'.$id, $data)->assertRedirect(route('students.show', $id));
        $this->assertDatabaseHas('students', ['id' => $id, 'mname' => 'Previous']);
        $this->post('/student-update', array_merge($data, ['student_id' => $id, 'mname' => '']))
            ->assertRedirect(route('students.show', $id));
        $this->assertDatabaseHas('students', ['id' => $id, 'mname' => null]);
        $this->put('/students/'.$id, array_merge($data, ['mname' => 'New middle']))
            ->assertRedirect(route('students.show', $id));
        $this->assertDatabaseHas('students', ['id' => $id, 'mname' => 'New middle']);
        $this->putJson('/students/'.$id, array_merge($data, ['mname' => ['invalid']]))
            ->assertUnprocessable()->assertJsonValidationErrors('mname');
        $this->assertDatabaseHas('students', ['id' => $id, 'mname' => 'New middle']);
    }

    private function studentData(): array
    {
        return [
            'fname' => 'First', 'sname' => 'Last', 'email' => 'student@example.test',
            'gender' => 'male', 'date_of_birth' => '1990-01-01', 'district' => 'Central',
            'phone' => '123', 'address' => 'Address',
        ];
    }
}
