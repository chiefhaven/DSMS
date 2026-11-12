<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payments', 'recorded_by_user_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->uuid('recorded_by_user_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // Retain recorder identities when rolling back.
    }
};
