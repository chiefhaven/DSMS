<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnrollmentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('enrollments')->insert([
            'course_id' => 1,
            'student_id' => 1,
            'created_at' => date('Y-m-d')
        ]);
        DB::table('enrollments')->insert([
            'course_id' => 2,
            'student_id' => 2,
            'created_at' => date('Y-m-d')
        ]);
        DB::table('enrollments')->insert([
            'course_id' => 1,
            'student_id' => 3,
            'created_at' => date('Y-m-d')
        ]);
        DB::table('enrollments')->insert([
            'course_id' => 3,
            'student_id' => 4,
            'created_at' => date('Y-m-d')
        ]);
        DB::table('enrollments')->insert([
            'course_id' => 2,
            'student_id' => 5,
            'created_at' => date('Y-m-d')
        ]);
    }
}
