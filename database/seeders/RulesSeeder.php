<?php

namespace Database\Seeders;

use App\Models\Rule;
use Illuminate\Database\Seeder;

class RulesSeeder extends Seeder
{
    /**
     * Seed the BMI categories. The ids are fixed: the inference service returns them as rule_id
     * (InferenceEngine::CATEGORIES), so they must not depend on the auto-increment counter.
     */
    public function run(): void
    {
        Rule::query()->upsert([
            ['id' => 1, 'name' => 'Bajo peso', 'min' => '0', 'max' => '18.4'],
            ['id' => 2, 'name' => 'Normal', 'min' => '18.5', 'max' => '24.9'],
            ['id' => 3, 'name' => 'Sobrepeso', 'min' => '25.0', 'max' => '29.9'],
            ['id' => 4, 'name' => 'Obesidad', 'min' => '30.0', 'max' => 'más'],
        ], ['id'], ['name', 'min', 'max']);
    }
}
