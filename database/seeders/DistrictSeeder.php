<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('districts')->insert([
            'name' => 'Salima'
        ]);
        DB::table('districts')->insert([
            'name' => 'Lilongwe'
        ]);
        DB::table('districts')->insert([
            'name' => 'Dedza'
        ]);
    }
}
