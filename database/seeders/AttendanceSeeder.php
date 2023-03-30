<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('attendances')->insert([
            'student_id' => 1,
            'attendance_date' => date('Y-m-d'),
            'lesson_id' => 1,
            'instructor_id' => 1,
            'entered_by' => 'Chimwemwe Mboma'
        ]);
        DB::table('attendances')->insert([
            'student_id' => 1,
            'attendance_date' => date('Y-m-d'),
            'lesson_id' => 1,
            'instructor_id' => 1,
            'entered_by' => 'Chimwemwe Mboma'
        ]);
        DB::table('attendances')->insert([
            'student_id' => 1,
            'attendance_date' => date('Y-m-d'),
            'lesson_id' => 2,
            'instructor_id' => 1,
            'entered_by' => 'Chimwemwe Mboma'
        ]);
        DB::table('attendances')->insert([
            'student_id' => 1,
            'attendance_date' => date('Y-m-d'),
            'lesson_id' => 1,
            'instructor_id' => 2,
            'entered_by' => 'Chimwemwe Mboma'
        ]);


    }
}
