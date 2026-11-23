<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $dbName = DB::getDatabaseName();
        $instructorFk = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'leave_requests' AND COLUMN_NAME = 'instructor_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1",
            [$dbName]
        );

        if ($instructorFk && isset($instructorFk->CONSTRAINT_NAME)) {
            Schema::table('leave_requests', function (Blueprint $table) use ($instructorFk) {
                $table->dropForeign($instructorFk->CONSTRAINT_NAME);
            });
        }

        $instructorColumn = DB::selectOne(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'leave_requests' AND COLUMN_NAME = 'instructor_id' LIMIT 1",
            [$dbName]
        );
        if ($instructorColumn && strtoupper((string) $instructorColumn->IS_NULLABLE) === 'NO') {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->foreignUuid('instructor_id')->nullable()->change();
            });
        }

        if (!Schema::hasColumn('leave_requests', 'administrator_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->foreignUuid('administrator_id')->nullable()->after('instructor_id')->constrained('administrators')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $dbName = DB::getDatabaseName();

        if (Schema::hasColumn('leave_requests', 'administrator_id')) {
            DB::table('leave_requests')->whereNull('instructor_id')->whereNotNull('administrator_id')->delete();
            $adminFk = DB::selectOne(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'leave_requests' AND COLUMN_NAME = 'administrator_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1",
                [$dbName]
            );
            Schema::table('leave_requests', function (Blueprint $table) use ($adminFk) {
                if ($adminFk && isset($adminFk->CONSTRAINT_NAME)) {
                    $table->dropForeign($adminFk->CONSTRAINT_NAME);
                }
                $table->dropColumn('administrator_id');
            });
        }

        $instructorColumn = DB::selectOne(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'leave_requests' AND COLUMN_NAME = 'instructor_id' LIMIT 1",
            [$dbName]
        );
        if ($instructorColumn && strtoupper((string) $instructorColumn->IS_NULLABLE) === 'YES') {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->foreignUuid('instructor_id')->nullable(false)->change();
            });
        }
    }
};
