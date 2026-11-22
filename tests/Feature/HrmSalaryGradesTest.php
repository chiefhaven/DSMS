<?php

namespace Tests\Feature;

use App\Models\SalaryGrade;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HrmSalaryGradesTest extends TestCase
{
    private User $manager;
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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->uuid('notifiable_id');
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('administrators', function (Blueprint $table) {
            $table->id();
            $table->string('salary_grade_id', 36)->nullable();
            $table->softDeletes();
        });
        Schema::create('instructors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('salary_grade_id', 36)->nullable();
            $table->softDeletes();
        });

        (require base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();
        (require base_path('database/migrations/2026_11_22_113900_create_salary_grades_table.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'view salary grades', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage salary grades', 'guard_name' => 'web']);
        Permission::create(['name' => 'view payroll', 'guard_name' => 'web']);

        $this->manager = $this->makeUser('Manager', 'manager@example.test');
        $this->manager->givePermissionTo(['view salary grades', 'manage salary grades']);
        $this->viewer = $this->makeUser('Viewer', 'viewer@example.test');
        $this->viewer->givePermissionTo('view salary grades');
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

    public function test_salary_grades_are_permission_protected_and_support_crud(): void
    {
        $this->actingAs($this->viewer)
            ->get(route('salary-grades.index'))
            ->assertRedirect(route('payroll.index') . '#salary-grades-tab');
        $this->get(route('payroll.index'))
            ->assertOk()
            ->assertViewIs('hrm.payroll')
            ->assertSee('Salary Grades')
            ->assertSee('No salary grades yet.')
            ->assertDontSee('All Payrolls')
            ->assertDontSee('payrollTable')
            ->assertDontSee(route('payroll.fetch'));
        $this->getJson(route('salary-grades.fetch'))->assertOk()->assertExactJson([]);
        $this->postJson(route('salary-grades.store'), [])->assertForbidden();

        $payload = [
            'code' => 'G1',
            'name' => 'Entry level',
            'description' => 'Entry-level salary grade',
            'basic_salary' => 450000,
        ];

        $this->actingAs($this->manager)
            ->postJson(route('salary-grades.store'), $payload)
            ->assertOk()
            ->assertJsonPath('salary_grade.code', 'G1')
            ->assertJsonPath('salary_grade.basic_salary', '450000.00');

        $grade = SalaryGrade::query()->firstOrFail();
        $this->postJson(route('salary-grades.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $updatedPayload = array_merge($payload, [
            'code' => 'G2',
            'basic_salary' => 500000,
        ]);
        $this->putJson(route('salary-grades.update', $grade), $updatedPayload)
            ->assertOk()
            ->assertJsonPath('salary_grade.code', 'G2');

        $this->assertDatabaseHas('salary_grades', [
            'id' => $grade->id,
            'code' => 'G2',
            'basic_salary' => '500000.00',
        ]);

        $this->deleteJson(route('salary-grades.destroy', $grade))->assertOk();
        $this->assertSoftDeleted('salary_grades', ['id' => $grade->id]);
    }

    public function test_salary_grades_is_a_peer_tab_with_payroll_views(): void
    {
        $this->manager->givePermissionTo('view payroll');

        $content = $this->actingAs($this->manager)
            ->get(route('payroll.index'))
            ->assertOk()
            ->getContent();

        $tabListStart = strpos($content, '<ul class="nav nav-tabs nav-tabs-block" role="tablist" aria-label="Payroll views">');
        $this->assertNotFalse($tabListStart);
        $tabListEnd = strpos($content, '</ul>', $tabListStart);
        $this->assertNotFalse($tabListEnd);

        $tabList = substr($content, $tabListStart, $tabListEnd - $tabListStart);
        $this->assertStringContainsString('id="payrolls-tab"', $tabList);
        $this->assertStringContainsString('id="groups-tab"', $tabList);
        $this->assertStringContainsString('id="salary-grades-tab"', $tabList);
    }
}
