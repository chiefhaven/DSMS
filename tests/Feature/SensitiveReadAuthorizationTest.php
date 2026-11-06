<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Request;
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
        $user->setRelation('roles', collect($role === null ? [] : [
            new Role(['name' => $role, 'guard_name' => 'web']),
        ]));

        if ($api) {
            Sanctum::actingAs($user, ['basic-access']);
        } else {
            $this->actingAs($user, 'web');
        }
    }
}
