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
        Schema::table('attendances', function (Blueprint $table) {
            $table->uuid('bulk_attendance_id')->nullable()->after('instructor_id');
            $table->uuid('administrator_id')->nullable()->after('instructor_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['bulk_attendance_id']);
            $table->dropForeign(['administrator_id']);
            $table->dropColumn(['bulk_attendance_id', 'administrator_id']);
        });
    }
};
