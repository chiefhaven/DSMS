<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE instructors MODIFY status ENUM('Pending','Suspended','Active','Contract ended') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE instructors SET status = 'Active' WHERE status = 'Contract ended'");
        DB::statement("ALTER TABLE instructors MODIFY status ENUM('Pending','Suspended','Active') NOT NULL");
    }
};
