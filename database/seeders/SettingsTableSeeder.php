<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->insert([
            'school_name' => 'Daron Driving School',
            'company_description' => 'A driving school for your kids and family',
            'slogan' => Str::random(10),            
            'logo' => 'logo.png',           
            'authorization_signature' => 'authorization-signature.png',
            'postal' => '2247',
            'district_id' => 2,
            'favicon' => 'favicon.ico',
            'time_zone' => 'Africa/Blantyre',
            'email' => 'info@darondrivingschool.com',
            'phone_1' => '+265'.mt_rand(1000000,10000000),
            'phone_2' => '+265'.mt_rand(1000000,10000000),
            'address' => 'Area 49. Baghdad',
        ]);
    }
}
