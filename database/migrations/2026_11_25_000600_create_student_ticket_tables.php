<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('assigned_to')->nullable();
            $table->string('category', 50);
            $table->string('subject', 200);
            $table->string('status', 32)->default('open');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->index(['student_id', 'updated_at']);
            $table->index(['status', 'updated_at']);
        });

        Schema::create('student_ticket_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('user_id')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('student_tickets')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('student_ticket_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('student_ticket_messages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_ticket_attachments');
        Schema::dropIfExists('student_ticket_messages');
        Schema::dropIfExists('student_tickets');
    }
};
