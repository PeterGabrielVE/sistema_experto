<?php

namespace Tests\Feature;

use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MacroPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.expert.url' => 'http://expert:8000', 'services.expert.token' => 't0k']);

        $this->doctor = User::factory()->create();
        $this->patient = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => '11111111-1',
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01',
        ]);
    }

    private function form(array $overrides = []): array
    {
        return ['weight' => 80, 'size' => 165, 'age' => 35, 'physical_activity' => 1, ...$overrides];
    }

    /**
     * A trimmed response of POST /evaluate with its macronutrients (expert/app/nutrition.py).
     */
    private function evaluation(array $plan): array
    {
        return ['indices' => [], 'assessments' => [], 'findings' => [], 'macronutrients' => $plan, 'ruleset_version' => '2026.10.5'];
    }

    private function calculatedPlan(): array
    {
        return [
            'status' => 'calculado',
            'energy' => ['bmr' => 1470, 'activity_level' => 'Ligera', 'activity_factor' => 1.375, 'maintenance' => 2021, 'adjustment' => -500, 'target' => 1520],
            'reference_weight_kg' => 68.1,
            'protein_min_g_per_kg' => 1.2,
            'macros' => [
                'carbohydrates' => ['label' => 'Carbohidratos', 'percent' => 45, 'grams' => 171, 'kcal' => 684],
                'proteins' => ['label' => 'Proteínas', 'percent' => 22, 'grams' => 84, 'kcal' => 334, 'g_per_kg' => 1.23],
                'fats' => ['label' => 'Grasas', 'percent' => 33, 'grams' => 56, 'kcal' => 502],
            ],
            'limits' => [],
            'rules' => [['rule_id' => 'MAC-01', 'title' => 'Exceso de peso', 'evidence' => ['IMC 29,4 kg/m²'], 'advice' => 'Déficit de 500 kcal/día.']],
            'notes' => [],
        ];
    }

    public function test_returns_the_plan_and_the_form_fields(): void
    {
        ClinicalRecord::create(['patient_id' => $this->patient->id, 'consultation_reason' => 'Control', 'has_prediabetes' => true]);
        $this->patient->measurements()->create(['measured_at' => now()->subWeek(), 'weight_kg' => 84, 'height_cm' => 165, 'waist_cm' => 98]);
        $this->patient->labResults()->create(['taken_at' => now()->subWeek(), 'triglycerides' => 180]);
        Http::fake(['expert:8000/evaluate' => Http::response($this->evaluation($this->calculatedPlan()))]);

        $this->actingAs($this->doctor)->postJson("/diagnosis/{$this->patient->id}/macros", $this->form())
            ->assertOk()
            ->assertJsonPath('data.rules.0.rule_id', 'MAC-01')
            ->assertJsonPath('fields', [
                'carbohydrate' => 171, 'lipido' => 56, 'protein' => 84,
                'isocaloric_carbohydrate' => 57, 'isocaloric_lipido' => 18.67, 'isocaloric_protein' => 28,
                'result_pulgar' => 1520, 'imc_desired' => 19,
            ]);

        Http::assertSent(function (Request $request) {
            $facts = json_decode($request->body(), true);

            // The form's weight prevails over the measurement; waist, labs and record come from the patient.
            return $facts['age'] === 35
                && $facts['physical_activity'] === 1
                && $facts['anthropometry'] == ['weight_kg' => 80, 'height_cm' => 165, 'waist_cm' => 98]
                && $facts['labs'] == ['triglycerides' => 180]
                && $facts['conditions']['prediabetes'] === true;
        });
    }

    public function test_without_a_plan_there_are_no_fields(): void
    {
        Http::fake(['*' => Http::response($this->evaluation([
            'status' => 'no_aplica', 'reason' => 'Menor de 18 años: usar requerimientos y curvas pediátricas.', 'rules' => [], 'notes' => [],
        ]))]);

        $this->actingAs($this->doctor)->postJson("/diagnosis/{$this->patient->id}/macros", $this->form(['age' => 15]))
            ->assertOk()
            ->assertJsonPath('data.status', 'no_aplica')
            ->assertJsonPath('fields', null);
    }

    public function test_service_not_configured_or_down(): void
    {
        config(['services.expert.url' => null]);
        Http::fake();

        $this->actingAs($this->doctor)->postJson("/diagnosis/{$this->patient->id}/macros", $this->form())
            ->assertOk()
            ->assertExactJson(['data' => null, 'fields' => null]);
        Http::assertNothingSent();

        config(['services.expert.url' => 'http://expert:8000']);
        Http::fake(['*' => Http::response('', 500)]);

        $this->actingAs($this->doctor)->postJson("/diagnosis/{$this->patient->id}/macros", $this->form())
            ->assertOk()
            ->assertExactJson(['data' => null, 'fields' => null]);
    }

    public function test_validation_and_authorization(): void
    {
        Http::fake();

        $this->actingAs($this->doctor)->postJson("/diagnosis/{$this->patient->id}/macros", ['weight' => 0, 'physical_activity' => 9])
            ->assertJsonValidationErrors(['weight', 'size', 'age', 'physical_activity']);

        $this->actingAs(User::factory()->admin()->create())->postJson("/diagnosis/{$this->patient->id}/macros", $this->form())
            ->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_consultation_form_asks_the_expert_service(): void
    {
        $this->actingAs($this->doctor)->get("/diagnosis/{$this->patient->id}")
            ->assertOk()
            ->assertSee(json_encode(route('diagnosis.macros', $this->patient)), false)
            ->assertSee('macro-plan-summary')
            ->assertDontSee('result_pulgar = peso * imc_deseado', false);
    }
}
