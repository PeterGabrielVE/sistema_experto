<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Portal accounts of patients (US-6.3): a user with the Patient role tied to its patient.
 * Deleting the patient deletes the account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->unique()->constrained('patients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Without patient_id they would be accounts of nobody.
        DB::table('users')->where('rol_id', 4)->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patient_id');
        });
    }
};
