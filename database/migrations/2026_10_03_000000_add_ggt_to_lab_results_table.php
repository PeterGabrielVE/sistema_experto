<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GGT (gamma-glutamil transferasa), needed by the fatty liver index (FLI).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_results', function (Blueprint $table) {
            $table->decimal('ggt', 6, 1)->nullable()->after('triglycerides'); // U/L
        });
    }

    public function down(): void
    {
        Schema::table('lab_results', function (Blueprint $table) {
            $table->dropColumn('ggt');
        });
    }
};
