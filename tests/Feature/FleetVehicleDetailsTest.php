<?php

namespace Tests\Feature;

use App\Http\Controllers\FleetController;
use App\Http\Requests\StoreFleetRequest;
use App\Http\Requests\UpdateFleetRequest;
use App\Models\Fleet;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FleetVehicleDetailsTest extends TestCase
{
    public function test_insurers_are_restricted_with_prime_default_and_legacy_edit_support(): void
    {
        $request = new StoreFleetRequest;
        $payload = ['make' => 'Isuzu', 'model' => 'Elf', 'engine_capacity_cc' => 2999];
        $this->assertSame('Prime Insurance', (new Fleet)->insurance_provider);
        foreach (Fleet::INSURERS as $insurer) {
            $this->assertTrue(Validator::make($payload + ['insurance_provider' => $insurer], $request->vehicleDetailsRules())->passes());
        }
        $fleet = new Fleet;
        $fleet->insurance_provider = 'Legacy insurer';
        $legacy = $payload + ['insurance_provider' => 'Legacy insurer'];
        $this->assertFalse(Validator::make($legacy, $request->vehicleDetailsRules())->passes());
        $this->assertTrue(Validator::make($legacy, $request->vehicleDetailsRules($fleet))->passes());
        $this->assertFalse(Validator::make($payload + ['insurance_provider' => 'Unlisted insurer'], $request->vehicleDetailsRules($fleet))->passes());
        $hydrated = (new Fleet)->newFromBuilder(['id' => 1, 'insurance_provider' => null]);
        $this->assertNull($hydrated->insurance_provider);
    }

    public function test_make_model_are_required_and_legacy_combined_names_are_supported(): void
    {
        $rules = (new StoreFleetRequest)->vehicleDetailsRules();
        $this->assertEquals($rules, (new UpdateFleetRequest)->vehicleDetailsRules());
        $this->assertTrue(Validator::make(['make' => 'Toyota', 'model' => 'Vitz', 'engine_capacity_cc' => 1500], $rules)->passes());
        $this->assertFalse(Validator::make(['make' => 'Toyota'], $rules)->passes());
        $this->assertFalse(Validator::make([], $rules)->passes());
        $this->assertTrue(Validator::make(['car_brand_model' => 'Toyota Vitz', 'engine_capacity_cc' => 1500], $rules)->passes());
        $missing = Validator::make(['make' => 'Toyota', 'model' => 'Vitz'], $rules);
        $this->assertTrue($missing->fails());
        $this->assertTrue($missing->errors()->has('engine_capacity_cc'));
    }

    public function test_optional_fields_validate_boundaries_and_options(): void
    {
        $valid = [
            'make' => 'Toyota', 'model' => 'Vitz',
            'manufacture_year' => 2018, 'vin' => 'CHASSIS-123', 'engine_number' => 'ENGINE-123',
            'engine_capacity_cc' => 1500,
            'fuel_type' => 'Petrol', 'transmission' => 'Automatic', 'colour' => 'Blue',
            'odometer_km' => 0, 'seating_capacity' => 5,
            'insurance_provider' => 'Prime Insurance', 'insurance_expiry' => '2027-01-31',
            'roadworthiness_expiry' => '2027-02-28', 'service_date' => '2026-10-15',
            'next_service_km' => 120000,
        ];
        $rules = (new StoreFleetRequest)->vehicleDetailsRules();
        $this->assertTrue(Validator::make($valid, $rules)->passes());
        foreach ([null, '', 0, -1, 1.5, 4294967296, 'invalid'] as $capacity) {
            $this->assertFalse(Validator::make(array_replace($valid, ['engine_capacity_cc' => $capacity]), $rules)->passes());
        }
        foreach ([
            'make' => str_repeat('A', 96), 'model' => str_repeat('A', 96),
            'manufacture_year' => date('Y') + 2, 'fuel_type' => 'Unknown', 'transmission' => 'Unknown',
            'insurance_provider' => 'Unknown insurer',
            'odometer_km' => -1, 'seating_capacity' => 0, 'insurance_expiry' => '2027-02-30',
            'roadworthiness_expiry' => 'invalid', 'service_date' => 'invalid', 'next_service_km' => 1.5,
        ] as $field => $invalid) {
            $validator = Validator::make(array_replace($valid, [$field => $invalid]), $rules);
            $this->assertFalse($validator->passes(), $field);
            $this->assertTrue($validator->errors()->has($field), $field);
        }
    }

    public function test_details_persist_and_legacy_updates_preserve_optional_values(): void
    {
        config(['database.connections.fleet_details_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::setDefaultConnection('fleet_details_test');
        config(['activitylog.enabled' => false]);
        Schema::create('fleets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('car_brand_model');
            $table->string('car_registration_number');
            $table->unsignedInteger('licence_class_id');
            $table->string('instructor_id')->nullable();
            $table->string('fleet_image')->nullable();
            $table->string('tracker_device_id')->nullable();
            $table->text('car_description')->nullable();
            $table->timestamps();
        });
        Schema::create('instructors', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('status');
            $table->softDeletes();
        });
        Schema::create('licence_classes', function (Blueprint $table) {
            $table->increments('id');
        });
        DB::table('instructors')->insert(['id' => 'test-instructor', 'status' => 'Active']);
        DB::table('licence_classes')->insert(['id' => 1]);
        $migration = require base_path('database/migrations/2026_11_11_004500_add_vehicle_details_to_fleets.php');
        $migration->up();
        $capacityMigration = require base_path('database/migrations/2026_11_11_005700_add_engine_capacity_to_fleets.php');
        $capacityMigration->up();
        $referenceMigration = require base_path('database/migrations/2026_11_11_012500_add_engine_capacity_reference_to_fleets.php');
        $referenceMigration->up();

        $controller = app(FleetController::class);
        $payload = [
            'make' => 'Toyota', 'model' => 'Vitz', 'fuel_type' => 'Petrol', 'odometer_km' => 0,
            'insurance_expiry' => '2027-01-31', 'next_service_km' => 5000,
            'manufacture_year' => 2018, 'vin' => 'CHASSIS-123', 'engine_number' => 'ENGINE-123',
            'engine_capacity_cc' => 1500,
            'transmission' => 'Automatic', 'colour' => 'Blue', 'seating_capacity' => 5,
            'insurance_provider' => 'Prime Insurance',
            'roadworthiness_expiry' => '2027-02-28', 'service_date' => '2026-10-15',
            'reg_number' => 'TEST-123', 'licenceClass' => 1, 'instructor' => 'test-instructor',
        ];
        $request = StoreFleetRequest::create('/fleet', 'POST', $payload);
        $request->setValidator(Validator::make($payload, $request->rules()));
        $response = $controller->store($request);
        $this->assertSame(route('fleet.index'), $response->getTargetUrl());
        $saved = Fleet::firstOrFail();
        $id = $saved->id;
        $this->assertSame('Toyota Vitz', $saved->car_brand_model);
        $this->assertSame('Toyota', $saved->make);
        $this->assertSame(0, $saved->odometer_km);
        $this->assertSame('2027-01-31', $saved->insurance_expiry->format('Y-m-d'));
        foreach ((new StoreFleetRequest)->vehicleDetailsRules() as $field => $rule) {
            $value = $saved->{$field};
            $this->assertSame(
                $payload[$field],
                $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value,
                $field
            );
        }

        DB::table('fleets')->where('id', $id)->update(['engine_capacity_reference' => 'Example base model reference']);
        $saved->refresh();
        $legacy = UpdateFleetRequest::create('/fleet/'.$id, 'PUT', [
            'car_brand_model' => 'Legacy name', 'reg_number' => 'TEST-123',
            'engine_capacity_cc' => 1500,
            'licenceClass' => 1, 'instructor' => 'test-instructor',
        ]);
        $this->assertSame(route('fleet.index'), $controller->update($legacy, $saved)->getTargetUrl());
        $saved->refresh();
        $this->assertSame('Legacy name', $saved->car_brand_model);
        $this->assertSame(1500, $saved->engine_capacity_cc);
        $this->assertSame('Example base model reference', $saved->engine_capacity_reference);
        $this->assertSame('Petrol', $saved->fuel_type);
        $this->assertSame(5000, $saved->next_service_km);
        $update = UpdateFleetRequest::create('/fleet/'.$id, 'PUT', [
            'make' => 'Honda', 'model' => 'Fit', 'insurance_expiry' => null,
            'engine_capacity_cc' => 1300,
            'reg_number' => 'TEST-123', 'licenceClass' => 1, 'instructor' => 'test-instructor',
        ]);
        $this->assertSame(route('fleet.index'), $controller->update($update, $saved)->getTargetUrl());
        $updated = Fleet::findOrFail($id);
        $this->assertSame('Honda Fit', $updated->car_brand_model);
        $this->assertNull($updated->insurance_expiry);
        $this->assertSame(1300, $updated->engine_capacity_cc);
        $this->assertNull($updated->engine_capacity_reference);
        $this->assertSame(5000, $updated->next_service_km);
        $referenceMigration->down();
        $this->assertFalse(Schema::hasColumn('fleets', 'engine_capacity_reference'));
        $capacityMigration->down();
        $this->assertFalse(Schema::hasColumn('fleets', 'engine_capacity_cc'));
        $migration->down();
        $this->assertFalse(Schema::hasColumn('fleets', 'make'));
    }
}
