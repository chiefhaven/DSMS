<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->decimal('basic_salary', 12, 2)->nullable();
        });

        foreach (['instructors', 'administrators'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('designation_id', 36)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropColumn('basic_salary');
        });

        foreach (['instructors', 'administrators'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('designation_id');
            });
        }
    }
};
