<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Answers of the FINDRISC questionnaire (type 2 diabetes risk) that are not
 * measured: age, BMI and waist come from the consultation and measurements.
 * Null: not asked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_records', function (Blueprint $table) {
            $table->boolean('daily_physical_activity')->nullable()->after('water_liters');
            $table->boolean('daily_fruit_vegetables')->nullable()->after('daily_physical_activity');
            $table->boolean('antihypertensive_medication')->nullable()->after('daily_fruit_vegetables');
            $table->boolean('high_glucose_history')->nullable()->after('antihypertensive_medication');
            $table->string('family_history_diabetes', 15)->nullable()->after('high_glucose_history');
        });
    }

    public function down(): void
    {
        Schema::table('clinical_records', function (Blueprint $table) {
            $table->dropColumn([
                'daily_physical_activity', 'daily_fruit_vegetables', 'antihypertensive_medication',
                'high_glucose_history', 'family_history_diabetes',
            ]);
        });
    }
};
