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
        Schema::create('schedule_lesson_students', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('lesson_id');
            $table->uuid('student_id');
            $table->uuid('schedule_id');

            $table->longText('location')->nullable();

            // Foreign key constraints
            $table->foreign('lesson_id')->references('id')->on('lessons')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('schedule_id')->references('id')->on('schedule')->onDelete('cascade');

            $table->index(['lesson_id', 'student_id', 'schedule_id']);

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
        Schema::dropIfExists('schedule_lesson_students');
    }
};
