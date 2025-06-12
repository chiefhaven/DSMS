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
        Schema::table('expense_student', function (Blueprint $table) {
            $table->time('paid_at')->nullable();
            $table->uuid('payment_entered_by')->nullable();
        });
    }

    public function down()
    {
        Schema::table('expense_student', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'payment_entered_by']);
        });
    }

};
