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
            $table->dropColumn('student_id');
            $table->dropColumn('lesson_id');
            $table->dropColumn('location');
        });
    }

    public function down()
    {
        Schema::table('schedule_lessons', function (Blueprint $table) {
            $table->uuid('student_id')->nullable();
            $table->uuid('lesson_id')->nullable();
            $table->longText('location')->nullable();
        });
    }
};
