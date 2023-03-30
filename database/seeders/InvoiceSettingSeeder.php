<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('invoice_settings')->insert([
            'header' => '',
            'footer' => '',
            'invoice_due_days' => 0,            
            'terms' => '',
            'year' => True,
            'invoice_logo' => 'logo.png',
            'prefix' => 'Daron',
        ]);
    }
}
