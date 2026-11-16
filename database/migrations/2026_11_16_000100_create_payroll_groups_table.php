<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('payment_month', 7);
            $table->string('created_by', 36)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('payroll_group_id', 36)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('payroll_group_id');
        });
        Schema::dropIfExists('payroll_groups');
    }
};
