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
            $table->decimal('paid', 10, 2)
                ->default(0.00)
                ->after('expense_id')
                ->comment('Amount owed to the student for this expense');
            $table->decimal('balance', 10, 2)
                ->after('paid')
                ->default(0.00)
                ->comment('Amount remaining to be paid for this expense');
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
            $table->dropColumn(['paid', 'balance']);
        });
    }
};
