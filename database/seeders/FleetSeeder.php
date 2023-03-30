<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FleetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('fleets')->insert([
            'fleet_image' => 'honda_fit.png',
            'car_brand_model' => 'Honda Fit',
            'car_registration_number' => 'BX 7632',
            'car_description' => 'Honda fit',
            'instructor_id' => 1,
        ]);
        DB::table('fleets')->insert([
            'fleet_image' => 'daihatsu_mira.png',
            'car_brand_model' => 'Daihatsu Mira',
            'car_registration_number' => 'BX 2763',
            'car_description' => 'Mira',
            'instructor_id' => 1,
        ]);
    }
}
