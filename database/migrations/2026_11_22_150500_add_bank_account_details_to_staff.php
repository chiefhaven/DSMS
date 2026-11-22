<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['administrators', 'instructors'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                foreach ([
                    'bank_name' => 255,
                    'bank_branch' => 255,
                    'bank_account_name' => 255,
                    'bank_account_number' => 100,
                    'bank_account_type' => 100,
                ] as $column => $length) {
                    if (!Schema::hasColumn($tableName, $column)) {
                        $table->string($column, $length)->nullable();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        $columns = [
            'bank_name',
            'bank_branch',
            'bank_account_name',
            'bank_account_number',
            'bank_account_type',
        ];

        foreach (['administrators', 'instructors'] as $tableName) {
            $existingColumns = array_values(array_filter(
                $columns,
                fn ($column) => Schema::hasColumn($tableName, $column)
            ));

            if ($existingColumns !== []) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($existingColumns));
            }
        }
    }
};
