<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\HrDepartment;
use App\Models\Instructor;
use App\Models\SalaryGrade;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HrmPayrollTest extends TestCase
{
    private const INSTRUCTOR_ID = '6c1f9c38-8a7e-4a79-9a39-7a1a1e2b4c51';
    private const ADMINISTRATOR_ID = 71;

    private User $processor;
    private User $viewer;

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

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('instructors', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fname')->nullable();
            $table->string('sname')->nullable();
            $table->string('status')->nullable();
            $table->string('hr_department_id', 36)->nullable();
            $table->decimal('basic_salary', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('administrators', function (Blueprint $table) {
            $table->id();
            $table->string('fname')->nullable();
            $table->string('sname')->nullable();
            $table->string('status')->nullable();
            $table->string('hr_department_id', 36)->nullable();
            $table->decimal('basic_salary', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('attendances', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('instructor_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('bonus', 12, 2)->default(0);
        });

        (require base_path('database/migrations/2026_11_15_000600_create_payrolls_table.php'))->up();
        (require base_path('database/migrations/2026_11_16_000100_create_payroll_groups_table.php'))->up();
        (require base_path('database/migrations/2026_11_15_000500_create_designations_table.php'))->up();
        (require base_path('database/migrations/2026_11_16_000300_add_basic_salary_to_designations.php'))->up();
        $legacyDesignationId = (string) Str::uuid();
        DB::table('designations')->insert([
            'id' => $legacyDesignationId,
            'name' => 'Legacy designation',
            'basic_salary' => 1800,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('instructors')->insert([
            'id' => self::INSTRUCTOR_ID, 'fname' => 'Example', 'sname' => 'Instructor', 'status' => 'active',
            'basic_salary' => 1500,
        ]);
        DB::table('administrators')->insert([
            'id' => self::ADMINISTRATOR_ID, 'fname' => 'Office', 'sname' => 'Admin', 'status' => 'Active',
        ]);
        DB::table('administrators')->where('id', self::ADMINISTRATOR_ID)->update([
            'designation_id' => $legacyDesignationId,
        ]);
        (require base_path('database/migrations/2026_11_22_113900_create_salary_grades_table.php'))->up();
        (require base_path('database/migrations/2026_11_22_120000_add_salary_grades_to_staff_and_remove_designation_salary.php'))->up();
        (require base_path('database/migrations/2026_11_15_000650_create_hr_departments_table.php'))->up();
        (require base_path('database/migrations/2026_11_22_130000_add_hr_department_id_to_designations.php'))->up();
        (require base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'view payroll', 'guard_name' => 'web']);
        Permission::create(['name' => 'process payroll', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage salary grades', 'guard_name' => 'web']);
        Permission::create(['name' => 'view designations', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage designations', 'guard_name' => 'web']);

        $this->processor = $this->makeUser('Processor', 'processor@example.test');
        $this->processor->givePermissionTo([
            'view payroll',
            'process payroll',
            'manage salary grades',
            'view designations',
            'manage designations',
        ]);
        $this->viewer = $this->makeUser('Viewer', 'viewer@example.test');
        $this->viewer->givePermissionTo('view payroll');

        DB::table('settings')->insert(['id' => 1, 'bonus' => 50]);
    }

    private function makeUser(string $name, string $email): User
    {
        return User::withoutEvents(function () use ($name, $email) {
            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = Hash::make('InitialPassword123!');
            $user->save();

            return $user;
        });
    }

    private function addAttendance(string $createdAt): void
    {
        DB::table('attendances')->insert([
            'id' => (string) Str::uuid(),
            'instructor_id' => self::INSTRUCTOR_ID,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    public function test_payroll_actions_require_the_specific_permissions(): void
    {
        $unauthorized = $this->makeUser('Unauthorized', 'unauthorized@example.test');

        $this->actingAs($unauthorized)->get(route('payroll.index'))->assertForbidden();
        $this->actingAs($unauthorized)->getJson(route('payroll.fetch'))->assertForbidden();

        $this->actingAs($this->viewer)->getJson(route('payroll.fetch'))->assertOk();
        $this->actingAs($this->viewer)->postJson(route('payroll.store'), [])->assertForbidden();

        $this->assertDatabaseCount('payrolls', 0);
    }

    public function test_store_creates_a_group_with_instructor_bonus_and_net_pay_per_employee(): void
    {
        $this->addAttendance('2026-11-03 09:00:00');
        $this->addAttendance('2026-11-10 09:00:00');
        $this->addAttendance('2026-11-24 09:00:00');
        $this->addAttendance('2026-10-30 09:00:00'); // outside the pay month

        $this->actingAs($this->processor)
            ->postJson(route('payroll.store'), [
                'payment_month' => '2026-11',
                'group_name' => 'November run',
                'status' => 'Paid',
                'payment_date' => '2026-11-30',
                'employees' => [
                    ['employee' => 'instructor:' . self::INSTRUCTOR_ID, 'basic_salary' => 1000, 'allowances' => 100, 'deductions' => 50],
                    ['employee' => 'administrator:' . self::ADMINISTRATOR_ID, 'basic_salary' => 800],
                ],
            ])
            ->assertOk()
            ->assertJson(['message' => 'Payroll group created with 2 employee records.']);

        $this->assertDatabaseCount('payroll_groups', 1);
        $this->assertDatabaseHas('payroll_groups', ['name' => 'November run', 'payment_month' => '2026-11']);

        $group = \App\Models\PayrollGroup::first();
        $this->assertDatabaseHas('payrolls', [
            'instructor_id' => self::INSTRUCTOR_ID,
            'payroll_group_id' => $group->id,
            'payment_month' => '2026-11',
            'bonus_attendances' => 3,
            'bonus' => 150,
            'net_pay' => 1200,
            'status' => 'Paid',
            'created_by' => $this->processor->id,
        ]);
        $this->assertDatabaseHas('payrolls', [
            'administrator_id' => self::ADMINISTRATOR_ID,
            'payroll_group_id' => $group->id,
            'bonus' => 0,
            'net_pay' => 800,
        ]);
    }

    public function test_duplicate_employee_months_are_rejected(): void
    {
        $payload = [
            'payment_month' => '2026-11',
            'status' => 'Pending',
            'employees' => [
                ['employee' => 'administrator:' . self::ADMINISTRATOR_ID, 'basic_salary' => 800],
            ],
        ];

        $this->actingAs($this->processor)->postJson(route('payroll.store'), $payload)->assertOk();

        $response = $this->actingAs($this->processor)
            ->postJson(route('payroll.store'), $payload)
            ->assertUnprocessable();

        $this->assertStringContainsString('already have payroll records', $response->json('message'));
        $this->assertDatabaseCount('payrolls', 1);
        $this->assertDatabaseCount('payroll_groups', 1);
    }

    public function test_group_deletion_removes_its_payroll_records(): void
    {
        $this->actingAs($this->processor)->postJson(route('payroll.store'), [
            'payment_month' => '2026-11',
            'status' => 'Pending',
            'employees' => [
                ['employee' => 'instructor:' . self::INSTRUCTOR_ID, 'basic_salary' => 1000],
                ['employee' => 'administrator:' . self::ADMINISTRATOR_ID, 'basic_salary' => 800],
            ],
        ])->assertOk();

        $group = \App\Models\PayrollGroup::first();

        $this->actingAs($this->viewer)
            ->getJson(route('payroll.groups', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.employees', 2);

        $this->actingAs($this->viewer)
            ->deleteJson(route('payroll.groups.destroy', $group->id))
            ->assertForbidden();

        $this->actingAs($this->processor)
            ->deleteJson(route('payroll.groups.destroy', $group->id))
            ->assertOk();

        $this->assertSame(0, \App\Models\Payroll::count());
        $this->assertSame(0, \App\Models\PayrollGroup::count());
    }

    public function test_fetch_filters_by_global_search_server_side(): void
    {
        \App\Models\Payroll::create([
            'instructor_id' => self::INSTRUCTOR_ID,
            'payment_month' => '2026-11',
            'basic_salary' => 1000,
            'net_pay' => 1000,
            'status' => 'Pending',
        ]);
        \App\Models\Payroll::create([
            'administrator_id' => self::ADMINISTRATOR_ID,
            'payment_month' => '2026-11',
            'basic_salary' => 800,
            'net_pay' => 800,
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($this->viewer)->getJson(route('payroll.fetch', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'Office'],
        ]))->assertOk();

        $this->assertSame(1, $response->json('recordsFiltered'));
        $this->assertStringContainsString('Office Admin', $response->json('data.0.employee'));
    }

    public function test_employees_endpoint_uses_editable_salary_or_grade_default(): void
    {
        $this->assertFalse(Schema::hasColumn('designations', 'basic_salary'));

        $salaryGrade = SalaryGrade::create([
            'code' => 'SG-01',
            'name' => 'Senior',
            'basic_salary' => 2000,
        ]);
        DB::table('administrators')->where('id', self::ADMINISTRATOR_ID)->update(['salary_grade_id' => $salaryGrade->id]);
        DB::table('instructors')->where('id', self::INSTRUCTOR_ID)->update(['salary_grade_id' => $salaryGrade->id]);
        $this->actingAs($this->processor)
            ->deleteJson(route('salary-grades.destroy', $salaryGrade))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Reassign this salary grade before deleting it.');

        $response = $this->actingAs($this->viewer)
            ->getJson(route('payroll.employees'))
            ->assertOk();

        $instructorEntry = collect($response->json())->firstWhere('value', 'instructor:' . self::INSTRUCTOR_ID);
        $this->assertNotNull($instructorEntry);
        $this->assertEquals(1500, (float) $instructorEntry['basic_salary']);

        $administratorEntry = collect($response->json())->firstWhere('value', 'administrator:' . self::ADMINISTRATOR_ID);
        $this->assertNotNull($administratorEntry);
        $this->assertEquals(1800, (float) $administratorEntry['basic_salary']);

        DB::table('instructors')->where('id', self::INSTRUCTOR_ID)->update(['basic_salary' => null]);
        DB::table('administrators')->where('id', self::ADMINISTRATOR_ID)->update(['basic_salary' => null]);
        $gradeDefaults = $this->actingAs($this->viewer)
            ->getJson(route('payroll.employees'))
            ->assertOk();
        $defaultInstructor = collect($gradeDefaults->json())->firstWhere('value', 'instructor:' . self::INSTRUCTOR_ID);
        $this->assertEquals(2000, (float) $defaultInstructor['basic_salary']);
        $defaultAdministrator = collect($gradeDefaults->json())->firstWhere('value', 'administrator:' . self::ADMINISTRATOR_ID);
        $this->assertEquals(2000, (float) $defaultAdministrator['basic_salary']);
    }

    public function test_designations_require_and_return_an_hr_department(): void
    {
        $department = HrDepartment::create(['name' => 'Operations']);

        $this->actingAs($this->processor)
            ->postJson(route('designations.store'), ['name' => 'Accountant'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('hr_department_id');

        $this->postJson(route('designations.store'), [
            'name' => 'Accountant',
            'description' => 'Finance team designation',
            'hr_department_id' => $department->id,
        ])
            ->assertOk()
            ->assertJsonPath('designation.hr_department_id', $department->id)
            ->assertJsonPath('designation.hr_department.name', 'Operations');

        $this->getJson(route('designations.fetch'))
            ->assertOk()
            ->assertJsonPath('0.hr_department.name', 'Operations');
    }

    public function test_inactive_employees_are_excluded_and_rejected(): void
    {
        DB::table('instructors')->where('id', self::INSTRUCTOR_ID)->update(['status' => 'Suspended']);
        DB::table('administrators')->where('id', self::ADMINISTRATOR_ID)->update(['status' => 'Pending']);

        $employees = $this->actingAs($this->viewer)
            ->getJson(route('payroll.employees'))
            ->assertOk()
            ->json();
        $this->assertEmpty($employees);

        $response = $this->actingAs($this->processor)
            ->postJson(route('payroll.store'), [
                'payment_month' => '2026-11',
                'status' => 'Pending',
                'employees' => [
                    ['employee' => 'instructor:' . self::INSTRUCTOR_ID, 'basic_salary' => 1000],
                    ['employee' => 'administrator:' . self::ADMINISTRATOR_ID, 'basic_salary' => 800],
                ],
            ])
            ->assertUnprocessable();

        $this->assertStringContainsString('not active', $response->json('message'));
        $this->assertStringContainsString('Example Instructor', $response->json('message'));
        $this->assertStringContainsString('Office Admin', $response->json('message'));
        $this->assertDatabaseCount('payrolls', 0);
        $this->assertDatabaseCount('payroll_groups', 0);
    }

    public function test_group_details_endpoint_returns_summary_and_records(): void
    {
        $this->addAttendance('2026-11-03 09:00:00');

        $this->actingAs($this->processor)->postJson(route('payroll.store'), [
            'payment_month' => '2026-11',
            'group_name' => 'November run',
            'status' => 'Pending',
            'employees' => [
                ['employee' => 'instructor:' . self::INSTRUCTOR_ID, 'basic_salary' => 1000, 'allowances' => 100, 'deductions' => 50],
                ['employee' => 'administrator:' . self::ADMINISTRATOR_ID, 'basic_salary' => 800],
            ],
        ])->assertOk();

        $group = \App\Models\PayrollGroup::first();

        $response = $this->actingAs($this->viewer)
            ->getJson(route('payroll.groups.show', $group->id))
            ->assertOk()
            ->assertJsonPath('group.name', 'November run')
            ->assertJsonPath('summary.employees', 2)
            ->assertJsonPath('summary.paid', 0)
            ->assertJsonPath('summary.pending', 2);

        // Instructor: 1000 + 100 + (1 attendance x 50) - 50 = 1100; administrator: 800.
        $this->assertEquals(1900, $response->json('summary.net_pay'));
        $this->assertCount(2, $response->json('records'));
        $this->assertSame('Example Instructor', $response->json('records.0.employee'));
        $this->assertEquals(50, $response->json('records.0.bonus'));
    }

    public function test_paid_status_requires_a_payment_date(): void
    {
        $this->actingAs($this->processor)
            ->postJson(route('payroll.store'), [
                'payment_month' => '2026-11',
                'status' => 'Paid',
                'employees' => [
                    ['employee' => 'administrator:' . self::ADMINISTRATOR_ID, 'basic_salary' => 800],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_date']);
    }
}
