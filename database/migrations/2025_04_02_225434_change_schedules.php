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
        Schema::table('schedule_lessons', function (Blueprint $table) {
            $table->longText('location')->nullable()->after('finish_time'); // Replace 'column_name' with the appropriate column to place it after
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('schedule_lessons', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
