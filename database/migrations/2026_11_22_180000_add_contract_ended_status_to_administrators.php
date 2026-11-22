<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE administrators MODIFY status ENUM('Pending','Suspended','Active','Contract ended') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE administrators SET status = 'Active' WHERE status = 'Contract ended'");
        DB::statement("ALTER TABLE administrators MODIFY status ENUM('Pending','Suspended','Active') NOT NULL");
    }
};
