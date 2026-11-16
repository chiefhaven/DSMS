<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['instructors', 'administrators'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('hr_department_id', 36)->nullable()->index();
                $blueprint->decimal('basic_salary', 12, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['instructors', 'administrators'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn(['hr_department_id', 'basic_salary']);
            });
        }
    }
};
