<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * The food catalog (Alimentos) kept in the application (US-4.2).
 */
class FoodCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = User::factory()->create();
    }

    /**
     * A food per exchange portion; energy matches 4/4/9 kcal/g unless overridden.
     */
    private function food(string $name, string $group, array $attributes = []): Food
    {
        return Food::create([
            'name' => $name, 'item' => $group, 'id_group' => 3, 'portion' => '1', 'gr' => '50',
            'kcal' => '140', 'protein' => '3', 'lipid' => '0', 'cho' => '32', 'glycemic_index' => 70,
            'clna_mg' => '0', 'k_mg' => '0', 'p_mg' => '0', 'ca_mg' => '0',
            ...$attributes,
        ]);
    }

    #[Group('US-4.2/AC-1')]
    public function test_doctor_lists_searches_and_filters_active_foods(): void
    {
        $this->food('Pan Marraqueta', 'Pan', ['allergens' => 'gluten', 'price' => 180, 'saturated_fat' => 0.2]);
        $this->food('Avena', 'Cereales', ['glycemic_index' => 55]);
        $this->food('Pollo', 'Carnes', ['kcal' => '65', 'protein' => '11', 'lipid' => '2', 'cho' => '1', 'glycemic_index' => null, 'price' => 300]);

        $this->actingAs($this->doctor)->get(route('foods.index'))
            ->assertOk()
            ->assertSeeInOrder(['Carnes', 'Pollo', 'Cereales', 'Avena', 'Pan', 'Pan Marraqueta']) // by group and name
            ->assertSee('Gluten') // allergen label of meal_plan.allergens
            ->assertSee('180')
            ->assertSee('3 alimentos');

        $this->actingAs($this->doctor)->get(route('foods.index', ['q' => 'marra']))
            ->assertOk()
            ->assertSee('Pan Marraqueta')
            ->assertDontSee('Avena')
            ->assertDontSee('Pollo')
            ->assertSee('1 alimento');

        $this->actingAs($this->doctor)->get(route('foods.index', ['grupo' => 'Carnes']))
            ->assertOk()
            ->assertSee('Pollo')
            ->assertDontSee('Pan Marraqueta')
            ->assertDontSee('Avena');

        $this->actingAs($this->doctor)->get(route('foods.index', ['q' => 'pollo', 'grupo' => 'Pan']))
            ->assertOk()
            ->assertSee('Ningún alimento coincide con la búsqueda.');
    }

    #[Group('US-4.2/AC-1')]
    public function test_list_marks_energy_mismatch_and_missing_glycemic_index(): void
    {
        $this->food('Queso Chanco', 'Lácteos', ['kcal' => '80', 'protein' => '6', 'lipid' => '0', 'cho' => '0', 'glycemic_index' => null]);
        $this->food('Choclo', 'Cereales', ['glycemic_index' => null]);
        $this->food('Avena', 'Cereales');

        $this->actingAs($this->doctor)->get(route('foods.index'))
            ->assertOk()
            ->assertSee('Energía: 80 kcal declaradas, 24 calculadas con 4/4/9 kcal/g (tolerancia 15 %).')
            ->assertSee('Sin índice glicémico, con 32 g de carbohidratos por porción.')
            ->assertSee('2 con datos a revisar');
    }

    #[Group('US-4.2/AC-6')]
    public function test_doctor_can_view_but_not_change_the_catalog(): void
    {
        $this->food('Avena', 'Cereales');

        $this->actingAs($this->doctor)->get(route('home'))->assertSee(route('foods.index'));
        $this->actingAs(User::factory()->chiefDoctor()->create())->get(route('foods.index'))->assertOk()->assertSee('Avena');
        $this->actingAs($this->doctor)->get(route('foods.index'))->assertOk()->assertSee('Avena');
    }

    #[Group('US-4.2/AC-6')]
    public function test_administrator_cannot_see_the_catalog(): void
    {
        $this->get(route('foods.index'))->assertRedirect(route('login'));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('foods.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('home'))->assertDontSee(route('foods.index'));
    }

    #[Group('US-4.2')] // NFR-1
    public function test_lists_five_hundred_foods_in_under_a_second(): void
    {
        $now = now();
        Food::insert(collect(range(1, 500))->map(fn ($i) => [
            'name' => "Alimento {$i}", 'item' => ['Pan', 'Cereales', 'Carnes', 'Frutas'][$i % 4], 'id_group' => 3,
            'portion' => '1', 'gr' => '50', 'kcal' => '140', 'protein' => '3', 'lipid' => '0', 'cho' => '32',
            'glycemic_index' => $i % 3 ? 60 : null, 'clna_mg' => '0', 'k_mg' => '0', 'p_mg' => '0', 'ca_mg' => '0',
            'created_at' => $now, 'updated_at' => $now,
        ])->all());
        $this->actingAs($this->doctor)->get(route('foods.index'))->assertOk(); // warm up views and config

        $start = microtime(true);
        $this->actingAs($this->doctor)->get(route('foods.index'))->assertOk()->assertSee('500 alimentos');

        $this->assertLessThan(1.0, microtime(true) - $start);
    }
}
