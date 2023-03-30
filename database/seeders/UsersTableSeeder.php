<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Haven',
            'student_id' => '',
            'instructor_id' => '',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
        ]);
        DB::table('users')->insert([
            'name' => Str::random(10),
            'student_id' => '1',
            'instructor_id' => '',
            'email' => Str::random(10).'@admin.com',
            'password' => bcrypt('password'),
        ]);
        DB::table('users')->insert([
            'name' => Str::random(10),
            'student_id' => '',
            'instructor_id' => '1',
            'email' => Str::random(10).'@admin.com',
            'password' => bcrypt('password'),
        ]);
        DB::table('users')->insert([
            'name' => Str::random(10),
            'student_id' => '',
            'instructor_id' => '2',
            'email' => Str::random(10).'@admin.com',
            'password' => bcrypt('password'),
        ]);
        DB::table('users')->insert([
            'name' => Str::random(10),
            'administrator_id' => '1',
            'email' => Str::random(10).'@admin.com',
            'password' => bcrypt('password'),
        ]);
        DB::table('users')->insert([
            'name' => Str::random(10),
            'administrator_id' => '2',
            'email' => Str::random(10).'@admin.com',
            'password' => bcrypt('password'),
        ]);
          
    }
}
