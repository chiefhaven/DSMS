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
            $this->assertContains('role:superAdmin|Super-Admin|admin|financeAdmin', $routes->getByName($name)->middleware());
        }
        $this->assertContains('role:superAdmin|admin', $routes->getByName('instructors.show')->gatherMiddleware());
        $this->assertContains('role:superAdmin|admin', $routes->getByName('administrators.update')->gatherMiddleware());
        $this->assertContains('role:superAdmin|admin|financeAdmin', $routes->getByName('expenses.show')->gatherMiddleware());
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
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->with('superAdmin')->andReturnTrue();
        Auth::shouldReceive('user')->andReturn($user);

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
