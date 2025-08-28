<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expense_type_options', function (Blueprint $table) {
            $table->float('fees_percent_threshhold', 8, 2)
                ->nullable()
                ->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expense_type_options', function (Blueprint $table) {
            $table->dropColumn('fees_percent_threshhold');
        });
    }
};
