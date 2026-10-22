<?php

namespace Tests\Feature;

use App\Events\FoodCatalogChanged;
use App\Listeners\RecordAuditTrail;
use App\Models\Diagnosis;
use App\Models\Food;
use App\Models\MealPlan;
use App\Models\Patient;
use App\Models\User;
use App\Services\MealPlanService;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * The food catalog (Alimentos) kept in the application (US-4.2).
 */
class FoodCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private User $chief;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = User::factory()->create();
        $this->chief = User::factory()->chiefDoctor()->create();
    }

    /**
     * The food form (column names of the CSV), valid unless overridden.
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Pan Integral', 'group' => 'Pan', 'portion' => '1 rebanada', 'grams' => '50',
            'kcal' => '135,5', 'protein' => '5', 'fat' => '1,5', 'saturated_fat' => '0,3', 'cho' => '26',
            'glycemic_index' => '51', 'allergens' => ['gluten', 'soya'], 'price' => '120',
        ], $overrides);
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
        $avena = $this->food('Avena', 'Cereales');

        $this->actingAs($this->doctor)->get(route('home'))->assertSee(route('foods.index'));
        $this->actingAs($this->chief)->get(route('foods.index'))->assertOk()->assertSee('Avena')
            ->assertSee(route('foods.create'))->assertSee(route('foods.edit', $avena));
        $this->actingAs($this->doctor)->get(route('foods.index'))->assertOk()->assertSee('Avena')
            ->assertDontSee(route('foods.create'))->assertDontSee(route('foods.edit', $avena));

        $this->actingAs($this->doctor)->get(route('foods.create'))->assertForbidden();
        $this->actingAs($this->doctor)->post(route('foods.store'), $this->payload())->assertForbidden();
        $this->actingAs($this->doctor)->get(route('foods.edit', $avena))->assertForbidden();
        $this->actingAs($this->doctor)->put(route('foods.update', $avena), $this->payload())->assertForbidden();

        $this->actingAs($this->doctor)->patch(route('foods.retire', $avena))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->patch(route('foods.retire', $avena))->assertForbidden();

        $this->assertSame(1, Food::count());
        $this->assertSame('Avena', $avena->refresh()->name);
        $this->assertNull($avena->retired_at);
    }

    #[Group('US-4.2/AC-4')]
    public function test_retired_food_is_not_sent_to_the_generator_nor_the_editor(): void
    {
        $this->freezeSecond();
        [$diagnosis, $pollo] = $this->consultation();
        $avena = $this->food('Avena', 'Cereales');
        Http::fake(['expert:8000/meal-plan' => Http::response(['status' => 'optimo', 'days' => []]), '*' => Http::response([], 503)]);

        $this->actingAs($this->chief)->get(route('foods.index'))->assertSee(route('foods.retire', $pollo));
        $this->actingAs($this->chief)->patch(route('foods.retire', $pollo))
            ->assertRedirect(route('foods.index'))
            ->assertSessionHas('status', 'Pollo se retiró del catálogo: ya no se usa en propuestas nuevas.');

        // Not deleted: saved menus refer to it.
        $this->assertTrue($pollo->refresh()->retired_at->eq(now()));

        app(MealPlanService::class)->generate($diagnosis);
        $sent = Http::recorded()->map(fn ($pair) => $pair[0])
            ->first(fn (Request $request) => $request->url() === 'http://expert:8000/meal-plan');
        $this->assertSame([$avena->id], array_column($sent['foods'], 'id'));
        $this->assertSame([$avena->id], array_column(app(MealPlanService::class)->editorCatalog(), 'id'));

        $this->actingAs($this->chief)->get(route('foods.index'))
            ->assertSee('Pollo se retiró del catálogo') // the flashed message, not a row
            ->assertSee(route('foods.edit', $avena))
            ->assertDontSee(route('foods.edit', $pollo))
            ->assertSee('1 alimento');
    }

    #[Group('US-4.2/AC-4')]
    public function test_saved_menu_with_a_retired_food_is_shown_complete(): void
    {
        [$diagnosis, $pollo] = $this->consultation();
        $this->actingAs($this->chief)->patch(route('foods.retire', $pollo));

        // The editor rebuilds a saved menu with its foods, retired ones included.
        $plan = app(MealPlanService::class)->rebuild($diagnosis, [['meals' => [
            ['key' => 'almuerzo', 'items' => [['food_id' => $pollo->id, 'portions' => 2]]],
        ]]]);
        $almuerzo = collect($plan['days'][0]['meals'])->firstWhere('key', 'almuerzo');
        $this->assertSame('Pollo', $almuerzo['items'][0]['name']);
        $this->assertEquals(130, $almuerzo['items'][0]['energy']);

        MealPlan::create(['diagnosis_id' => $diagnosis->id, 'plan' => $plan, 'edited' => true, 'created_by' => $this->doctor->id]);
        Http::fake(['*' => Http::response([], 503)]);

        $this->actingAs($this->doctor)->get(route('result', $diagnosis))->assertOk()->assertSee('Pollo');
        Http::assertNotSent(fn (Request $request) => $request->url() === 'http://expert:8000/meal-plan');
    }

    #[Group('US-4.2/AC-7')]
    public function test_retiring_is_audited_once(): void
    {
        $this->freezeSecond();
        Event::fake([FoodCatalogChanged::class]);
        $avena = $this->food('Avena', 'Cereales');

        $this->actingAs($this->chief)->patch(route('foods.retire', $avena))->assertRedirect(route('foods.index'));
        Event::assertDispatched(FoodCatalogChanged::class, fn ($e) => $e->action === 'retired'
            && $e->food->is($avena) && $e->actor->is($this->chief)
            && $e->changes === ['retired_at' => [null, now()->toIso8601String()]]);

        // Already retired: nothing changes.
        $this->actingAs($this->chief)->patch(route('foods.retire', $avena))->assertStatus(422);
        Event::assertDispatchedTimes(FoodCatalogChanged::class, 1);
    }

    #[Group('US-4.2/AC-2')]
    public function test_chief_doctor_creates_and_edits_a_food(): void
    {
        $this->food('Pan Marraqueta', 'Pan', ['id_group' => 3]);

        $this->actingAs($this->chief)->get(route('foods.create'))->assertOk()->assertSee('Nuevo alimento');
        $this->actingAs($this->chief)->post(route('foods.store'), $this->payload())
            ->assertRedirect(route('foods.index'))
            ->assertSessionHas('status', 'Alimento creado correctamente.')
            ->assertSessionMissing('warning');

        $food = Food::where('name', 'Pan Integral')->sole();
        $this->assertSame('Pan', $food->item);
        $this->assertSame(3, (int) $food->id_group); // from the foods of the group
        $this->assertSame('1 rebanada', $food->portion);
        $this->assertSame(50.0, (float) $food->gr);
        $this->assertSame(135.5, (float) $food->kcal); // decimal comma accepted
        $this->assertSame(1.5, (float) $food->lipid);
        $this->assertSame(0.3, (float) $food->saturated_fat);
        $this->assertSame(51, (int) $food->glycemic_index);
        $this->assertSame('gluten;soya', $food->allergens);
        $this->assertSame(120.0, (float) $food->price);

        $this->actingAs($this->chief)->get(route('foods.edit', $food))->assertOk()->assertSee('Editar alimento')->assertSee('135,5');
        $this->actingAs($this->chief)->put(route('foods.update', $food), $this->payload([
            'kcal' => '140', 'glycemic_index' => '', 'allergens' => [], 'price' => '',
        ]))->assertRedirect(route('foods.index'))->assertSessionHas('status', 'Alimento actualizado correctamente.');

        $food->refresh();
        $this->assertSame(140.0, (float) $food->kcal);
        $this->assertNull($food->glycemic_index); // unknown, not 0
        $this->assertNull($food->allergens);
        $this->assertNull($food->price);
    }

    public static function invalidFoods(): array
    {
        return [
            'group the generator does not use' => [['group' => 'Snacks'], 'group'],
            'portion of 0 g' => [['grams' => '0'], 'grams'],
            'no energy' => [['kcal' => '0'], 'kcal'],
            'negative protein' => [['protein' => '-1'], 'protein'],
            'negative carbohydrates' => [['cho' => '-0,5'], 'cho'],
            'not a number' => [['fat' => 'mucho'], 'fat'],
            'unknown allergen' => [['allergens' => ['gluten', 'chocolate']], 'allergens.1'],
            'more saturated fat than fat' => [['saturated_fat' => '2'], 'saturated_fat'],
            'glycemic index out of range' => [['glycemic_index' => '200'], 'glycemic_index'],
            'no name' => [['name' => ''], 'name'],
        ];
    }

    #[Group('US-4.2/AC-2')]
    #[DataProvider('invalidFoods')]
    public function test_validation_rejects_bad_values_without_saving(array $overrides, string $field): void
    {
        $food = $this->food('Avena', 'Cereales');

        $this->actingAs($this->chief)->post(route('foods.store'), $this->payload($overrides))->assertSessionHasErrors($field);
        $this->actingAs($this->chief)->put(route('foods.update', $food), $this->payload($overrides))->assertSessionHasErrors($field);

        $this->assertSame(1, Food::count());
        $this->assertSame('Avena', $food->refresh()->name);
    }

    #[Group('US-4.2/AC-2')]
    public function test_energy_out_of_tolerance_is_saved_with_a_warning(): void
    {
        // 6 g protein = 24 kcal, declared 80 (cheese with fat left out).
        $this->actingAs($this->chief)->post(route('foods.store'), $this->payload([
            'name' => 'Queso Chanco', 'group' => 'Lácteos', 'kcal' => '80', 'protein' => '6', 'fat' => '0',
            'saturated_fat' => '', 'cho' => '0', 'glycemic_index' => '', 'allergens' => ['leche'],
        ]))
            ->assertRedirect(route('foods.index'))
            ->assertSessionHas('warning', 'Queso Chanco se guardó, pero quedó con datos a revisar. Energía: 80 kcal declaradas, 24 calculadas con 4/4/9 kcal/g (tolerancia 15 %).');

        $this->assertSame(80.0, (float) Food::where('name', 'Queso Chanco')->sole()->kcal);
        $this->actingAs($this->chief)->get(route('foods.index'))->assertSee('1 con datos a revisar');
    }

    #[Group('US-4.2/AC-3')]
    public function test_new_proposals_use_the_edited_values(): void
    {
        [$diagnosis, $pollo] = $this->consultation();
        Http::fake(['expert:8000/meal-plan' => Http::response(['status' => 'optimo', 'days' => []]), '*' => Http::response([], 503)]);

        $this->actingAs($this->chief)->put(route('foods.update', $pollo), $this->payload([
            'name' => 'Pollo', 'group' => 'Carnes', 'kcal' => '70', 'protein' => '12', 'fat' => '2', 'saturated_fat' => '0,5',
            'cho' => '0', 'glycemic_index' => '', 'allergens' => [],
        ]))->assertSessionHasNoErrors();
        app(MealPlanService::class)->generate($diagnosis);

        $sent = Http::recorded()->map(fn ($pair) => $pair[0])
            ->first(fn (Request $request) => $request->url() === 'http://expert:8000/meal-plan');
        $food = collect($sent['foods'])->firstWhere('id', $pollo->id);
        $this->assertSame(70.0, $food['kcal']);
        $this->assertSame(12.0, $food['protein']);
        $this->assertSame(0.0, $food['cho']);
    }

    #[Group('US-4.2/AC-3')]
    public function test_saved_proposals_do_not_change(): void
    {
        [$diagnosis, $pollo] = $this->consultation();
        $plan = ['status' => 'editado', 'days' => [['day' => 1, 'meals' => [['key' => 'almuerzo', 'label' => 'Almuerzo', 'items' => [
            ['food_id' => $pollo->id, 'name' => 'Pollo', 'group' => 'Carnes', 'portions' => 2, 'grams' => 100, 'energy' => 130],
        ]]]]]];
        $saved = MealPlan::create(['diagnosis_id' => $diagnosis->id, 'plan' => $plan, 'edited' => true, 'created_by' => $this->doctor->id]);

        $this->actingAs($this->chief)->put(route('foods.update', $pollo), $this->payload([
            'name' => 'Pollo', 'group' => 'Carnes', 'kcal' => '90', 'protein' => '12', 'fat' => '4,7', 'saturated_fat' => '',
            'cho' => '0', 'glycemic_index' => '', 'allergens' => [],
        ]))->assertSessionHasNoErrors();

        $this->assertSame($plan, $saved->refresh()->plan);
    }

    #[Group('US-4.2/AC-7')]
    public function test_changes_are_audited_with_old_and_new_values(): void
    {
        Event::fake([FoodCatalogChanged::class]);

        $this->actingAs($this->chief)->post(route('foods.store'), $this->payload());
        $food = Food::where('name', 'Pan Integral')->sole();
        Event::assertDispatched(FoodCatalogChanged::class, fn ($e) => $e->action === 'created'
            && $e->actor->is($this->chief) && $e->food->is($food)
            && $e->changes['kcal'] === [null, '135.5'] && $e->changes['allergens'] === [null, 'gluten;soya']);

        $this->actingAs($this->chief)->put(route('foods.update', $food), $this->payload(['kcal' => '140', 'allergens' => ['gluten']]));
        Event::assertDispatched(FoodCatalogChanged::class, fn ($e) => $e->action === 'updated'
            && $e->changes === ['kcal' => ['135.5', '140'], 'allergens' => ['gluten;soya', 'gluten']]);

        // Saving without changes does not create an audit entry ("140,0" is the same value).
        $this->actingAs($this->chief)->put(route('foods.update', $food), $this->payload(['kcal' => '140,0', 'allergens' => ['gluten']]));
        Event::assertDispatchedTimes(FoodCatalogChanged::class, 2);
    }

    #[Group('US-4.2/AC-7')]
    public function test_audit_trail_records_who_when_and_the_values(): void
    {
        $food = new Food;
        $food->id = 12;

        $channel = Mockery::mock();
        $channel->shouldReceive('info')->once()->with('food.updated', Mockery::on(
            fn ($context) => $context['actor_id'] === $this->chief->id
                && $context['actor_role'] === 'ChiefDoctor'
                && $context['food_id'] === 12
                && $context['changes'] === ['kcal' => ['140', '150']]
        ));
        Log::shouldReceive('channel')->with('audit')->andReturn($channel);

        (new RecordAuditTrail)->handleFoodCatalogChanged(new FoodCatalogChanged($food, $this->chief, 'updated', ['kcal' => ['140', '150']]));
    }

    /**
     * A consultation with energy and macronutrient targets, and a food of its menus.
     *
     * @return array{0: Diagnosis, 1: Food}
     */
    private function consultation(): array
    {
        config(['services.inference.url' => null, 'services.expert.url' => 'http://expert:8000', 'services.expert.token' => 't0k']);
        $this->seed(RulesSeeder::class);
        $diagnosis = Diagnosis::create([
            'id_patient' => Patient::factory()->create()->id, 'age' => 36, 'weight' => 80, 'size' => 165, 'imc' => 29.38,
            'physical_activity' => 2, 'id_rule' => 3, 'created_by' => $this->doctor->id,
            'result_pulgar' => 1750, 'carbohydrate' => 197, 'protein' => 88, 'lipido' => 68,
        ]);

        return [$diagnosis, $this->food('Pollo', 'Carnes', ['id_group' => 4, 'kcal' => '65', 'protein' => '11', 'lipid' => '2', 'cho' => '1', 'glycemic_index' => null])];
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
