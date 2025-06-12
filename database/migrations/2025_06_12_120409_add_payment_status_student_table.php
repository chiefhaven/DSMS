<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expense_student', function (Blueprint $table) {
            $table->float('amount')->nullable();
            $table->binary('status')->nullable();
            $table->uuid('payment_method')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expense_student', function (Blueprint $table) {
            $table->dropColumn(['amount', 'status', 'payment_method']);
        });
    }
};
