<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('notification_templates')->insert([
            'type' => 'balance',
            'body' => 'All theory balance'
        ]);

        DB::table('notification_templates')->insert([
            'type' => 'enrollement',
            'body' => 'All practicalsenrollment'
        ]);

        DB::table('notification_templates')->insert([
            'type' => 'new',
            'body' => 'All practicals new'
        ]);
    }
}
