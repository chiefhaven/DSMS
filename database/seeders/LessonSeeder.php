<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('lessons')->insert([
            'name' => 'Theory',
            'description' => 'All theory'
        ]);

        DB::table('lessons')->insert([
            'name' => 'Practical',
            'description' => 'All practicals'
        ]);
    }
}
