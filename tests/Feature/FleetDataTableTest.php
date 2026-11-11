<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FleetDataTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.fleet_table_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::setDefaultConnection('fleet_table_test');
        $this->withoutMiddleware();
        Schema::create('fleets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('car_brand_model');
            $table->string('car_registration_number');
            $table->string('instructor_id')->nullable();
            $table->unsignedInteger('licence_class_id')->nullable();
            $table->string('fleet_image')->nullable();
            $table->text('car_description')->nullable();
        });
        (require database_path('migrations/2026_11_11_004500_add_vehicle_details_to_fleets.php'))->up();
        (require database_path('migrations/2026_11_11_005700_add_engine_capacity_to_fleets.php'))->up();
        Schema::create('instructors', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fname');
            $table->string('sname');
            $table->softDeletes();
        });
        Schema::create('licence_classes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('class');
        });
        Schema::create('students', function (Blueprint $table) {
            $table->integer('fleet_id');
            $table->string('status');
            $table->softDeletes();
        });
        DB::table('instructors')->insert([
            ['id' => 'a', 'fname' => 'Alice', 'sname' => 'Jones'],
            ['id' => 'z', 'fname' => 'Zoe', 'sname' => 'Smith'],
        ]);
        DB::table('licence_classes')->insert(['id' => 1, 'class' => 'C1']);
        DB::table('fleets')->insert([
            ['id' => 1, 'car_brand_model' => 'Alpha', 'car_registration_number' => 'REG1', 'instructor_id' => 'z',
                'licence_class_id' => 1, 'engine_capacity_cc' => 10000, 'insurance_expiry' => '2026-12-01', 'car_description' => '<script>invalid</script>'],
            ['id' => 2, 'car_brand_model' => 'Beta', 'car_registration_number' => 'REG2', 'instructor_id' => 'a',
                'licence_class_id' => 1, 'engine_capacity_cc' => 2000, 'insurance_expiry' => '2026-12-02', 'car_description' => null],
            ['id' => 3, 'car_brand_model' => 'Gamma', 'car_registration_number' => 'REG3', 'instructor_id' => null,
                'licence_class_id' => null, 'engine_capacity_cc' => 900, 'insurance_expiry' => null, 'car_description' => null],
        ]);
        DB::table('students')->insert([
            ['fleet_id' => 1, 'status' => 'Active', 'deleted_at' => null],
            ['fleet_id' => 1, 'status' => 'Finished', 'deleted_at' => null],
            ['fleet_id' => 1, 'status' => 'Active', 'deleted_at' => '2026-11-01'],
            ['fleet_id' => 2, 'status' => 'Active', 'deleted_at' => null],
            ['fleet_id' => 2, 'status' => 'Active', 'deleted_at' => null],
        ]);
    }

    private function fetch(array $overrides = []): array
    {
        $this->app->forgetInstance('datatables.request');
        $names = [
            'car_brand_model', 'car_registration_number', 'instructor_name', 'active_students_count',
            'insurance_expiry', 'roadworthiness_expiry', 'odometer_km', 'service_date',
            'next_service_km', 'insurance_provider', 'manufacture_year', 'engine_capacity_cc',
            'fuel_type', 'transmission', 'licenceClass.class', 'actions',
        ];
        $data = [
            'draw' => 7, 'start' => 0, 'length' => 2,
            'search' => ['value' => '', 'regex' => false],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => array_map(fn ($name) => [
                'data' => ['car_brand_model' => 'vehicle', 'licenceClass.class' => 'licence_class'][$name] ?? $name,
                'name' => $name, 'searchable' => in_array($name, ['active_students_count', 'actions'], true) ? 'false' : 'true',
                'orderable' => $name === 'actions' ? 'false' : 'true', 'search' => ['value' => '', 'regex' => false],
            ], $names),
        ];
        $response = $this->getJson('/fleet?'.http_build_query(array_replace($data, $overrides)), [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()->json();
        $this->assertArrayNotHasKey('error', $response, json_encode($response));
        return $response;
    }

    public function test_database_pagination_counts_rendering_and_expiry_warnings(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-11 14:00:00', date_default_timezone_get()));
        $page = $this->fetch();
        $this->assertSame(7, $page['draw']);
        $this->assertSame(3, $page['recordsTotal']);
        $this->assertSame(3, $page['recordsFiltered']);
        $this->assertCount(2, $page['data']);
        $this->assertSame([1, 2], array_column($page['data'], 'id'));
        $this->assertSame([1, 2], array_column($page['data'], 'active_students_count'));
        $this->assertStringContainsString('text-danger', $page['data'][0]['insurance_expiry']);
        $this->assertStringNotContainsString('text-danger', $page['data'][1]['insurance_expiry']);
        $this->assertStringContainsString('&lt;script&gt;', $page['data'][0]['vehicle']);
        $this->assertStringContainsString(route('fleet.edit', 1), $page['data'][0]['actions']);
        $last = $this->fetch(['start' => 2]);
        $this->assertSame([3], array_column($last['data'], 'id'));
        $this->assertStringContainsString('Not assigned', $last['data'][0]['instructor_name']);
        $this->travelBack();
    }

    public function test_search_and_numeric_date_relationship_sorting_run_on_database(): void
    {
        foreach (['REG2' => 2, 'Zoe Smith' => 1] as $term => $id) {
            $page = $this->fetch(['search' => ['value' => $term, 'regex' => false]]);
            $this->assertSame(3, $page['recordsTotal']);
            $this->assertSame(1, $page['recordsFiltered']);
            $this->assertSame([$id], array_column($page['data'], 'id'));
        }
        foreach ([11 => [3, 2, 1], 3 => [3, 1, 2], 4 => [3, 1, 2], 2 => [3, 2, 1]] as $column => $ids) {
            $page = $this->fetch(['length' => 10, 'order' => [['column' => $column, 'dir' => 'asc']]]);
            $this->assertSame($ids, array_column($page['data'], 'id'), (string) $column);
        }
        $page = $this->fetch(['search' => ['value' => 'C1', 'regex' => false]]);
        $this->assertSame(2, $page['recordsFiltered']);
        $page = $this->fetch(['search' => ['value' => 'does-not-exist', 'regex' => false]]);
        $this->assertSame(0, $page['recordsFiltered']);
        $this->assertSame([], $page['data']);
    }
}
