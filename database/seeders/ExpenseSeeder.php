<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('expenses')->insert([
            'category' => 'fuel',
            'description' => 'Fuel for 22 February 2023, Mira DZ6235',
            'amount' => '10000',
            'paymentmethod_id' => '2',
        ]);
        DB::table('expenses')->insert([
            'category' => 'fuel',
            'description' => 'Fuel for 2 February 2023, Mira BS2345',
            'amount' => '10000',
            'paymentmethod_id' => '2',
        ]);
    }
}
