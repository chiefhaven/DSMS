<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstructorLessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('instructor_lesson')->insert([
            'instructor_id' => 1,
            'lesson_id' => 1
        ]);
        DB::table('instructor_lesson')->insert([
            'instructor_id' => 1,
            'lesson_id' => 2
        ]);
    }
}
