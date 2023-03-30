<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class InvoicesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('invoices')->insert([
            'invoice_number' => rand(10,100000),
            'student_id' => 1,
            'course_id' => 1,
            'course_price' => 180000,
            'invoice_total' => 180000,
            'invoice_discount' => 10000,
            'invoice_amount_paid' => 50000,
            'invoice_balance' => 120000,
            'invoice_payment_due_date' => date('Y-m-d'),
            'invoice_payment_method' => 'Cash',
            'invoice_terms' => Str::random(100),
            'date_created' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
