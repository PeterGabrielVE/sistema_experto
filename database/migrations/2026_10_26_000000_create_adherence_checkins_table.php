<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How the patient followed each meal of the menu of a day (US-6.2). A meal not marked has no row:
 * it is "sin dato", never "no cumplí" (constitution IV).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adherence_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('meal_plan_id')->nullable()->constrained('meal_plans')->nullOnDelete();
            $table->date('date'); // day of the menu, clinic timezone
            $table->unsignedTinyInteger('plan_day');
            $table->string('meal_key', 32);
            $table->string('meal_label', 64); // shown even if the plan changes
            $table->string('status', 8); // done | partial | missed
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['patient_id', 'date', 'meal_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adherence_checkins');
    }
};
