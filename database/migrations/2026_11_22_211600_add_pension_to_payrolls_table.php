<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'employee_pension')) {
                $table->decimal('employee_pension', 12, 2)->default(0)->after('paye');
            }
            if (!Schema::hasColumn('payrolls', 'employer_pension')) {
                $table->decimal('employer_pension', 12, 2)->default(0)->after('employee_pension');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $columns = array_filter(['employee_pension', 'employer_pension'], fn ($column) => Schema::hasColumn('payrolls', $column));
            if ($columns !== []) {
                $table->dropColumn(array_values($columns));
            }
        });
    }
};
