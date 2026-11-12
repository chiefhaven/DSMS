<?php

namespace Tests\Feature;

use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseResourceController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\InvoiceController;
use App\Http\Requests\UpdateAdministratorRequest;
use App\Http\Requests\UpdateInstructorRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Requests\UpdateexpenseRequest;
use App\Models\Administrator;
use App\Models\Expense;
use App\Models\Instructor;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class ManagementResourceRoutesTest extends TestCase
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

        Schema::create('settings', fn (Blueprint $table) => $table->id());
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administrator_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->softDeletes();
        });
        foreach (['courses', 'classrooms', 'fleets', 'administrators', 'instructors', 'invoices', 'expenses'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->string('id')->primary();
                if ($name === 'invoices') {
                    $table->string('invoice_number')->nullable();
                }
                $table->softDeletes();
            });
        }

        // The parent route file includes these registrations after integration.
        if (!Route::has('invoices.create')) {
            Route::middleware('web')->group(base_path('routes'.DIRECTORY_SEPARATOR.'management-resources.php'));
        }
        app('router')->getRoutes()->refreshNameLookups();
    }

    /** @dataProvider resourceRoutes */
    public function test_canonical_routes_match_their_resource_names(string $name, string $verb, string $path): void
    {
        $route = app('router')->getRoutes()->match(Request::create($path, $verb));

        $this->assertSame($name, $route->getName());
        $this->assertSame($path, parse_url(route($name, str_contains($path, '/123') ? 123 : [], false), PHP_URL_PATH));
    }

    public static function resourceRoutes(): array
    {
        $cases = [];
        foreach (['invoices', 'instructors', 'administrators', 'expenses'] as $resource) {
            foreach ([
                'index' => ['GET', "/$resource"],
                'create' => ['GET', "/$resource/create"],
                'store' => ['POST', "/$resource"],
                'show' => ['GET', "/$resource/123"],
                'edit' => ['GET', "/$resource/123/edit"],
                'update' => ['PUT', "/$resource/123"],
                'destroy' => ['DELETE', "/$resource/123"],
            ] as $action => [$verb, $path]) {
                $cases["$resource.$action"] = ["$resource.$action", $verb, $path];
            }
            $cases["$resource.patch"] = ["$resource.update", 'PATCH', "/$resource/123"];
        }
        foreach (['payments', 'instructor-payments'] as $resource) {
            $cases["$resource.index"] = ["$resource.index", 'GET', "/$resource"];
            $cases["$resource.store"] = ["$resource.store", 'POST', "/$resource"];
        }
        $cases['payments.destroy'] = ['payments.destroy', 'DELETE', '/payments/123'];

        return $cases;
    }

    public function test_stub_methods_are_not_exposed_as_canonical_routes(): void
    {
        foreach (['payments' => ['create', 'show', 'edit', 'update'], 'instructor-payments' => ['create', 'show', 'edit', 'update', 'destroy']] as $resource => $actions) {
            foreach ($actions as $action) {
                $this->assertFalse(Route::has("$resource.$action"));
            }
        }
    }

    public function test_administrators_index_returns_a_view_without_loading_profiles(): void
    {
        $view = app(AdministratorController::class)->index(Request::create('/administrators'));

        $this->assertSame('administrators.administrators', $view->name());
        $this->assertArrayNotHasKey('administrator', $view->getData());
    }

    public function test_administrators_datatable_pages_searches_and_escapes_profiles(): void
    {
        Schema::table('administrators', function (Blueprint $table) {
            $table->string('fname');
            $table->string('sname');
            $table->string('phone')->nullable();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('email');
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->string('model_id');
        });
        DB::table('administrators')->insert([
            ['id' => '1', 'fname' => '<script>alert(1)</script>', 'sname' => 'Alpha', 'phone' => '123'],
            ['id' => '2', 'fname' => 'Zed', 'sname' => 'Beta', 'phone' => null],
        ]);
        DB::table('users')->insert(['id' => 1, 'administrator_id' => 1, 'email' => 'finance@example.test']);
        DB::table('roles')->insert(['id' => 1, 'name' => 'financeAdmin', 'guard_name' => 'web']);
        DB::table('model_has_roles')->insert(['role_id' => 1, 'model_type' => User::class, 'model_id' => '1']);

        $viewer = Mockery::mock(User::class)->makePartial();
        $viewer->shouldReceive('can')->andReturn(true);
        $this->actingAs($viewer);
        $fetch = function (string $search = '', int $start = 0) {
            $request = Request::create('/administrators', 'GET', [
                'draw' => 3,
                'start' => $start,
                'length' => 1,
                'search' => ['value' => $search],
                'columns' => [['data' => 'administrator', 'name' => 'fname', 'orderable' => 'true']],
                'order' => [['column' => 0, 'dir' => 'asc']],
            ], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
            app()->instance('request', $request);
            app()->forgetInstance('datatables.request');

            return app(AdministratorController::class)->index($request)->getData(true);
        };

        $data = $fetch();
        $this->assertSame(3, $data['draw']);
        $this->assertSame(2, $data['recordsTotal']);
        $this->assertCount(1, $data['data']);
        $row = $data['data'][0];
        $this->assertEqualsCanonicalizing(['actions', 'administrator', 'roles', 'email', 'phone'], array_keys($row));
        $this->assertStringContainsString('&lt;script&gt;', $row['administrator']);
        $this->assertStringContainsString('Finance Admin', $row['roles']);
        $this->assertStringContainsString(route('administrators.edit', 1), $row['actions']);
        $this->assertStringContainsString(route('administrators.show', 1), $row['actions']);
        $this->assertStringContainsString('name="_method" value="DELETE"', $row['actions']);

        foreach (['finance@example.test', 'financeAdmin', '123', 'Alpha'] as $search) {
            $filtered = $fetch($search);
            $this->assertSame(2, $filtered['recordsTotal']);
            $this->assertSame(1, $filtered['recordsFiltered']);
            $this->assertSame('finance@example.test', $filtered['data'][0]['email']);
        }

        $secondPage = $fetch('', 1);
        $this->assertSame('Zed Beta', $secondPage['data'][0]['administrator']);
        $this->assertSame('N/A', $secondPage['data'][0]['email']);
        $this->assertStringContainsString('Staff', $secondPage['data'][0]['roles']);
        $this->assertSame(0, $fetch('missing')['recordsFiltered']);
    }

    public function test_access_middleware_is_preserved(): void
    {
        $routes = app('router')->getRoutes();
        foreach (self::resourceRoutes() as [$name]) {
            $middleware = $routes->getByName($name)->middleware();
            if ($name === 'instructors.show') {
                $this->assertNotContains('auth', $middleware);
            } else {
                $this->assertContains('auth', $middleware, $name);
            }
        }
        foreach (['invoices.index', 'invoices.show'] as $name) {
            $this->assertContains('permission:view invoices', $routes->getByName($name)->gatherMiddleware());
        }
        $this->assertContains('permission:view instructors', $routes->getByName('instructors.show')->gatherMiddleware());
        $this->assertContains('permission:edit administrators', $routes->getByName('administrators.update')->gatherMiddleware());
        $this->assertContains('permission:view expenses', $routes->getByName('expenses.show')->gatherMiddleware());
    }

    public function test_guests_cannot_write_canonical_resources(): void
    {
        foreach (['invoices', 'payments', 'instructors', 'administrators', 'expenses', 'instructor-payments'] as $resource) {
            $this->postJson("/$resource")->assertUnauthorized();
        }
    }

    public function test_legacy_named_urls_remain_available(): void
    {
        foreach ([
            'addinvoices' => ['/addinvoice/123', 123],
            'store-invoice' => ['/store-invoice', []],
            'invoice-update' => ['/invoice-update', []],
            'add-payment' => ['/add-payment', []],
            'delete-payment' => ['/delete-payment/123', 123],
            'viewinstructor' => ['/viewinstructor/123', 123],
            'updateinstructor' => ['/updateinstructor', []],
            'viewadministrator' => ['/viewadministrator', []],
            'updateadministrator' => ['/updateadministrator', []],
        ] as $name => [$path, $parameters]) {
            $this->assertSame($path, route($name, $parameters, false));
        }
    }

    public function test_invoice_create_validates_the_canonical_student_query(): void
    {
        $this->expectException(ValidationException::class);
        app(InvoiceController::class)->create(Request::create('/invoices/create'));
    }

    /** @dataProvider invalidStudents */
    public function test_invoice_create_rejects_invalid_or_unknown_students(string $student): void
    {
        $this->expectException(ValidationException::class);
        app(InvoiceController::class)->create(Request::create('/invoices/create', 'GET', ['student' => $student]));
    }

    public static function invalidStudents(): array
    {
        return [
            'invalid UUID' => ['not-a-student'],
            'missing student' => ['f2638868-b1d0-4c92-b71d-59a1f01e6e22'],
        ];
    }

    public function test_invoice_create_supports_both_query_and_legacy_student_argument(): void
    {
        $id = 'f2638868-b1d0-4c92-b71d-59a1f01e6e22';
        DB::table('students')->insert(['id' => $id]);
        $controller = app(InvoiceController::class);
        $canonical = $controller->create(Request::create('/invoices/create', 'GET', ['student' => $id]));
        $legacy = $controller->create(Request::create("/addinvoice/$id", 'GET', ['student' => 'ignored']), $id);

        $this->assertSame($id, $canonical->getData()['student']->id);
        $this->assertSame($id, $legacy->getData()['student']->id);
    }

    /** @dataProvider updateIdentities */
    public function test_url_update_identity_overrides_conflicting_body_identity(string $controllerClass, string $modelClass, string $requestClass, string $key): void
    {
        $model = new $modelClass;
        $model->id = 123;
        $model->exists = true;
        if ($model instanceof Instructor) {
            $user = new User;
            $user->id = 1;
            $model->setRelation('user', $user);
        }
        $request = $requestClass::create('/resource/123', 'PUT', [$key => 456]);
        $controller = Mockery::mock($controllerClass)->makePartial();
        $controller->shouldReceive('validate')->once()->andThrow(new \RuntimeException('stop before persistence'));

        try {
            $controller->update($request, $model);
        } catch (\RuntimeException $exception) {
            $this->assertSame('stop before persistence', $exception->getMessage());
        }

        $this->assertSame('123', (string) $request->input($key));
    }

    public static function updateIdentities(): array
    {
        return [
            'invoice' => [InvoiceController::class, Invoice::class, UpdateInvoiceRequest::class, 'invoice_id'],
            'instructor' => [InstructorController::class, Instructor::class, UpdateInstructorRequest::class, 'instructor_id'],
            'administrator' => [AdministratorController::class, Administrator::class, UpdateAdministratorRequest::class, 'administrator_id'],
            'expense' => [ExpenseController::class, Expense::class, UpdateexpenseRequest::class, 'expenseId'],
        ];
    }

    /** @dataProvider updateIdentities */
    public function test_legacy_updates_resolve_the_body_identifier(string $controllerClass, string $modelClass, string $requestClass, string $key): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $request = $requestClass::create('/legacy-update', 'POST', [$key => 'missing-body-target']);

        app($controllerClass)->update($request, new $modelClass);
    }

    public function test_expense_html_show_does_not_replace_the_legacy_data_action(): void
    {
        $expense = new Expense;
        $expense->id = 'expense-id';

        $view = app(ExpenseResourceController::class)->show($expense);
        $this->assertSame('expenses.viewExpense', $view->name());
        $this->assertSame($expense, $view->getData()['expense']);
        $this->assertNull(app(ExpenseController::class)->show($expense));
    }

    public function test_canonical_model_binding_uses_url_parameters_not_request_body(): void
    {
        foreach ([
            'invoices' => ['invoice', Invoice::class, 'invoice_id'],
            'instructors' => ['instructor', Instructor::class, 'instructor_id'],
            'administrators' => ['administrator', Administrator::class, 'administrator_id'],
            'expenses' => ['expense', Expense::class, 'expenseId'],
        ] as $resource => [$parameter, $modelClass, $bodyKey]) {
            $id = in_array($resource, ['instructors', 'expenses'])
                ? 'f2638868-b1d0-4c92-b71d-59a1f01e6e22'
                : '123';
            DB::table($resource)->insert(['id' => $id]);
            $request = Request::create("/$resource/$id", 'PUT', [$bodyKey => 456]);
            $route = app('router')->getRoutes()->match($request);
            app('router')->substituteImplicitBindings($route);

            $this->assertInstanceOf($modelClass, $route->parameter($parameter));
            $this->assertSame($id, (string) $route->parameter($parameter)->id);
        }

        $route = app('router')->getRoutes()->match(Request::create('/invoices/123', 'DELETE'));
        app('router')->substituteImplicitBindings($route);
        $this->assertInstanceOf(Invoice::class, $route->parameter('invoice'));
    }
}
