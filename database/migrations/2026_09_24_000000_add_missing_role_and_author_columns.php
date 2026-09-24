<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The application uses users.rol_id and patients/diagnoses.created_by, but
 * no migration created them. Existing databases may already have them
 * (added by hand), so every column is only added when missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'rol_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedTinyInteger('rol_id')->nullable()->after('password');
            });
        }

        foreach (['patients', 'diagnoses'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'created_by')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('created_by')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        // Intentionally left empty: these columns may pre-date this migration.
    }
};
