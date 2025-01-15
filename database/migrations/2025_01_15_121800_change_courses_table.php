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
        Schema::table('courses', function (Blueprint $table) {
            $table->double('practicals')->nullable()->change();
            $table->double('theory')->nullable()->change();
            $table->double('duration')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            // Revert changes here if needed
            $table->integer('practicals')->nullable(false)->change();
            $table->integer('theory')->nullable(false)->change();
            $table->integer('duration')->nullable(false)->change();
        });
    }
};
