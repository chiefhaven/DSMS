<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('approve fleet updates', 'web');

        Schema::create('fleet_update_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_id')->constrained('fleets')->cascadeOnDelete();
            $table->uuid('submitted_by')->nullable();
            $table->unsignedInteger('odometer_km');
            $table->date('roadworthiness_expiry');
            $table->date('insurance_expiry');
            $table->string('status', 20)->default('pending');
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['fleet_id', 'status'], 'fleet_update_submissions_status_index');
            $table->index(['submitted_by', 'created_at'], 'fleet_update_submissions_owner_index');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_update_submissions');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
