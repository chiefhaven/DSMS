<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FleetTrackingPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.tracking_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::setDefaultConnection('tracking_test');
        $this->withoutMiddleware();
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00', date_default_timezone_get()));
        Schema::create('fleets', function (Blueprint $table) {
            $table->integer('id');
            $table->integer('tracker_device_id')->nullable();
        });
        Schema::create('fleet_trackers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('device_id')->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->timestamps();
        });
        DB::table('fleets')->insert([
            ['id' => 12, 'tracker_device_id' => 5],
            ['id' => 13, 'tracker_device_id' => null],
        ]);
        foreach ([
            '2026-11-10 23:59:59', '2026-11-11 00:00:00',
            '2026-11-11 08:59:59', '2026-11-11 09:00:00',
            '2026-11-11 09:30:00', '2026-11-11 10:00:00',
            '2026-11-12 00:00:00',
        ] as $time) {
            DB::table('fleet_trackers')->insert([
                'device_id' => 5, 'latitude' => -14, 'longitude' => 34,
                'created_at' => $time,
            ]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_presets_and_default_use_exact_school_time_boundaries(): void
    {
        $this->getJson('/show-vehicle-geo-data/12')->assertOk()->assertJsonCount(5);
        $this->getJson('/show-vehicle-geo-data/12?period=today')->assertJsonCount(5);
        $this->getJson('/show-vehicle-geo-data/12?period=yesterday')->assertJsonCount(1);
        $this->getJson('/show-vehicle-geo-data/12?period=hour')->assertJsonCount(2);
    }

    public function test_custom_period_excludes_end_and_preserves_response_shape(): void
    {
        $query = http_build_query([
            'period' => 'custom', 'from' => '2026-11-11T09:00', 'to' => '2026-11-11T09:30',
        ]);
        $this->getJson('/show-vehicle-geo-data/12?'.$query)->assertOk()->assertJsonCount(1)
            ->assertJsonStructure([['latitude', 'longitude', 'created_at', 'fleet']]);
    }

    public function test_invalid_ranges_are_rejected_and_unassigned_devices_do_not_leak_points(): void
    {
        foreach ([
            ['period' => 'invalid'],
            ['period' => 'custom'],
            ['period' => 'custom', 'from' => 'invalid', 'to' => 'invalid'],
            ['period' => 'custom', 'from' => '2026-11-11T10:00', 'to' => '2026-11-11T09:00'],
        ] as $query) {
            $this->getJson('/show-vehicle-geo-data/12?'.http_build_query($query))->assertStatus(422);
        }
        DB::table('fleet_trackers')->insert([
            'device_id' => null, 'latitude' => -14, 'longitude' => 34,
            'created_at' => '2026-11-11 09:00:00',
        ]);
        $this->getJson('/show-vehicle-geo-data/13')->assertOk()->assertExactJson([]);
        $this->getJson('/show-vehicle-geo-data/999')->assertNotFound();
    }
}
