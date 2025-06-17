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
        Schema::table('vehicle_trackers', function (Blueprint $table) {
            $table->uuid('fleet_id')->nullable()->change();
            $table->uuid('user_id')->nullable()->change();
            $table->decimal('latitude', 10, 7)->nullable()->change();
            $table->decimal('longitude', 10, 7)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vehicle_trackers', function (Blueprint $table) {
            $table->uuid('fleet_id')->nullable(false)->change();
            $table->uuid('user_id')->nullable(false)->change();
            $table->string('latitude')->nullable(false)->change();
            $table->string('longitude')->nullable(false)->change();
        });
    }
};
