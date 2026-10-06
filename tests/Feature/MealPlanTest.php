<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\Food;
use App\Models\MealPlan;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MealPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Diagnosis $diagnosis;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.inference.url' => null,
            'services.expert.url' => 'http://expert:8000',
            'services.expert.token' => 't0k',
        ]);
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
        Food::create(['id_group' => 4, 'name' => 'Pollo', 'item' => 'Carnes', 'portion' => '1', 'kcal' => '65', 'protein' => '11', 'lipid' => '2', 'saturated_fat' => 0.5, 'cho' => '1', 'clna_mg' => '0', 'k_mg' => '0', 'p_mg' => '0', 'ca_mg' => '0', 'gr' => '50']);
        Food::create(['id_group' => 10, 'name' => 'Aceite de Oliva', 'item' => 'Aceites', 'portion' => '1', 'kcal' => '180', 'protein' => '0', 'lipid' => '15', 'saturated_fat' => 2.1, 'cho' => '0', 'clna_mg' => '0', 'k_mg' => '0', 'p_mg' => '0', 'ca_mg' => '0', 'gr' => '0']);
        Food::create(['id_group' => 3, 'name' => 'Pan Marraqueta', 'item' => 'Pan', 'portion' => '1', 'kcal' => '140', 'protein' => '3', 'lipid' => '1', 'saturated_fat' => 0.2, 'cho' => '30', 'glycemic_index' => 75, 'clna_mg' => '0', 'k_mg' => '0', 'p_mg' => '0', 'ca_mg' => '0', 'gr' => '50']);
    }

    private function day(int $number, string $food = 'Pollo'): array
    {
        return [
            'day' => $number,
            'totals' => ['energy' => 1762.5, 'carbohydrates' => 195, 'proteins' => 87.5, 'fats' => 66, 'glycemic_load' => 91.4, 'saturated_fat' => 13.9],
            'deviation_percent' => ['energy' => 0.7, 'carbohydrates' => -1, 'proteins' => -0.6, 'fats' => -2.9],
            'meals' => [[
                'key' => 'almuerzo', 'label' => 'Almuerzo', 'energy_target' => 525,
                'items' => [
                    ['food_id' => 1, 'name' => $food, 'group' => 'Carnes', 'portions' => 2.5, 'grams' => 125, 'energy' => 162.5, 'carbohydrates' => 2.5, 'proteins' => 27.5, 'fats' => 5, 'glycemic_load' => 0, 'saturated_fat' => 1.3],
                    ['food_id' => 2, 'name' => 'Aceite de Oliva', 'group' => 'Aceites', 'portions' => 0.5, 'grams' => null, 'energy' => 90, 'carbohydrates' => 0, 'proteins' => 0, 'fats' => 7.5, 'glycemic_load' => 0, 'saturated_fat' => 1.1],
                ],
                'totals' => ['energy' => 252.5, 'carbohydrates' => 2.5, 'proteins' => 27.5, 'fats' => 12.5, 'glycemic_load' => 0, 'saturated_fat' => 2.4],
            ]],
            'notes' => [],
        ];
    }

    /**
     * A trimmed response of POST /meal-plan (expert/app/meal_plan.py).
     */
    private function plan(int $days = 1): array
    {
        return [
            'status' => 'optimo', 'seed' => 1000,
            'targets' => ['energy' => 1750, 'carbohydrates' => 197, 'proteins' => 88, 'fats' => 68],
            'limits' => ['glycemic_load' => 100, 'saturated_fat' => 14],
            'days' => array_map(fn ($n) => $this->day($n, $n === 1 ? 'Pollo' : 'Pavo del día '.$n), range(1, $days)),
            'notes' => ['Sin valores nutricionales, no se usan: Mote Crudo.'],
        ];
    }

    private function sentPlanRequests(): array
    {
        return array_values(array_filter(
            Http::recorded()->map(fn ($pair) => $pair[0])->all(),
            fn (Request $request) => $request->url() === 'http://expert:8000/meal-plan'
        ));
    }

    public function test_result_page_shows_the_generated_proposal(): void
    {
        Http::fake(['expert:8000/meal-plan' => Http::response($this->plan()), '*' => Http::response([], 503)]);

        $this->actingAs($this->doctor)->get("/result/{$this->diagnosis->id}")
            ->assertOk()
            ->assertSeeInOrder(['Propuesta de menú', 'Sin guardar', 'Revisar y guardar', 'Otra variante', '1 día', '3 días', '7 días'])
            ->assertSeeInOrder(['Energía', '1.762,5 kcal', '/ 1.750', '+0,7 %', 'Carga glucémica', '91,4', 'máx. 100'])
            ->assertSeeInOrder(['Almuerzo', '252,5 / 525 kcal', 'Pollo:', '125 g', '2,5 porciones', 'Aceite de Oliva:', '0,5 porciones'])
            ->assertSee('Sin valores nutricionales, no se usan: Mote Crudo.');

        [$request] = $this->sentPlanRequests();
        $body = json_decode($request->body(), true);
        $this->assertEquals(['energy' => 1750, 'carbohydrates' => 197, 'proteins' => 88, 'fats' => 68], $body['targets']);
        $this->assertSame([$this->diagnosis->id * 1000, 1], [$body['seed'], $body['days']]);
        $this->assertEquals(['id' => 1, 'name' => 'Pollo', 'item' => 'Carnes', 'grams' => 50, 'kcal' => 65, 'protein' => 11, 'fat' => 2, 'saturated_fat' => 0.5, 'cho' => 1, 'glycemic_index' => null], $body['foods'][0]);
        // No macronutrient plan (the evaluation failed): the general ceilings.
        $this->assertEquals(['glycemic_load' => 120, 'saturated_fat' => 19], $body['limits']);
    }

    public function test_several_days_and_variant(): void
    {
        Http::fake(['expert:8000/meal-plan' => Http::response($this->plan(3)), '*' => Http::response([], 503)]);

        $this->actingAs($this->doctor)->get("/result/{$this->diagnosis->id}?dias=3&variante=2")
            ->assertOk()
            ->assertSeeInOrder(['Día 1', 'Día 2', 'Día 3'])
            ->assertSee('Pavo del día 3')
            ->assertSee(e(route('download', ['diagnosis' => $this->diagnosis, 'variante' => 2, 'dias' => 3])), false);

        $body = json_decode($this->sentPlanRequests()[0]->body(), true);
        $this->assertSame([$this->diagnosis->id * 1000 + 2, 3], [$body['seed'], $body['days']]);
    }

    public function test_ceilings_come_from_the_macronutrient_plan(): void
    {
        $evaluation = ['indices' => [], 'ruleset_version' => '2026.10.5.1', 'findings' => [], 'assessments' => [
            'glycemic_status' => ['status' => 'normal', 'evidence' => []],
            'insulin_resistance' => ['status' => 'indeterminado', 'positive' => 0, 'evaluated' => 0, 'evidence' => []],
            'metabolic_syndrome' => ['status' => 'indeterminado', 'met' => 0, 'unknown' => 5, 'criteria' => []],
        ], 'macronutrients' => ['status' => 'calculado', 'rules' => [], 'notes' => [],
            'energy' => ['bmr' => 1400, 'activity_level' => 'Moderada', 'activity_factor' => 1.55, 'maintenance' => 2170, 'adjustment' => -500, 'target' => 1670],
            'macros' => [
                'carbohydrates' => ['label' => 'Carbohidratos', 'percent' => 45, 'grams' => 188, 'kcal' => 752],
                'proteins' => ['label' => 'Proteínas', 'percent' => 20, 'grams' => 84, 'kcal' => 334],
                'fats' => ['label' => 'Grasas', 'percent' => 35, 'grams' => 65, 'kcal' => 585],
            ],
            'limits' => [
                'saturated_fat' => ['label' => 'Grasas saturadas', 'comparator' => 'max', 'amount' => 14, 'unit' => 'g', 'percent' => 7],
                'sodium' => ['label' => 'Sodio', 'comparator' => 'max', 'amount' => 1500, 'unit' => 'mg'],
                'glycemic_load' => ['label' => 'Carga glucémica', 'comparator' => 'max', 'amount' => 80, 'unit' => ''],
            ]]];
        Http::fake(['expert:8000/evaluate' => Http::response($evaluation), 'expert:8000/meal-plan' => Http::response($this->plan())]);

        $this->actingAs($this->doctor)->get("/result/{$this->diagnosis->id}")->assertOk();

        $body = json_decode($this->sentPlanRequests()[0]->body(), true);
        $this->assertEquals(['glycemic_load' => 80, 'saturated_fat' => 14], $body['limits']);
        Http::assertSentCount(2); // the evaluation of the page is reused
    }

    public function test_saved_proposal_is_shown_instead_of_generating_one(): void
    {
        MealPlan::create(['diagnosis_id' => $this->diagnosis->id, 'plan' => $this->plan(), 'edited' => true, 'created_by' => $this->doctor->id]);
        Http::fake(['*' => Http::response([], 503)]);

        $this->actingAs($this->doctor)->get("/result/{$this->diagnosis->id}")
            ->assertOk()
            ->assertSeeInOrder(['Propuesta de menú', 'Guardada', 'por '.$this->doctor->name, 'ajustada a mano', 'Ajustar', 'Generar nueva propuesta', 'Descartar'])
            ->assertSee('Pollo:')
            ->assertDontSee('Otra variante');

        $this->assertSame([], $this->sentPlanRequests());

        // The PDF has the saved proposal too.
        $this->actingAs($this->doctor)->get("/download/{$this->diagnosis->id}")->assertOk();
        $this->assertSame([], $this->sentPlanRequests());
    }

    public function test_pdf_view_lists_the_days(): void
    {
        $html = view('diagnoses._meal-plan-pdf', ['mealPlan' => $this->plan(2)])->render();

        $this->assertStringContainsString('Día 2', $html);
        $this->assertStringContainsString('Pavo del día 2', $html);
        $this->assertStringContainsString('125 g', $html);
        $this->assertStringContainsString('Carga glucémica 91,4', $html);
    }

    public function test_pdf_generates_the_same_variant(): void
    {
        Http::fake(['expert:8000/meal-plan' => Http::response($this->plan()), '*' => Http::response([], 503)]);

        $this->actingAs($this->doctor)->get("/download/{$this->diagnosis->id}?variante=2&dias=3")->assertOk();

        $body = json_decode($this->sentPlanRequests()[0]->body(), true);
        $this->assertSame([$this->diagnosis->id * 1000 + 2, 3], [$body['seed'], $body['days']]);
    }

    public function test_without_targets_nothing_is_requested(): void
    {
        $this->diagnosis->update(['result_pulgar' => null]);
        Http::fake(['*' => Http::response([], 503)]);

        $this->actingAs($this->doctor)->get("/result/{$this->diagnosis->id}")
            ->assertOk()
            ->assertSee('la consulta necesita requerimiento energético');
        $this->assertSame([], $this->sentPlanRequests());
    }

    public function test_service_down(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->actingAs($this->doctor)->get("/result/{$this->diagnosis->id}")
            ->assertOk()
            ->assertSee('El sistema experto no está disponible para generar la propuesta.');
    }

    public function test_old_exchange_tables_are_gone(): void
    {
        Http::fake(['*' => Http::response([], 503)]);

        $this->actingAs($this->doctor)->get("/result/{$this->diagnosis->id}")
            ->assertOk()
            ->assertDontSee('Resultado Método Pulgar')
            ->assertDontSee('id="example"', false);
    }
}
