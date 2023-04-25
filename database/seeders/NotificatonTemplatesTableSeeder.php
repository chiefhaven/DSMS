<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificatonTemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('notification_templates')->insert([
            'type' => 'balance',
            'body' => 'All theory'
        ]);

        DB::table('notification_templates')->insert([
            'type' => 'enrollement',
            'body' => 'All practicals'
        ]);

        DB::table('notification_templates')->insert([
            'type' => 'new registration',
            'body' => 'All practicals'
        ]);
    }
}
