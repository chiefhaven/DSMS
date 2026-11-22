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
                if (!Schema::hasColumn($tableName, 'pension_applicable')) {
                    $table->boolean('pension_applicable')->default(false)->after('paye');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['administrators', 'instructors'] as $tableName) {
            if (Schema::hasColumn($tableName, 'pension_applicable')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('pension_applicable');
                });
            }
        }
    }
};
