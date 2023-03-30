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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('school_name');
            $table->string('slogan');
            $table->string('company_description');
            $table->string('logo');
            $table->string('authorization_signature');
            $table->string('favicon');
            $table->integer('district_id');
            $table->string('postal');
            $table->string('time_zone');
            $table->string('email');
            $table->string('phone_1');
            $table->string('phone_2');
            $table->string('address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
