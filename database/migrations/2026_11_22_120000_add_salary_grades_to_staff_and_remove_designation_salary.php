<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['administrators', 'instructors'] as $staffTable) {
            if (!Schema::hasColumn($staffTable, 'salary_grade_id')) {
                Schema::table($staffTable, function (Blueprint $table) {
                    $table->uuid('salary_grade_id')->nullable();
                });
            }

            if (!Schema::hasIndex($staffTable, $staffTable . '_salary_grade_id_index')) {
                Schema::table($staffTable, function (Blueprint $table) {
                    $table->index('salary_grade_id');
                });
            }

            $staffWithDesignationSalary = DB::table($staffTable)
                ->join('designations', $staffTable . '.designation_id', '=', 'designations.id')
                ->whereNull($staffTable . '.basic_salary')
                ->whereNotNull('designations.basic_salary')
                ->select($staffTable . '.id as staff_id', 'designations.basic_salary')
                ->get();

            foreach ($staffWithDesignationSalary as $staff) {
                DB::table($staffTable)
                    ->where('id', $staff->staff_id)
                    ->update(['basic_salary' => $staff->basic_salary]);
            }
        }

        Schema::table('designations', function (Blueprint $table) {
            $table->dropColumn('basic_salary');
        });
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->decimal('basic_salary', 12, 2)->nullable();
        });

        foreach (['administrators', 'instructors'] as $staffTable) {
            if (Schema::hasColumn($staffTable, 'salary_grade_id')) {
                Schema::table($staffTable, function (Blueprint $table) {
                    $table->dropColumn('salary_grade_id');
                });
            }
        }
    }
};
