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
        Schema::create('vehicle_trackers', function (Blueprint $table) {
            $table->id();
            $table->uuid('fleet_id');
            $table->uuid('user_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();

            // Optional: Add foreign key constraints if you have fleets & users tables
            $table->foreign('fleet_id')->references('id')->on('fleets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_trackers');
    }

};
