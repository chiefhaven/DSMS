<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->string('make', 120)->nullable();
            $table->string('model', 120)->nullable();
            $table->unsignedSmallInteger('manufacture_year')->nullable();
            $table->string('vin', 50)->nullable();
            $table->string('engine_number', 50)->nullable();
            $table->string('fuel_type', 20)->nullable();
            $table->string('transmission', 20)->nullable();
            $table->string('colour', 50)->nullable();
            $table->unsignedInteger('odometer_km')->nullable();
            $table->unsignedSmallInteger('seating_capacity')->nullable();
            $table->string('insurance_provider', 255)->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('roadworthiness_expiry')->nullable();
            $table->date('service_date')->nullable();
            $table->unsignedInteger('next_service_km')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->dropColumn([
                'make', 'model', 'manufacture_year', 'vin', 'engine_number',
                'fuel_type', 'transmission', 'colour', 'odometer_km', 'seating_capacity',
                'insurance_provider', 'insurance_expiry', 'roadworthiness_expiry',
                'service_date', 'next_service_km',
            ]);
        });
    }
};
