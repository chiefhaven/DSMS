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
        Schema::create('expense_student', function (Blueprint $table) {
            $table->id();
            $table->uuid('expense_id', 255);
            $table->uuid('student_id', 255);
            $table->enum('expense_type', ['TRN', 'Highway Code I', 'Highway Code II','Road Test']);

            $table->foreign('expense_id')->references('id')
                 ->on('expenses')->onDelete('cascade');
            $table->foreign('student_id')->references('id')
                ->on('students')->onDelete('cascade');
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
        Schema::dropIfExists('expense_student');
    }
};
