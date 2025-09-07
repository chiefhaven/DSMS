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
        Schema::create('expense_type_licence_class', function (Blueprint $table) {
            $table->uuid('licence_class_id');
            $table->uuid('expense_type_id');

            $table->primary(['licence_class_id', 'expense_type_id']);

            $table->foreign('licence_class_id')
                  ->references('id')->on('licence_classes')
                  ->onDelete('cascade');

            $table->foreign('expense_type_id')
                  ->references('id')->on('expense_types')
                  ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
