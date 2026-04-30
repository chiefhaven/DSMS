<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expense_type_options', function (Blueprint $table) {
            $table->decimal('attendance_threshold', 5, 2)->default(0)->after('period_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_type_options', function (Blueprint $table) {
            $table->dropColumn('attendance_threshold');
        });
    }
};
