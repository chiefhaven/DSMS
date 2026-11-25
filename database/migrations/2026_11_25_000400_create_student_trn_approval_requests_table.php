<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'student_trn_approval_requests';
        if (Schema::hasTable($tableName)) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE `student_trn_approval_requests` ENGINE=InnoDB');
            }

            $hasStudentForeignKey = collect(Schema::getForeignKeys($tableName))->contains(
                fn (array $foreignKey) => $foreignKey['columns'] === ['student_id'] && $foreignKey['foreign_table'] === 'students'
            );
            $hasRequestedTrnIndex = Schema::hasIndex($tableName, ['requested_trn', 'status']);

            Schema::table($tableName, function (Blueprint $table) use ($hasStudentForeignKey, $hasRequestedTrnIndex) {
                if (!$hasStudentForeignKey) {
                    $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
                }
                if (!$hasRequestedTrnIndex) {
                    $table->index(['requested_trn', 'status']);
                }
            });

            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->string('requested_trn')->nullable();
            $table->string('picture_path')->nullable();
            $table->uuid('requested_by');
            $table->uuid('reviewed_by')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->index(['student_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['requested_trn', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_trn_approval_requests');
    }
};
