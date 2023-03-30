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
        Schema::create('students', function (Blueprint $table) {
            $table->increments('id')->default(NULL);
            $table->string('fname');
            $table->string('sname');            
            $table->string('signature')->nullable();
            $table->string('mname')->nullable();
            $table->string('trn')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->string('date_of_birth')->nullable();
            $table->string('phone')->unique;
            $table->string('address');            
            $table->string('district_id');
            $table->string('avatar')->nullable();
            $table->string('_token')->nullable();
            $table->string('course_id')->nullable();
            $table->enum('status', ['Pending', 'Suspended', 'Active', 'Finished']);
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
        Schema::dropIfExists('students');
    }
};
