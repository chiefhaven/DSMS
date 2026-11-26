<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_update_submissions', function (Blueprint $table) {
            $table->date('odometer_reading_date')->nullable()->after('odometer_km');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_update_submissions', function (Blueprint $table) {
            $table->dropColumn('odometer_reading_date');
        });
    }
};
