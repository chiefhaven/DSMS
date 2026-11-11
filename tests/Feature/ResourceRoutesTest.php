<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ResourceRoutesTest extends TestCase
{
    public function test_student_crud_routes_and_legacy_urls_resolve(): void
    {
        foreach ([
            ['GET', '/students', 'students.index', 'index'],
            ['GET', '/students/create', 'students.create', 'create'],
            ['POST', '/students', 'students.store', 'store'],
            ['GET', '/students/example', 'students.show', 'show'],
            ['GET', '/students/example/edit', 'students.edit', 'edit'],
            ['PUT', '/students/example', 'students.update', 'update'],
            ['PATCH', '/students/example', 'students.update', 'update'],
            ['DELETE', '/students/example', 'students.destroy', 'destroy'],
            ['GET', '/viewstudent/example', 'viewStudent', 'show'],
            ['GET', '/addstudent', 'addstudent', 'create'],
            ['POST', '/storestudent', 'storestudents', 'store'],
            ['POST', '/student-update', null, 'update'],
        ] as [$method, $url, $name, $action]) {
            $route = Route::getRoutes()->match(Request::create($url, $method));
            if ($name !== null) {
                $this->assertSame($name, $route->getName());
            }
            $this->assertSame($action, $route->getActionMethod());
            $this->assertContains('auth', $route->gatherMiddleware());
        }
    }

    public function test_operations_use_resource_verbs_without_exposing_stub_actions(): void
    {
        foreach ([
            ['POST', '/attendances', 'attendances.store'],
            ['PUT', '/attendances/example', 'attendances.update'],
            ['DELETE', '/attendances/example', 'attendances.destroy'],
            ['GET', '/bulk-attendances/create', 'bulk-attendances.create'],
            ['GET', '/bulk-attendances/example/edit', 'bulk-attendances.edit'],
            ['POST', '/api/bulk-attendances', 'api.bulk-attendances.store'],
            ['PATCH', '/api/bulk-attendances/example', 'api.bulk-attendances.update'],
            ['DELETE', '/api/bulk-attendances/example', 'api.bulk-attendances.destroy'],
            ['GET', '/schedules', 'schedules.index'],
            ['POST', '/schedules', 'schedules.store'],
            ['PUT', '/schedules/example', 'schedules.update'],
            ['DELETE', '/schedules/example', 'schedules.destroy'],
            ['PUT', '/settings', 'settings.update'],
            ['GET', '/settings/edit', 'settings.edit'],
            ['PUT', '/invoice-settings', 'invoice-settings.update'],
            ['POST', '/api/expense-types', 'api.expense-types.store'],
        ] as [$method, $url, $name]) {
            $route = Route::getRoutes()->match(Request::create($url, $method));
            $this->assertSame($name, $route->getName());
            $this->assertTrue(
                in_array('auth', $route->gatherMiddleware(), true)
                || in_array('auth:sanctum', $route->gatherMiddleware(), true)
            );
        }

        $this->assertFalse(Route::has('attendances.show'));
        $this->assertFalse(Route::has('attendances.edit'));
        $this->assertFalse(Route::has('bulk-attendances.show'));
        $this->assertFalse(Route::has('schedules.show'));
    }

    public function test_student_resource_routes_require_login(): void
    {
        $this->get('/students/create')->assertRedirect(route('login'));
        $this->put('/students/example', [])->assertRedirect(route('login'));
    }

    public function test_route_names_are_unique_and_password_urls_remain_available(): void
    {
        $names = [];
        foreach (Route::getRoutes() as $route) {
            if ($route->getName() !== null) {
                $this->assertArrayNotHasKey($route->getName(), $names, $route->uri());
                $names[$route->getName()] = true;
            }
        }
        $this->assertSame('password.request', Route::getRoutes()->match(Request::create('/password/reset'))->getName());
        $this->assertSame('session.password.request', Route::getRoutes()->match(Request::create('/forgot-password'))->getName());
    }
}
