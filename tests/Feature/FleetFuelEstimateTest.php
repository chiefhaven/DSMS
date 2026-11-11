<?php

namespace Tests\Feature;

use App\Models\Fleet;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FleetFuelEstimateTest extends TestCase
{
    public function test_details_render_estimate_and_missing_data_explanations(): void
    {
        config(['database.connections.fuel_estimate_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::setDefaultConnection('fuel_estimate_test');
        Schema::create('students', function (Blueprint $table) {
            $table->integer('fleet_id');
            $table->string('status');
            $table->softDeletes();
        });
        $fleet = new Fleet;
        $fleet->id = 12;
        $fleet->car_brand_model = 'Test vehicle';
        $fleet->engine_capacity_cc = 1500;
        $fleet->fuel_type = 'Petrol';
        $fleet->make = 'Isuzu';
        $fleet->model = 'Elf';
        $fleet->manufacture_year = 2014;
        $fleet->insurance_provider = 'Prime Insurance';
        $fleet->insurance_expiry = '2027-01-31';
        $fleet->odometer_km = 0;
        $fleet->next_service_km = 125000;
        $fleet->vin = '<script>invalid</script>';
        $fleet->colour = '';
        $fleet->setRelation('instructor', null);
        $template = str_replace(
            ["@extends('layouts.backend')", "@section('content')", "@section('css_after')", '@endsection'],
            '',
            file_get_contents(resource_path('views/fleet/viewfleet.blade.php'))
        );
        $html = Blade::render($template, ['fleet' => $fleet]);
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new \DOMXPath($document);
        $this->assertSame(3, $xpath->query('//*[@id="vehicle-specifications"]/div/div/section')->length);
        $this->assertSame(16, $xpath->query('//*[@id="vehicle-specifications"]//dt')->length);
        $this->assertSame(16, $xpath->query('//*[@id="vehicle-specifications"]//dd')->length);
        foreach (['Vehicle', 'Engine & drive', 'Insurance & service'] as $heading) {
            $this->assertSame(1, $xpath->query('//*[@id="vehicle-specifications"]//h3[normalize-space(.)="'.$heading.'"]')->length);
        }
        foreach ([
            'Make' => 'Isuzu', 'Model' => 'Elf', 'Year' => '2014', 'Engine (cc)' => '1,500',
            'Insurer' => 'Prime Insurance', 'Insurance expiry' => '31 Jan 2027',
            'Odometer (km)' => '0', 'Next service (km)' => '125,000',
            'Colour' => '-', 'Engine number' => '-', 'VIN / chassis' => '<script>invalid</script>',
        ] as $label => $value) {
            $node = $xpath->query('//*[@id="vehicle-specifications"]//dt[normalize-space(.)="'.$label.'"]/following-sibling::dd')->item(0);
            $this->assertNotNull($node, $label);
            $this->assertSame($value, trim($node->textContent), $label);
        }
        $this->assertSame(0, $xpath->query('//*[@id="vehicle-specifications"]//script')->length);
        $this->assertSame(3, $xpath->query('//*[@id="vehicle-metrics"]/div[contains(@class, "col-md-4")]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="vehicle-metrics"]/div/*[@id="fuel-estimate"]')->length);
        $this->assertSame(4, $xpath->query('//select[@id="tracking-period"]/option')->length);
        $this->assertSame(1, $xpath->query('//*[@id="tracking-period"]/ancestor::div[contains(@class, "col-lg-8")]')->length);
        $this->assertSame(3, $xpath->query('//*[@id="route-playback"]//button[@type="button"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="vehicleRouteMap"]/parent::div/following-sibling::div[1][@id="route-playback"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="playback-position" and @type="range"]')->length);
        $this->assertSame(5, $xpath->query('//select[@id="playback-speed"]/option')->length);
        $this->assertStringContainsString('const playbackSpeed = ref(200);', $html);
        $this->assertStringContainsString("'--speed-gradient': playbackGradient", $html);
        $this->assertStringContainsString('::-webkit-slider-runnable-track', $html);
        $this->assertStringContainsString('::-moz-range-track', $html);
        $this->assertStringNotContainsString('Green: slow', $html);
        $this->assertStringNotContainsString('Red: above 40 km/h', $html);
        $this->assertStringNotContainsString('Select a route with at least two timed points.', $html);
        $this->assertStringContainsString('speed > 40 ? 0', $html);
        $this->assertStringContainsString("timeZone: 'Africa/Blantyre'", $html);
        $this->assertStringContainsString('if (playbackMode.value) return;', $html);
        $this->assertStringContainsString('Vue.onUnmounted', $html);
        $this->assertStringContainsString('Fuel usage', $html);
        $this->assertStringContainsString('const estimatedRate = 7', $html);
        $this->assertSame(0, $xpath->query('//*[@id="fuel-estimate"]//input | //*[@id="fuel-estimate"]//details')->length);
        $this->assertStringContainsString('estimatedFuelUsage.toFixed(2)', $html);
        $this->assertStringNotContainsString('L/100 km', $html);
        $this->assertStringContainsString('trackedDistanceKm.value * estimatedRate / 100', $html);
        $fleet->engine_capacity_reference = 'Current model reference, not verified';
        $html = Blade::render($template, ['fleet' => $fleet]);
        $this->assertStringNotContainsString('Engine size is a model reference, not verified.', $html);
        $this->assertStringNotContainsString('Reference estimate', $html);
        $this->assertStringNotContainsString('Current model reference, not verified', $html);

        $fleet->engine_capacity_cc = null;
        $html = Blade::render($template, ['fleet' => $fleet]);
        $this->assertStringContainsString('Unavailable', $html);
        $this->assertStringNotContainsString('id="fuel-price"', $html);
        $fleet->fuel_type = 'Electric';
        $this->assertStringContainsString('Unavailable', Blade::render($template, ['fleet' => $fleet]));
    }

    public function test_capacity_and_fuel_type_determine_the_planning_rate(): void
    {
        $fleet = new Fleet;
        $fleet->engine_capacity_cc = 1500;
        foreach (['Petrol' => 7.0, 'Diesel' => 5.95, 'Hybrid' => 4.9, 'LPG' => 8.4] as $fuel => $rate) {
            $fleet->fuel_type = $fuel;
            $this->assertSame($rate, $fleet->estimatedFuelLitresPer100Km(), $fuel);
        }
        $fleet->engine_capacity_cc = 2000;
        $fleet->fuel_type = 'Petrol';
        $this->assertSame(8.0, $fleet->estimatedFuelLitresPer100Km());
    }

    public function test_missing_details_and_unsupported_fuels_do_not_produce_estimates(): void
    {
        $fleet = new Fleet;
        $fleet->fuel_type = 'Petrol';
        foreach ([null, 0, -1] as $capacity) {
            $fleet->engine_capacity_cc = $capacity;
            $this->assertNull($fleet->estimatedFuelLitresPer100Km());
        }
        $fleet->engine_capacity_cc = 1500;
        foreach ([null, 'Electric', 'Other', 'Unknown'] as $fuel) {
            $fleet->fuel_type = $fuel;
            $this->assertNull($fleet->estimatedFuelLitresPer100Km());
        }
    }
}
