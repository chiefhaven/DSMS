<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fleet_trackers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mbirasync_id')->unique();
            $table->unsignedBigInteger('device_id');
            $table->string('protocol')->nullable();
            $table->timestamp('device_time')->nullable();
            $table->timestamp('fix_time')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('altitude')->default(0);
            $table->float('speed')->default(0);
            $table->float('course')->default(0);
            $table->boolean('valid')->default(true);
            $table->json('attributes')->nullable();
            $table->timestamps();

            $table->index('device_id');
            $table->index('fix_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_trackers');
    }
};
