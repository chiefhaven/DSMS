<?php

namespace Tests\Feature;

use App\Models\Fleet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class FleetFormTest extends TestCase
{
    public function test_insurer_select_defaults_and_preserves_existing_and_old_values(): void
    {
        $this->app['request']->setLaravelSession($this->app['session']->driver());
        foreach ([
            [false, 'Prime Insurance', null, 'Prime Insurance'],
            [true, 'NICO General Insurance', null, 'NICO General Insurance'],
            [true, null, null, ''],
            [true, 'Legacy insurer', null, 'Legacy insurer'],
            [false, 'Prime Insurance', 'Reunion Insurance', 'Reunion Insurance'],
            [false, 'Prime Insurance', '', ''],
        ] as [$editing, $insurer, $oldInsurer, $selected]) {
            $fleet = new Fleet;
            $fleet->id = 17;
            $fleet->insurance_provider = $insurer;
            $this->app['session']->flashInput($oldInsurer === null ? [] : ['insurance_provider' => $oldInsurer]);
            $html = view('fleet.partials.vehicle-form', [
                'editing' => $editing, 'fleet' => $fleet, 'instructors' => collect(),
                'licenceClasses' => collect(), 'errors' => new ViewErrorBag,
            ])->render();
            $document = new \DOMDocument;
            $document->loadHTML($html);
            $xpath = new \DOMXPath($document);
            $this->assertSame(1, $xpath->query('//select[@name="insurance_provider"]')->length);
            $this->assertSame(0, $xpath->query('//input[@name="insurance_provider"]')->length);
            $options = $xpath->query('//select[@name="insurance_provider"]/option[@selected]');
            $this->assertSame($selected === '' ? 0 : 1, $options->length);
            if ($selected !== '') {
                $this->assertSame($selected, $options->item(0)->getAttribute('value'));
            }
            foreach (Fleet::INSURERS as $option) {
                $this->assertSame(1, $xpath->query('//select[@name="insurance_provider"]/option[@value="'.$option.'"]')->length);
            }
        }
    }

    public function test_fleet_list_renders_server_side_table_and_resource_cells(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-11 14:00:00', date_default_timezone_get()));
        $car = new Fleet;
        $car->id = 17;
        $car->car_brand_model = 'Isuzu Elf';
        $car->car_registration_number = 'MHG 428';
        $car->car_description = '<script>invalid</script>';
        $car->manufacture_year = 2014;
        $car->engine_capacity_cc = 2999;
        $car->fuel_type = 'Diesel';
        $car->transmission = 'Manual';
        $car->odometer_km = 0;
        $car->insurance_provider = 'Prime Insurance';
        $car->insurance_expiry = '2026-12-01';
        $car->roadworthiness_expiry = '2026-11-10';
        $car->service_date = '2026-11-01';
        $car->next_service_km = 125000;
        $car->setRelation('instructor', null);
        $car->setRelation('licenceClass', null);
        $template = str_replace(
            ["@extends('layouts.backend')", "@section('content')", "@section('css_after')", "@push('scripts')", '@endpush', '@endsection'],
            '',
            file_get_contents(resource_path('views/fleet/fleet.blade.php'))
        );
        foreach ([1, 0] as $vehicleCount) {
            $html = Blade::render($template, ['vehicleCount' => $vehicleCount, 'assignedVehicles' => 0, 'errors' => new ViewErrorBag]);
            $document = new \DOMDocument;
            $previous = libxml_use_internal_errors(true);
            try {
                $document->loadHTML($html);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
            $xpath = new \DOMXPath($document);
            $this->assertSame(16, $xpath->query('//table[@id="fleet-table"]/thead/tr/th')->length);
            $this->assertSame([
                'Vehicle', 'Registration', 'Instructor', 'Students', 'Insurance expiry',
                'Roadworthiness expiry', 'Odometer (km)', 'Last service', 'Next service (km)',
                'Insurer', 'Year', 'Engine (cc)', 'Fuel', 'Transmission', 'Licence class', 'Actions',
            ], array_map(fn ($node) => trim($node->textContent), iterator_to_array($xpath->query('//table[@id="fleet-table"]/thead/tr/th'))));
            $this->assertSame(0, $xpath->query('//table[@id="fleet-table"]/tbody/tr')->length);
            $this->assertSame(0, $xpath->query('//table[@id="fleet-table"]//script | //table[@id="fleet-table"]//td[@colspan]')->length);
            $this->assertStringContainsString("$('#fleet-table').DataTable({", $html);
            $this->assertStringContainsString("$('#fleet-table').on('click', '.delete-confirm'", $html);
            $this->assertStringContainsString("orderable: false, searchable: false", $html);
            $this->assertStringContainsString("emptyTable: 'No vehicles yet'", $html);
            $this->assertStringContainsString('serverSide: true', $html);
            $this->assertStringContainsString('Unable to load fleet.', $html);
        }
        foreach ([
            'manufacture_year' => '2014', 'engine_capacity_cc' => '2,999', 'fuel_type' => 'Diesel',
            'transmission' => 'Manual', 'odometer_km' => '0', 'insurance_provider' => 'Prime Insurance',
            'insurance_expiry' => '01 Dec 2026', 'roadworthiness_expiry' => '10 Nov 2026',
            'service_date' => '01 Nov 2026', 'next_service_km' => '125,000',
        ] as $field => $value) {
            $html = view('fleet.partials.list-cell', compact('car', 'field'))->render();
            $this->assertSame($value, trim(strip_tags($html)));
        }
        $html = view('fleet.partials.list-cell', ['car' => $car, 'field' => 'vehicle'])->render();
        $this->assertStringContainsString('&lt;script&gt;invalid&lt;/script&gt;', $html);
        $html = view('fleet.partials.list-cell', ['car' => $car, 'field' => 'actions'])->render();
        $this->assertStringContainsString('btn-sm btn-primary rounded-pill px-3', $html);
        foreach (['show', 'edit', 'destroy'] as $action) {
            $this->assertStringContainsString(route('fleet.'.$action, 17), $html);
        }
        $this->assertStringContainsString('Not assigned', view('fleet.partials.list-cell', ['car' => $car, 'field' => 'instructor_name'])->render());
        foreach ([null => false, '2026-11-11' => true, '2026-11-30' => true, '2026-12-01' => true, '2026-12-02' => false] as $date => $warning) {
            $car->insurance_expiry = $date ?: null;
            $car->roadworthiness_expiry = $date ?: null;
            foreach (['insurance_expiry', 'roadworthiness_expiry'] as $field) {
                $html = view('fleet.partials.list-cell', compact('car', 'field'))->render();
                $this->assertSame($warning, str_contains($html, 'text-danger'), (string) $date);
            }
        }
        $this->travelBack();
    }

    public function test_old_input_and_inline_errors_are_restored(): void
    {
        $this->app['request']->setLaravelSession($this->app['session']->driver());
        $this->app['session']->flashInput([
            'make' => 'Revised make', 'fuel_type' => 'Electric',
            'insurance_expiry' => '2027-03-01', 'odometer_km' => '0',
        ]);
        $html = view('fleet.partials.vehicle-form', [
            'editing' => false, 'fleet' => new Fleet, 'instructors' => collect(),
            'licenceClasses' => collect(),
            'errors' => (new ViewErrorBag)->put('default', new MessageBag(['make' => 'Check vehicle make.'])),
        ])->render();
        $document = new \DOMDocument;
        $document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $this->assertSame('Revised make', $xpath->query('//input[@name="make"]')->item(0)->getAttribute('value'));
        $this->assertSame('true', $xpath->query('//input[@name="make"]')->item(0)->getAttribute('aria-invalid'));
        $this->assertSame('Electric', $xpath->query('//select[@name="fuel_type"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame('2027-03-01', $xpath->query('//input[@name="insurance_expiry"]')->item(0)->getAttribute('value'));
        $this->assertSame('0', $xpath->query('//input[@name="odometer_km"]')->item(0)->getAttribute('value'));
        $this->assertStringContainsString('Check vehicle make.', $html);
    }

    public function test_create_route_resolves_before_vehicle_show(): void
    {
        $route = Route::getRoutes()->match(Request::create('/fleet/create'));

        $this->assertSame('fleet.create', $route->getName());
        $this->assertSame('create', $route->getActionMethod());
        $this->get('/fleet/create')->assertRedirect(route('login'));
    }

    public function test_vehicle_forms_submit_to_resource_routes(): void
    {
        foreach ([false, true] as $editing) {
            $fleet = new Fleet;
            $fleet->id = 13;
            $fleet->car_brand_model = 'Test vehicle';
            $fleet->make = 'Toyota';
            $fleet->model = 'Vitz';
            $fleet->odometer_km = 0;
            $fleet->insurance_expiry = '2027-01-31';
            $fleet->setRelation('instructor', null);

            $html = view('fleet.partials.vehicle-form', [
                'editing' => $editing,
                'fleet' => $fleet,
                'instructors' => collect(),
                'licenceClasses' => collect(),
                'errors' => new ViewErrorBag,
            ])->render();
            $document = new \DOMDocument;
            $document->loadHTML($html);
            $xpath = new \DOMXPath($document);

            $this->assertSame(
                $editing ? route('fleet.update', 13) : route('fleet.store'),
                $xpath->query('//form')->item(0)->getAttribute('action')
            );
            $this->assertSame('multipart/form-data', $xpath->query('//form')->item(0)->getAttribute('enctype'));
            foreach (['make', 'model', 'manufacture_year', 'vin', 'engine_number', 'engine_capacity_cc', 'fuel_type', 'transmission', 'colour', 'odometer_km', 'seating_capacity', 'insurance_provider', 'insurance_expiry', 'roadworthiness_expiry', 'service_date', 'next_service_km', 'reg_number', 'tracker_device_id', 'licenceClass', 'instructor', 'fleet_image', 'car_description'] as $name) {
                $this->assertSame(1, $xpath->query('//*[@name="'.$name.'"]')->length);
            }
            $this->assertSame($editing ? 1 : 0, $xpath->query('//input[@name="_method" and @value="PUT"]')->length);
            $this->assertSame($editing ? 0 : 1, $xpath->query('//select[@name="instructor" and @required]')->length);
            $this->assertSame(2, $xpath->query('//input[(@name="make" or @name="model") and @required]')->length);
            $this->assertSame(1, $xpath->query('//input[@name="engine_capacity_cc" and @type="number" and @required]')->length);
            $this->assertSame('Toyota', $xpath->query('//input[@name="make"]')->item(0)->getAttribute('value'));
            $this->assertSame('0', $xpath->query('//input[@name="odometer_km"]')->item(0)->getAttribute('value'));
            $this->assertSame('2027-01-31', $xpath->query('//input[@name="insurance_expiry"]')->item(0)->getAttribute('value'));
            $this->assertSame(1, $xpath->query('//textarea[@name="car_description"]/following::button[@type="submit"]')->length);
        }
    }
}
