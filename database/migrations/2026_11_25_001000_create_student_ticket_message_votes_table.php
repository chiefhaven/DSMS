<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_ticket_message_votes', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_id');
            $table->uuid('student_id');
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('student_ticket_messages')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->unique(['message_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_ticket_message_votes');
    }
};
