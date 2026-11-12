<?php

namespace Tests\Feature;

use App\Models\Fleet;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleLocationPermissionsTest extends TestCase
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
        (require base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();
        foreach (['superAdmin', 'Super-Admin', 'admin', 'instructor', 'student'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }
        (require base_path('database/migrations/2026_11_11_120000_seed_access_permissions.php'))->up();
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('instructor_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('fleets', function (Blueprint $table) {
            $table->id();
            $table->string('instructor_id')->nullable();
            $table->integer('tracker_device_id')->nullable();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->string('fleet_id')->nullable();
            $table->string('status');
            $table->softDeletes();
        });
        Schema::create('vehicle_trackers', function (Blueprint $table) {
            $table->id();
            $table->integer('fleet_id');
            $table->string('user_id');
            $table->double('latitude');
            $table->double('longitude');
            $table->timestamps();
        });
        (require base_path('database/migrations/2026_11_12_001800_seed_vehicle_location_permissions.php'))->up();
    }

    public function test_viewing_routes_require_the_view_permission(): void
    {
        $paths = [
            '/track-fleet', '/get-all-vehicle-locations', '/show-vehicle-geo-data/1',
            '/api/get-all-vehicle-locations', '/api/show-vehicle-geo-data/1',
            '/api/mbirasync/positions', '/api/mbirasync/positions/live',
            '/api/mbirasync/positions/1/history',
        ];
        $role = Role::create(['name' => 'location-viewer', 'guard_name' => 'web']);
        $this->authenticate($role);
        foreach ($paths as $path) {
            $this->getJson($path)->assertForbidden();
        }
        $role->givePermissionTo('view vehicle locations');
        $this->authenticate($role);
        $this->get('/track-fleet')->assertOk()->assertSee('Fleet location');
        foreach ($paths as $path) {
            $route = app('router')->getRoutes()->match(Request::create($path, 'GET'));
            $action = $route->getAction();
            unset($action['controller']);
            $action['uses'] = static fn () => response()->noContent();
            $route->setAction($action);
            $this->getJson($path)->assertNoContent();
        }
        $this->assertStringContainsString('Track fleet', view('layouts.partials.main_nav')->render());
        $this->postJson('/api/save-vehicle-location', ['latitude' => -14, 'longitude' => 34])->assertForbidden();
    }

    public function test_recording_is_limited_to_authorized_instructors_and_their_assigned_vehicle(): void
    {
        DB::table('fleets')->insert(['id' => 12, 'instructor_id' => 'instructor-one']);
        $role = Role::findByName('instructor', 'web');
        $user = $this->authenticate($role);
        $instructor = new Instructor();
        $instructor->id = 'instructor-one';
        $user->setRelation('instructor', $instructor);
        $this->assertStringContainsString('/api/save-vehicle-location', view('layouts.partials.main_nav')->render());
        $this->postJson('/api/save-vehicle-location', ['latitude' => -14, 'longitude' => 34])->assertCreated();
        $this->assertDatabaseHas('vehicle_trackers', [
            'fleet_id' => 12, 'user_id' => $user->id, 'latitude' => -14, 'longitude' => 34,
        ]);
        $this->getJson('/api/get-all-vehicle-locations')->assertForbidden();

        $role->revokePermissionTo('record vehicle locations');
        $this->authenticate($role)->setRelation('instructor', $instructor);
        $this->postJson('/api/save-vehicle-location', ['latitude' => -14, 'longitude' => 34])->assertForbidden();
        $this->assertStringNotContainsString('/api/save-vehicle-location', view('layouts.partials.main_nav')->render());
        $this->assertDatabaseCount('vehicle_trackers', 1);

        $role->givePermissionTo('record vehicle locations');
        $this->authenticate($role)->setRelation('instructor', null);
        $this->postJson('/api/save-vehicle-location', ['latitude' => -14, 'longitude' => 34])->assertForbidden();

        $instructor->id = 'unassigned';
        $this->authenticate($role)->setRelation('instructor', $instructor);
        $this->postJson('/api/save-vehicle-location', ['latitude' => -14, 'longitude' => 34])->assertNotFound();
    }

    public function test_guests_cannot_view_or_record_locations(): void
    {
        $this->getJson('/track-fleet')->assertUnauthorized();
        $this->getJson('/api/mbirasync/positions')->assertUnauthorized();
        $this->postJson('/api/save-vehicle-location', ['latitude' => -14, 'longitude' => 34])->assertUnauthorized();
    }

    public function test_tracking_panel_and_script_require_view_permission(): void
    {
        $role = Role::create(['name' => 'fleet-reader', 'guard_name' => 'web']);
        $this->authenticate($role);
        $fleet = new Fleet();
        $fleet->id = 12;
        $fleet->car_brand_model = 'Vehicle';
        $fleet->setRelation('instructor', null);
        $fleet->setRelation('licenceClass', null);
        $template = str_replace(
            ["@extends('layouts.backend')", "@section('content')", "@section('css_after')", '@endsection'],
            '',
            file_get_contents(resource_path('views/fleet/viewfleet.blade.php'))
        );
        $html = Blade::render($template, ['fleet' => $fleet]);
        $this->assertStringContainsString('Vehicle details', $html);
        $this->assertStringNotContainsString('id="vehicleRouteMap"', $html);
        $this->assertStringNotContainsString('const vehicleRouteApp', $html);
        $this->assertStringNotContainsString('Track fleet', view('layouts.partials.main_nav')->render());

        $role->givePermissionTo('view vehicle locations');
        $this->authenticate($role);
        $html = Blade::render($template, ['fleet' => $fleet]);
        $this->assertStringContainsString('id="vehicleRouteMap"', $html);
        $this->assertStringContainsString('const vehicleRouteApp', $html);
    }

    public function test_migration_preserves_legacy_grants_and_instructor_profiles_idempotently(): void
    {
        $role = Role::create(['name' => 'legacy-manager', 'guard_name' => 'web']);
        $role->givePermissionTo('manage administration');
        DB::table('users')->insert(['id' => 'legacy-instructor', 'instructor_id' => 'instructor-one']);
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => Permission::findByName('manage administration', 'web')->id,
            'model_type' => (new User())->getMorphClass(),
            'model_id' => 'direct-manager',
        ]);
        $migration = require base_path('database/migrations/2026_11_12_001800_seed_vehicle_location_permissions.php');
        $migration->up();
        $migration->up();
        $this->assertTrue($role->fresh()->hasPermissionTo('view vehicle locations'));
        foreach (['direct-manager' => 'view vehicle locations', 'legacy-instructor' => 'record vehicle locations'] as $id => $permission) {
            $this->assertDatabaseHas(config('permission.table_names.model_has_permissions'), [
                'permission_id' => Permission::findByName($permission, 'web')->id,
                'model_type' => (new User())->getMorphClass(),
                'model_id' => $id,
            ]);
        }
    }

    private function authenticate(Role $role): User
    {
        $user = new User(['name' => 'Location test']);
        $user->id = 'location-user';
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('notifications', collect());
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
        return $user;
    }
}
