<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_number', 32)->nullable()->unique();
        });

        Schema::create('student_number_sequences', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_number_sequences');
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['student_number']);
            $table->dropColumn('student_number');
        });
    }
};
