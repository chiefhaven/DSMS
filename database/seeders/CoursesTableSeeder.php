<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoursesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('courses')->insert([
            'name' => 'Full Course',
            'short_description' => '30 Days plus license',
            'price' => 180000,
            'duration' => 30,
            'practicals' => 25,
            'theory' => 5,
            'status' => 'Active'
        ]);
        DB::table('courses')->insert([
            'name' => 'Half Course',
            'short_description' => '20 Days plus license',
            'price' => 160000,
            'duration' => 15,
            'practicals' => 15,
            'theory' => 5,
            'status' => 'Active'
        ]);
        DB::table('courses')->insert([
            'name' => 'Quoter Course',
            'short_description' => '10 Days plus license',
            'price' => 150000,
            'duration' => 15,
            'practicals' => 10,
            'theory' => 5,
            'status' => 'Active'
        ]);
    }
}
