<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A measurement can be linked to the consultation (diagnoses) where it was taken.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_measurements', function (Blueprint $table) {
            $table->foreignId('diagnosis_id')->nullable()->after('patient_id')->constrained('diagnoses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clinical_measurements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diagnosis_id');
        });
    }
};
