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
        Schema::create('administrators', function (Blueprint $table) {
            $table->id();
            $table->string('fname');
            $table->string('sname');
            $table->enum('gender', ['Male', 'Female']);
            $table->string('date_of_birth');
            $table->string('phone');
            $table->string('address');            
            $table->string('district_id');
            $table->string('avatar')->nullable();
            $table->string('token')->nullable();
            $table->enum('status', ['Pending', 'Suspended', 'Active']);
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
        Schema::dropIfExists('adminitrators');
    }
};
