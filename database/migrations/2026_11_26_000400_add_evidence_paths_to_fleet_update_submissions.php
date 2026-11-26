<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_update_submissions', function (Blueprint $table) {
            $table->string('odometer_picture_path')->nullable();
            $table->string('service_date_picture_path')->nullable();
            $table->string('roadworthiness_picture_path')->nullable();
            $table->string('insurance_picture_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fleet_update_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'odometer_picture_path',
                'service_date_picture_path',
                'roadworthiness_picture_path',
                'insurance_picture_path',
            ]);
        });
    }
};
