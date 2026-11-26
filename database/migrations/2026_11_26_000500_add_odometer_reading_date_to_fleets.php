<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->date('odometer_reading_date')->nullable()->after('odometer_km');
            $table->timestamp('odometer_reading_at')->nullable()->after('odometer_reading_date');
        });

        DB::table('fleets')
            ->whereNotNull('odometer_km')
            ->whereNotNull('updated_at')
            ->update([
                'odometer_reading_date' => DB::raw('DATE(updated_at)'),
                'odometer_reading_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->dropColumn(['odometer_reading_date', 'odometer_reading_at']);
        });
    }
};
