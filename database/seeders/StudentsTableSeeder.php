<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('students')->insert([
            'fname' => 'Haven',
            'mname' =>'',
            'sname' => 'Chikumba',
            'signature' => 'student-signature.png',
            'trn' => Str::random(10),
            'gender' => 'Male',
            'date_of_birth' => mt_rand(10,100000),
            'phone' => '+265'.rand(10,100000),
            'address' => Str::random(10),
            'district_id' => 1,
            'avatar' => Str::random(10).'png',
            'course_id' => 1,
            'status' => 'Active',
        ]);
    }
}
