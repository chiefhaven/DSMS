<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('instructor_id', 36)->nullable()->index();
            $table->unsignedBigInteger('administrator_id')->nullable()->index();
            $table->string('payment_month', 7);
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->unsignedInteger('bonus_attendances')->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('status')->default('Pending');
            $table->string('created_by', 36)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['instructor_id', 'payment_month']);
            $table->unique(['administrator_id', 'payment_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
