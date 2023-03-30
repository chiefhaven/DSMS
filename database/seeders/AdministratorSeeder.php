<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class AdministratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('administrators')->insert([
            'fname' => 'Haven',
            'sname' => 'Chikumba',            
            'gender' => 'Male',
            'date_of_birth' => '1976-09-30',
            'phone' => '+265'.rand(10,100000),
            'address' => Str::random(10),
            'district_id' => 1,
            'avatar' => Str::random(10).'.png',
            'status' => 'Active',
        ]);
        DB::table('administrators')->insert([
            'fname' => 'Lyson',
            'sname' => 'Mboma',
            'gender' => 'Female',
            'date_of_birth' => '1994-08-22',
            'phone' => '+265'.rand(10,100000),
            'address' => Str::random(10),
            'district_id' => 1,
            'avatar' => Str::random(10).'.png',
            'status' => 'Active',
        ]);

    }
}
