<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('designations', 'hr_department_id')) {
            Schema::table('designations', function (Blueprint $table) {
                $table->uuid('hr_department_id')->nullable();
            });
        }

        if (!Schema::hasIndex('designations', 'designations_hr_department_id_index')) {
            Schema::table('designations', function (Blueprint $table) {
                $table->index('hr_department_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('designations', 'hr_department_id')) {
            Schema::table('designations', function (Blueprint $table) {
                $table->dropColumn('hr_department_id');
            });
        }
    }
};
