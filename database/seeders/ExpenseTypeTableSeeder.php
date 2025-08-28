<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ExpenseTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trnId = Str::uuid();
        $theoryId = Str::uuid();
        $roadTestId = Str::uuid();

        $expenseTypes = [
            [
                'id' => $trnId,
                'name' => 'TRN',
                'description' => 'Covers TRN',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $theoryId,
                'name' => 'Theory',
                'description' => 'Either highway code I or II',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $roadTestId,
                'name' => 'Road test',
                'description' => 'For road test fees',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('expense_types')->insert($expenseTypes);

        $expenseTypeOptions = [
            [
                'id' => Str::uuid(),
                'expense_type_id' => $trnId,
                'name' => 'TRN Option A',
                'amount_per_student' => 10000,
                'value' => 'standard',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'expense_type_id' => $theoryId,
                'name' => 'Theory Option B',
                'amount_per_student' => 15000,
                'value' => 'highway code I',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'expense_type_id' => $roadTestId,
                'name' => 'Retake Road Test',
                'amount_per_student' => 20000,
                'value' => 'retake',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('expense_type_options')->insert($expenseTypeOptions);
    }
}
