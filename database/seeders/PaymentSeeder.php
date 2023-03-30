<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('payments')->insert([
            'transaction_id' => 'QRfdrsehwet',
            'student_id' => 1,
            'amount_paid' => 100000,
            'payment_method' => 1,
            'entered_by' => 'Haven Chikumba',
            'created_at' => Carbon::now(),
        ]);
    }
}
