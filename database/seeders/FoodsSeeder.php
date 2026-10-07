<?php

namespace Database\Seeders;

use App\Services\FoodCatalogImporter;
use Illuminate\Database\Seeder;

class FoodsSeeder extends Seeder
{
    /**
     * The food composition catalog of shared/food_catalog.csv (php artisan foods:import).
     */
    public function run(FoodCatalogImporter $importer): void
    {
        $importer->import();
    }
}
