<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The menu proposal accepted for a consultation (one per consultation), as generated
 * by the expert service or adjusted by the doctor. The result page and the PDF show it
 * instead of a freshly generated one, so it does not change with the catalog or rules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('diagnosis_id')->unique();
            $table->json('plan'); // MealPlan of expert/app/responses.py
            $table->boolean('edited')->default(false); // adjusted by hand
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plans');
    }
};
