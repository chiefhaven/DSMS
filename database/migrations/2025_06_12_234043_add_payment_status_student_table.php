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
            $table->boolean('repeat')->nullable()->after('student_id');
            $table->boolean('status')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('expense_student', function (Blueprint $table) {
            $table->dropColumn('repeat');

            $table->boolean('status')->nullable(false)->change();
        });
    }
};
