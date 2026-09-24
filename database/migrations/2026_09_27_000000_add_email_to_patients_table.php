<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patient contact email. Age is not stored: it is derived from birthdate
 * (Patient::$age) so it never gets out of date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Not unique: relatives may share an address; the RUT identifies the patient.
            $table->string('email', 191)->nullable()->after('rut')->index();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn('email');
        });
    }
};
