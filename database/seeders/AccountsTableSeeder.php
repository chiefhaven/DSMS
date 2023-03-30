<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('accounts')->insert([
            'user_id' => 1,
            'account_number' => rand(100000000,999999999)
        ]);
        DB::table('accounts')->insert([
            'user_id' => 2,
            'account_number' => rand(100000000,999999999)
        ]);
        DB::table('accounts')->insert([
            'user_id' => 3,
            'account_number' => rand(100000000,999999999)
        ]);
        DB::table('accounts')->insert([
            'user_id' => 4,
            'account_number' => rand(100000000,999999999)
        ]);
        DB::table('accounts')->insert([
            'user_id' => 5,
            'account_number' => rand(100000000,999999999)
        ]);
    }
}
