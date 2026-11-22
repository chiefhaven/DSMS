<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['administrators', 'instructors'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'terms_of_employment')) {
                    $table->text('terms_of_employment')->nullable();
                }
                if (!Schema::hasColumn($tableName, 'contract_signed')) {
                    $table->boolean('contract_signed')->default(false);
                }
                if (!Schema::hasColumn($tableName, 'contract_signed_at')) {
                    $table->date('contract_signed_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['administrators', 'instructors'] as $tableName) {
            $columns = array_values(array_filter(
                ['terms_of_employment', 'contract_signed', 'contract_signed_at'],
                fn ($column) => Schema::hasColumn($tableName, $column)
            ));

            if ($columns !== []) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }
    }
};
