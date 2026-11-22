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
                if (!Schema::hasColumn($tableName, 'employment_date')) {
                    $table->date('employment_date')->nullable();
                }
                if (!Schema::hasColumn($tableName, 'employment_type')) {
                    $table->string('employment_type')->nullable();
                }
                if (!Schema::hasColumn($tableName, 'end_date')) {
                    $table->date('end_date')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['administrators', 'instructors'] as $tableName) {
            $columns = array_values(array_filter(
                ['employment_date', 'employment_type', 'end_date'],
                fn ($column) => Schema::hasColumn($tableName, $column)
            ));

            if ($columns !== []) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }
    }
};
