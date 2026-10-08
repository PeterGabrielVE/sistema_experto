<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allergens and price per exchange portion, so the generated meal plan leaves out the foods the
 * patient is allergic to and fits a budget. Filled by php artisan foods:import (shared/food_catalog.csv).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->string('allergens', 200)->nullable()->after('glycemic_index'); // tags of meal_plan.allergens, separated by ;
            $table->decimal('price', 8, 1)->nullable()->after('allergens'); // CLP per portion
        });
    }

    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dropColumn(['allergens', 'price']);
        });
    }
};
