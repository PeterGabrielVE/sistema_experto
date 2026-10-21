<?php

namespace Tests\Feature;

use App\Models\Food;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Data of the food catalog to review (US-4.2/AC-1), with the thresholds of
 * meal_plan.catalog_review in shared/clinical_thresholds.json, the same the expert service uses.
 */
#[Group('US-4.2/AC-1')]
class FoodReviewFlagsTest extends TestCase
{
    /**
     * 100 kcal declared; 4/4/9 kcal/g give $computed kcal.
     */
    private function food(float $computed, array $attributes = []): Food
    {
        return new Food([
            'name' => 'Prueba', 'item' => 'Cereales', 'kcal' => '100',
            'protein' => '0', 'lipid' => '0', 'cho' => (string) ($computed / 4), 'glycemic_index' => 55,
            ...$attributes,
        ]);
    }

    public function test_thresholds_come_from_the_shared_file(): void
    {
        $shared = json_decode(file_get_contents(base_path('shared/clinical_thresholds.json')), true);

        $this->assertSame(15, $shared['meal_plan']['catalog_review']['energy_tolerance_percent']);
        $this->assertSame(5, $shared['meal_plan']['catalog_review']['glycemic_index_min_cho']);
        $this->assertNotEmpty($shared['meal_plan']['catalog_review']['source']);
        $this->assertSame($shared['meal_plan']['catalog_review'], config('clinical.meal_plan.catalog_review'));
    }

    public function test_review_flags_use_the_shared_thresholds(): void
    {
        $this->assertSame([], $this->food(114)->reviewFlags()); // 14 % off
        $this->assertSame([], $this->food(86)->reviewFlags());

        $flags = $this->food(116)->reviewFlags(); // 16 % off
        $this->assertSame(['energy'], array_keys($flags));
        $this->assertSame('Energía: 100 kcal declaradas, 116 calculadas con 4/4/9 kcal/g (tolerancia 15 %).', $flags['energy']);

        // Read from the configuration, not written in the code.
        config(['clinical.meal_plan.catalog_review.energy_tolerance_percent' => 20]);
        $this->assertSame([], $this->food(116)->reviewFlags());
    }

    public function test_energy_uses_protein_fat_and_carbohydrates(): void
    {
        // 2 g protein (8) + 4 g fat (36) + 14 g carbohydrates (56) = 100 kcal.
        $food = new Food(['kcal' => '100', 'protein' => '2', 'lipid' => '4', 'cho' => '14', 'glycemic_index' => 40]);
        $this->assertSame([], $food->reviewFlags());

        // Legacy text columns with a decimal comma.
        $food = new Food(['kcal' => '100', 'protein' => '2', 'lipid' => '4,0', 'cho' => '14,0', 'glycemic_index' => 40]);
        $this->assertSame([], $food->reviewFlags());
    }

    public function test_flags_carbohydrates_without_glycemic_index(): void
    {
        $this->assertSame(
            ['glycemic_index' => 'Sin índice glicémico, con 5 g de carbohidratos por porción.'],
            $this->food(20, ['kcal' => '20', 'glycemic_index' => null])->reviewFlags(),
        );
        // Under the minimum (4,9 g) it does not count for the glycemic load.
        $this->assertSame([], $this->food(19.6, ['kcal' => '19.6', 'glycemic_index' => null])->reviewFlags());
        // A glycemic index of 0 is a value, not a missing one.
        $this->assertSame([], $this->food(20, ['kcal' => '20', 'glycemic_index' => 0])->reviewFlags());
    }

    public function test_a_food_without_energy_is_not_compared(): void
    {
        $this->assertArrayNotHasKey('energy', $this->food(40, ['kcal' => '0'])->reviewFlags());
    }
}
