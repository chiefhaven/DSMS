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
        Schema::table('settings', function (Blueprint $table) {
            $table->integer('fees_trn_threshold')->nullable()->default(0);
            $table->integer('fees_road_threshold')->nullable()->default(100);
            $table->integer('fees_code_i_threshold')->nullable()->default(20);
            $table->integer('fees_code_ii_threshold')->nullable()->default(70);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            //
        });
    }
};
