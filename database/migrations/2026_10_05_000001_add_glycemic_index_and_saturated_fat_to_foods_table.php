<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Glycemic index and saturated fat per exchange portion, so the generated meal plan
 * can respect the glycemic load and saturated fat limits. Fills the seeded catalog.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->unsignedTinyInteger('glycemic_index')->nullable()->after('cho'); // glucose = 100
            $table->decimal('saturated_fat', 4, 1)->nullable()->after('lipid'); // g per portion
        });

        foreach (require database_path('data/food_quality.php') as $name => [$glycemicIndex, $saturatedFat]) {
            DB::table('foods')->where('name', $name)->update(['glycemic_index' => $glycemicIndex, 'saturated_fat' => $saturatedFat]);
        }
    }

    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dropColumn(['glycemic_index', 'saturated_fat']);
        });
    }
};
