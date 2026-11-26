<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_update_submissions', function (Blueprint $table) {
            $table->date('service_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fleet_update_submissions', function (Blueprint $table) {
            $table->dropColumn('service_date');
        });
    }
};
