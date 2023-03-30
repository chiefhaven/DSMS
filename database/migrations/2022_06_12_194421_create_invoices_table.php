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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->integer('student_id');
            $table->integer('course_id');
            $table->string('course_price');
            $table->float('invoice_total');
            $table->float('invoice_discount');
            $table->float('invoice_amount_paid');
            $table->float('invoice_balance');
            $table->string('invoice_payment_due_date');
            $table->string('invoice_payment_method');
            $table->text('invoice_terms');
            $table->string('date_created');
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
        Schema::dropIfExists('invoices');
    }
};
