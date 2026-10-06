<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\Food;
use App\Models\MealPlan;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MealPlanEditorTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Diagnosis $diagnosis;

    private Food $chicken;

    private Food $bread;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.inference.url' => null, 'services.expert.url' => 'http://expert:8000', 'services.expert.token' => 't0k']);
        $this->seed(RulesSeeder::class);

        $this->doctor = User::factory()->create();
        $patient = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => '11111111-1',
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01',
        ]);
        $this->diagnosis = Diagnosis::create([
            'id_patient' => $patient->id, 'age' => 36, 'weight' => 80, 'size' => 165, 'imc' => 29.38,
            'physical_activity' => 2, 'id_rule' => 3, 'created_by' => $this->doctor->id,
            'result_pulgar' => 1750, 'carbohydrate' => 197, 'protein' => 88, 'lipido' => 68,
        ]);
        $this->chicken = Food::create(['id_group' => 4, 'name' => 'Pollo', 'item' => 'Carnes', 'portion' => '1', 'kcal' => '65', 'protein' => '11', 'lipid' => '2', 'saturated_fat' => 0.5, 'cho' => '1', 'clna_mg' => '0', 'k_mg' => '0', 'p_mg' => '0', 'ca_mg' => '0', 'gr' => '50']);
        $this->bread = Food::create(['id_group' => 3, 'name' => 'Pan Marraqueta', 'item' => 'Pan', 'portion' => '1', 'kcal' => '140', 'protein' => '3', 'lipid' => '1', 'saturated_fat' => 0.2, 'cho' => '30', 'glycemic_index' => 75, 'clna_mg' => '0', 'k_mg' => '0', 'p_mg' => '0', 'ca_mg' => '0', 'gr' => '50']);
        Food::create(['id_group' => 3, 'name' => 'Mote Crudo', 'item' => 'Cereales', 'portion' => '1', 'kcal' => '0', 'protein' => '0', 'lipid' => '0', 'cho' => '0', 'clna_mg' => '0', 'k_mg' => '0', 'p_mg' => '0', 'ca_mg' => '0', 'gr' => '100']);
        Http::fake(['*' => Http::response([], 503)]); // the expert service is not needed to edit and save
    }

    private function save(array $days, bool $generated = false)
    {
        return $this->actingAs($this->doctor)->put("/result/{$this->diagnosis->id}/menu", [
            'plan' => json_encode(['days' => $days]),
            'generated' => $generated ? '1' : '0',
        ]);
    }

    public function test_saves_the_menu_recomputing_every_amount(): void
    {
        $this->save([
            ['meals' => [
                ['key' => 'desayuno', 'items' => [['food_id' => $this->bread->id, 'portions' => 1.5]]],
                // Nutrients sent by the browser are ignored.
                ['key' => 'almuerzo', 'items' => [['food_id' => $this->chicken->id, 'portions' => 2, 'energy' => 99999]]],
            ]],
            ['meals' => [['key' => 'cena', 'items' => [['food_id' => $this->chicken->id, 'portions' => 3]]]]],
        ])->assertRedirect(route('result', $this->diagnosis).'#meal-plan');

        $saved = MealPlan::firstOrFail();
        $plan = $saved->plan;
        $this->assertTrue($saved->edited);
        $this->assertSame($this->doctor->id, (int) $saved->created_by);
        $this->assertSame('editado', $plan['status']);
        $this->assertCount(2, $plan['days']);
        $this->assertSame(['desayuno', 'colacion_matutina', 'almuerzo', 'colacion_vespertina', 'cena'], array_column($plan['days'][0]['meals'], 'key'));

        $bread = $plan['days'][0]['meals'][0]['items'][0];
        $this->assertEquals(['name' => 'Pan Marraqueta', 'portions' => 1.5, 'grams' => 75, 'energy' => 210, 'carbohydrates' => 45, 'glycemic_load' => 33.8], array_intersect_key($bread, array_flip(['name', 'portions', 'grams', 'energy', 'carbohydrates', 'glycemic_load'])));
        $this->assertEquals(130, $plan['days'][0]['meals'][2]['items'][0]['energy']);
        $this->assertEquals(340, $plan['days'][0]['totals']['energy']); // 210 + 130
        $this->assertEquals(round((340 - 1750) / 1750 * 100, 1), $plan['days'][0]['deviation_percent']['energy']);
        $this->assertEquals(195, $plan['days'][1]['totals']['energy']);
        $this->assertEquals(['glycemic_load' => 120, 'saturated_fat' => 19], $plan['limits']);

        $this->actingAs($this->doctor)->get("/result/{$this->diagnosis->id}")
            ->assertSeeInOrder(['Guardada', 'ajustada a mano', 'Día 1', 'Día 2', 'Pan Marraqueta:', '75 g']);
    }

    public function test_saved_as_generated_is_not_marked_as_edited(): void
    {
        $this->save([['meals' => [['key' => 'almuerzo', 'items' => [['food_id' => $this->chicken->id, 'portions' => 1]]]]]], generated: true);

        $this->assertFalse(MealPlan::firstOrFail()->edited);
        $this->assertSame('generado', MealPlan::firstOrFail()->plan['status']);
    }

    public function test_validation(): void
    {
        $this->save([['meals' => [['key' => 'merienda', 'items' => [['food_id' => 999, 'portions' => 0.3]]]]]])
            ->assertSessionHasErrors(['days.0.meals.0.key', 'days.0.meals.0.items.0.food_id', 'days.0.meals.0.items.0.portions']);

        $this->actingAs($this->doctor)->put("/result/{$this->diagnosis->id}/menu", ['plan' => 'no es json'])
            ->assertSessionHasErrors('days');

        $this->save(array_fill(0, 8, ['meals' => [['key' => 'cena', 'items' => []]]]))->assertSessionHasErrors('days');

        $this->assertSame(0, MealPlan::count());
    }

    public function test_editor_starts_from_the_saved_proposal_or_a_new_one(): void
    {
        $this->save([['meals' => [['key' => 'almuerzo', 'items' => [['food_id' => $this->chicken->id, 'portions' => 2]]]]]]);

        $this->actingAs($this->doctor)->get("/result/{$this->diagnosis->id}/menu")
            ->assertOk()
            ->assertSee('Guardar propuesta')
            ->assertSee('"almuerzo":[{"food_id":'.$this->chicken->id.',"portions":2', false)
            ->assertSee('id="input-generated" value="0"', false)
            // The catalog of the editor leaves out foods without nutrients.
            ->assertSee('"name":"Pan Marraqueta"', false)
            ->assertDontSee('"name":"Mote Crudo"', false);
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/meal-plan'));

        // A new one asks the expert service; when it fails the editor starts empty.
        $this->actingAs($this->doctor)->get("/result/{$this->diagnosis->id}/menu?nueva=1&dias=3")
            ->assertOk()
            ->assertSee('arme el menú a mano')
            ->assertSee('"desayuno":[]', false);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/meal-plan') && json_decode($request->body(), true)['days'] === 3);
    }

    public function test_discard_returns_to_the_automatic_proposal(): void
    {
        MealPlan::create(['diagnosis_id' => $this->diagnosis->id, 'plan' => ['days' => [], 'notes' => []], 'edited' => false]);

        $this->actingAs($this->doctor)->delete("/result/{$this->diagnosis->id}/menu")
            ->assertRedirect(route('result', $this->diagnosis).'#meal-plan');
        $this->assertSame(0, MealPlan::count());
    }

    public function test_without_targets_the_editor_is_not_available(): void
    {
        $this->diagnosis->update(['carbohydrate' => null]);

        $this->actingAs($this->doctor)->get("/result/{$this->diagnosis->id}/menu")
            ->assertRedirect(route('result', $this->diagnosis))
            ->assertSessionHasErrors('menu');
    }

    public function test_only_doctors_edit_proposals(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get("/result/{$this->diagnosis->id}/menu")->assertForbidden();
        $this->actingAs($admin)->put("/result/{$this->diagnosis->id}/menu", ['plan' => '{"days":[]}'])->assertForbidden();
        $this->actingAs($admin)->delete("/result/{$this->diagnosis->id}/menu")->assertForbidden();
    }
}
