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
            $table->uuid('lesson_id')->nullable()->change();
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
            if (Schema::hasColumn('schedule_lessons', 'student_id')) {
                $table->dropColumn('student_id');
            }
            if (Schema::hasColumn('schedule_lessons', 'lesson_id')) {
                $table->dropColumn('lesson_id');
            }
            if (Schema::hasColumn('schedule_lessons', 'location')) {
                $table->dropColumn('location');
            }
        });
    }
};
