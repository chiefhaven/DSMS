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
                if (!Schema::hasColumn($tableName, 'paye')) {
                    $table->boolean('paye')->default(false)->after('end_date');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['administrators', 'instructors'] as $tableName) {
            if (Schema::hasColumn($tableName, 'paye')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('paye');
                });
            }
        }
    }
};
