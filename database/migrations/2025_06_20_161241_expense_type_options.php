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
        Schema::create('expense_type_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('expense_type_id'); // FK to expense_types
            $table->string('name');          // Option name
            $table->integer('amount_per_student')->nullable();
            $table->string('value')->nullable(); // Optional value or config
            $table->timestamps();

            // Add FK constraint (optional but recommended)
            $table->foreign('expense_type_id')->references('id')->on('expense_types')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('expense_type_options');
    }
};
