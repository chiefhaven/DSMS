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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Set the primary key as UUID
            $table->string('name');
            $table->string('email')->unique();

            // Define `instructor_id`, `student_id`, and `administrator_id` as UUIDs
            $table->uuid('instructor_id')->nullable();
            $table->uuid('student_id')->nullable();
            $table->uuid('administrator_id')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            // Add foreign key constraints for the UUID columns
            $table->foreign('instructor_id')->references('id')->on('instructors')->onDelete('set null');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('set null');
            $table->foreign('administrator_id')->references('id')->on('administrators')->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
