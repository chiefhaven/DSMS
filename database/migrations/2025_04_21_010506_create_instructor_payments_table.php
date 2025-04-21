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
        Schema::create('instructor_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('instructor_id');
            $table->unsignedInteger('attendances_count');
            $table->decimal('pay_per_attendance', 10, 2);
            $table->decimal('total_payment', 10, 2);

            $table->date('payment_date');
            $table->string('payment_month', 7); // e.g., "2024-04"

            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('instructor_id')->references('id')->on('instructors')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('instructor_payments');
    }
};
