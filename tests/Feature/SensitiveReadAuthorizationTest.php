<?php

namespace Tests\Feature;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SensitiveReadAuthorizationTest extends TestCase
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
        foreach (['superAdmin', 'Super-Admin', 'admin', 'financeAdmin', 'instructor', 'instructorManager', 'student'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }
        (require base_path('database/migrations/2026_11_11_120000_seed_access_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_12_001100_seed_invoice_permissions.php'))->up();
    }

    /** @dataProvider sensitiveRoutes */
    public function test_guests_cannot_read_sensitive_data(string $path, bool $financial): void
    {
        $this->getJson($path)->assertUnauthorized();
    }

    /** @dataProvider unprivilegedRoutes */
    public function test_unprivileged_accounts_cannot_read_sensitive_data(string $path, ?string $role): void
    {
        $this->authenticate($role, str_starts_with($path, '/api/'));

        $this->getJson($path)->assertForbidden();
    }

    /** @dataProvider privilegedRoutes */
    public function test_privileged_accounts_pass_the_existing_route_boundary(string $path, string $role): void
    {
        $this->authenticate($role, str_starts_with($path, '/api/'));

        // Probe the real route middleware without querying production data or MbiraSync.
        $route = app('router')->getRoutes()->match(Request::create($path, 'GET'));
        $action = $route->getAction();
        unset($action['controller']);
        $action['uses'] = static fn () => response()->noContent();
        $route->setAction($action);

        $this->getJson($path)->assertNoContent();
    }

    /** @dataProvider dashboardRoles */
    public function test_dashboard_renders_for_administrator_roles(string $role, string $dashboardId): void
    {
        $this->authenticate($role, false);
        $user = auth()->user();
        $user->setRelation('administrator', null);
        $user->setRelation('instructor', null);
        $user->setRelation('student', null);
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());

        $html = view('dashboard', [
            'students' => collect(),
            'instructors' => collect(),
            'activities' => collect(),
            'settings' => (object) ['bonus' => 0],
        ])->render();

        $this->assertStringContainsString('id="'.$dashboardId.'"', $html);
        $this->assertStringNotContainsString('Dashboard Access Unavailable', $html);
    }

    public function test_admin_can_load_the_dashboard(): void
    {
        Schema::create('settings', function ($table) {
            $table->id();
            $table->decimal('bonus')->default(0);
        });
        foreach (['students', 'invoices', 'activity_log'] as $name) {
            Schema::create($name, function ($table) {
                $table->id();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        Schema::create('instructors', function ($table) {
            $table->id();
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('settings')->insert(['id' => 1, 'bonus' => 0]);

        $this->authenticate('admin', false);
        $user = auth()->user();
        $user->setRelation('administrator', null);
        $user->setRelation('instructor', null);
        $user->setRelation('student', null);
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());

        $this->get('/dashboard')->assertOk()->assertSee('id="dashboardApp"', false);
    }

    /** @dataProvider instructorDashboardRoutes */
    public function test_instructor_can_load_their_dashboard_without_management_data(string $method, string $path, bool $alsoAdmin): void
    {
        Schema::create('settings', function ($table) {
            $table->id();
            $table->decimal('bonus')->default(0);
        });
        Schema::create('instructors', function ($table) {
            $table->string('id')->primary();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('instructor_payments', function ($table) {
            $table->string('instructor_id');
            $table->date('payment_date');
        });
        Schema::create('attendances', function ($table) {
            $table->string('instructor_id');
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('settings')->insert(['id' => 1, 'bonus' => 25]);
        DB::table('instructors')->insert(['id' => 'instructor-1']);
        DB::table('attendances')->insert([
            ['instructor_id' => 'instructor-1', 'created_at' => now(), 'updated_at' => now()],
            ['instructor_id' => 'instructor-1', 'created_at' => now(), 'updated_at' => now()],
            ['instructor_id' => 'instructor-2', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->authenticate('instructor', false);
        $user = auth()->user();
        if ($alsoAdmin) {
            $admin = Role::findByName('admin', 'web');
            $admin->revokePermissionTo('access management');
            $user->setRelation('roles', collect([Role::findByName('instructor', 'web'), $admin]));
        }
        $instructor = Instructor::findOrFail('instructor-1');
        $instructor->setRelation('department', null);
        $instructor->setRelation('fleet', null);
        $instructor->setRelation('classrooms', collect());
        $user->instructor_id = $instructor->id;
        $user->setRelation('instructor', $instructor);
        $user->setRelation('administrator', null);
        $user->setRelation('student', null);
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());

        $this->{$method}($path)
            ->assertOk()
            ->assertSee('Your Summary')
            ->assertSee('K50.00')
            ->assertDontSee('id="dashboardApp"', false)
            ->assertDontSee('id="dashboardSummary"', false);

        $this->getJson('/summaryData')->assertForbidden();
        $this->getJson('/api/dashboardSummary')->assertForbidden();
        $summary = $this->getJson('/instructorSummaryData')->assertOk()->json();
        $this->assertSame(2, collect($summary)->firstWhere('date', now()->toDateString())['count']);
    }

    public static function instructorDashboardRoutes(): array
    {
        return [
            'login redirect' => ['get', '/dashboard', false],
            'home' => ['get', '/', false],
            'dashboard form' => ['post', '/', false],
            'instructor with restricted admin role' => ['get', '/dashboard', true],
        ];
    }

    public function test_instructor_without_portal_permission_cannot_access_dashboard(): void
    {
        Role::findByName('instructor', 'web')->revokePermissionTo('access portal');
        $this->authenticate('instructor', false);

        $this->getJson('/dashboard')->assertForbidden();
        $this->getJson('/')->assertForbidden();
        $this->postJson('/')->assertForbidden();
    }

    /** @dataProvider studentDashboardRoutes */
    public function test_student_can_load_their_dashboard_without_management_data(string $method, string $path, bool $alsoAdmin): void
    {
        $this->authenticate('student', false);
        $user = auth()->user();
        if ($alsoAdmin) {
            $admin = Role::findByName('admin', 'web');
            $admin->revokePermissionTo('access management');
            $user->setRelation('roles', collect([Role::findByName('student', 'web'), $admin]));
        }
        $user->setRelation('administrator', null);
        $user->setRelation('instructor', null);
        $user->setRelation('student', null);
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());

        $this->{$method}($path)
            ->assertOk()
            ->assertSee('Launch App')
            ->assertDontSee('id="dashboardApp"', false)
            ->assertDontSee('id="dashboardSummary"', false)
            ->assertDontSee('Your Summary');

        $this->getJson('/summaryData')->assertForbidden();
        $this->getJson('/api/dashboardSummary')->assertForbidden();
        $this->getJson('/instructorSummaryData')->assertForbidden();
    }

    public static function studentDashboardRoutes(): array
    {
        return [
            'login redirect' => ['get', '/dashboard', false],
            'home' => ['get', '/', false],
            'dashboard form' => ['post', '/', false],
            'student with restricted admin role' => ['get', '/dashboard', true],
        ];
    }

    public function test_student_without_portal_permission_cannot_access_dashboard(): void
    {
        Role::findByName('student', 'web')->revokePermissionTo('access portal');
        $this->authenticate('student', false);

        $this->getJson('/dashboard')->assertForbidden();
        $this->getJson('/')->assertForbidden();
        $this->postJson('/')->assertForbidden();
    }

    public static function dashboardRoles(): array
    {
        return [
            'admin' => ['admin', 'dashboardApp'],
            'finance admin' => ['financeAdmin', 'dashboardApp'],
            'super admin' => ['superAdmin', 'dashboardSummary'],
            'legacy super admin' => ['Super-Admin', 'dashboardSummary'],
        ];
    }

    public static function sensitiveRoutes(): array
    {
        return [
            'dashboard API' => ['/api/dashboardSummary', true],
            'invoices API' => ['/api/invoices', true],
            'invoice home API' => ['/api/invoicesHome', true],
            'invoice table API' => ['/api/fetchInvoices', true],
            'invoice detail API' => ['/api/invoice-view/1', true],
            'web dashboard' => ['/dashboard', true],
            'web home dashboard' => ['/', true],
            'web summary' => ['/summaryData', true],
            'web invoice search' => ['/search-invoice', true],
            'web invoices' => ['/invoices', true],
            'web invoice detail' => ['/view-invoice/1', true],
            'web invoice PDF' => ['/invoice-pdf/1', true],
            'locations API' => ['/api/get-all-vehicle-locations', false],
            'location history API' => ['/api/show-vehicle-geo-data/1', false],
            'locations web' => ['/get-all-vehicle-locations', false],
            'location history web' => ['/show-vehicle-geo-data/1', false],
            'MbiraSync positions' => ['/api/mbirasync/positions', false],
            'MbiraSync live' => ['/api/mbirasync/positions/live', false],
            'MbiraSync history' => ['/api/mbirasync/positions/1/history', false],
        ];
    }

    public static function unprivilegedRoutes(): array
    {
        $cases = [];
        foreach (self::sensitiveRoutes() as $name => [$path, $financial]) {
            $roles = [null, 'student', 'instructor', 'instructorManager'];
            if (in_array($path, ['/dashboard', '/'], true)) {
                $roles = [null, 'instructorManager'];
            }
            if (!$financial) {
                $roles[] = 'financeAdmin';
            }
            foreach ($roles as $role) {
                $cases[$name.' '.($role ?? 'roleless signup')] = [$path, $role];
            }
        }

        return $cases;
    }

    public static function privilegedRoutes(): array
    {
        $cases = [];
        foreach (self::sensitiveRoutes() as $name => [$path, $financial]) {
            $roles = ['superAdmin', 'Super-Admin', 'admin'];
            if ($financial) {
                $roles[] = 'financeAdmin';
            }
            if (in_array($path, ['/dashboard', '/'], true)) {
                $roles[] = 'instructor';
                $roles[] = 'student';
            }
            foreach ($roles as $role) {
                $cases[$name.' '.$role] = [$path, $role];
            }
        }

        return $cases;
    }

    private function authenticate(?string $role, bool $api): void
    {
        $user = new User(['name' => 'Authorization test']);
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->setRelation('roles', collect($role === null ? [] : [Role::findByName($role, 'web')]));

        if ($api) {
            Sanctum::actingAs($user, ['basic-access']);
        } else {
            $this->actingAs($user, 'web');
        }
    }
}
