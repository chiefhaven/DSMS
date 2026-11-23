<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('instructor_id')->constrained('instructors')->cascadeOnDelete();
            $table->foreignUuid('leave_type_id')->nullable()->constrained('leave_types')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('days')->default(1);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
