<?php

namespace Tests\Feature;

use App\Models\ClinicalRecord;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use App\Services\ExpertDiagnosisService;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class ExpertDiagnosisTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Patient $patient;

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
        $this->patient = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => '11111111-1',
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01',
        ]);
        $this->diagnosis = Diagnosis::create([
            'id_patient' => $this->patient->id, 'age' => 36, 'weight' => 80, 'size' => 165, 'imc' => 29.38,
            'physical_activity' => 2, 'id_rule' => 3, 'created_by' => $this->doctor->id,
        ]);
        $this->diagnosis->forceFill(['created_at' => now()->subMonth()])->save();
    }

    /**
     * A trimmed response of POST /evaluate (expert/app/rules.py).
     */
    private function evaluation(): array
    {
        return [
            'indices' => [
                'homa_ir' => ['label' => 'HOMA-IR', 'value' => 4.72, 'unit' => '', 'reference' => '≤ 2,5', 'high' => true],
                'bmi' => ['label' => 'IMC', 'value' => 29.4, 'unit' => 'kg/m²', 'reference' => '18,5 a 24,9', 'high' => true, 'category' => 'Sobrepeso'],
            ],
            'assessments' => [
                'glycemic_status' => ['status' => 'prediabetes', 'evidence' => ['Glicemia en ayunas 105 mg/dL']],
                'insulin_resistance' => ['status' => 'probable', 'positive' => 3, 'evaluated' => 3, 'evidence' => []],
                'metabolic_syndrome' => ['status' => 'presente', 'met' => 4, 'unknown' => 0, 'criteria' => []],
                'atherogenic_profile' => ['status' => 'limitrofe', 'positive' => 0, 'evaluated' => 1, 'evidence' => []],
            ],
            'findings' => [
                ['rule_id' => 'SM-01', 'severity' => 'alert', 'title' => 'Síndrome metabólico (4 de 5 criterios)', 'evidence' => ['Cintura ≥ 80 cm: 98 cm'], 'recommendation' => 'Abordar todos los factores de riesgo.'],
                ['rule_id' => 'RI-01', 'severity' => 'warning', 'title' => 'Resistencia a la insulina probable', 'evidence' => ['HOMA-IR 4,72 (sobre el umbral 2,5)'], 'recommendation' => null],
            ],
            'ruleset_version' => '2026.10.1',
        ];
    }

    /**
     * The JSON body as the service decodes it (comparisons are loose: floats are sent as 81.0).
     */
    private function sentFacts(Request $request): array
    {
        return json_decode($request->body(), true);
    }

    private function visitResult()
    {
        return $this->actingAs($this->doctor)->get("/result/{$this->diagnosis->id}");
    }

    #[Group('US-4.1/AC-1')] // the request carries the service's credential
    #[Group('US-2.2/AC-1')]
    public function test_result_page_shows_the_expert_evaluation(): void
    {
        ClinicalRecord::create(['patient_id' => $this->patient->id, 'consultation_reason' => 'Control', 'has_hypertension' => true]);
        $this->patient->measurements()->create([
            'measured_at' => now()->subMonth(), 'diagnosis_id' => $this->diagnosis->id,
            'weight_kg' => 81, 'height_cm' => 165, 'waist_cm' => 98, 'systolic_bp' => 128, 'diastolic_bp' => 82,
        ]);
        $this->patient->labResults()->create([
            'taken_at' => now()->subMonth(), 'diagnosis_id' => $this->diagnosis->id,
            'fasting_glucose' => 105, 'fasting_insulin' => 18.2, 'triglycerides' => 180, 'hdl' => 38,
        ]);
        Http::fake(['expert:8000/evaluate' => Http::response($this->evaluation())]);

        $this->visitResult()
            ->assertOk()
            ->assertSeeInOrder(['Evaluación del sistema experto', 'Prediabetes', 'Probable', '3 de 3 indicadores alterados', 'Presente', '4 de 5 criterios', 'Perfil aterogénico', 'Limítrofe', '0 de 1 índices alterados'])
            ->assertSeeInOrder(['Alerta', 'Síndrome metabólico (4 de 5 criterios)', 'Atención', 'Resistencia a la insulina probable'])
            ->assertSee('4,72')
            ->assertSee('medición del '.now()->subMonth()->format('d/m/Y'));

        Http::assertSent(function (Request $request) {
            $facts = $this->sentFacts($request);

            return $request->url() === 'http://expert:8000/evaluate'
                && $request->hasHeader('Authorization', 'Bearer t0k')
                && $facts['sex'] === 'M'
                && $facts['age'] === 36
                && $facts['anthropometry'] == ['weight_kg' => 81, 'height_cm' => 165, 'waist_cm' => 98]
                && $facts['vitals'] == ['systolic_bp' => 128, 'diastolic_bp' => 82]
                && $facts['labs'] == ['fasting_glucose' => 105, 'fasting_insulin' => 18.2, 'hdl' => 38, 'triglycerides' => 180]
                && $facts['conditions']['hypertension'] === true
                && $facts['conditions']['diabetes'] === false;
        });
    }

    public function test_sends_the_findrisc_answers_and_ggt(): void
    {
        ClinicalRecord::create([
            'patient_id' => $this->patient->id, 'consultation_reason' => 'Control',
            'daily_physical_activity' => false, 'daily_fruit_vegetables' => true,
            'family_history_diabetes' => 'first_degree', // antihypertensive_medication and high_glucose_history not asked
        ]);
        $this->patient->labResults()->create(['taken_at' => now()->subMonth(), 'triglycerides' => 180, 'ggt' => 62]);
        Http::fake(['*' => Http::response($this->evaluation())]);

        $this->visitResult()->assertOk();

        Http::assertSent(fn (Request $request) => $this->sentFacts($request)['risk_factors'] === [
            'daily_physical_activity' => false,
            'daily_fruit_vegetables' => true,
            'family_history_diabetes' => 'first_degree',
        ] && $this->sentFacts($request)['labs'] == ['triglycerides' => 180, 'ggt' => 62]);
    }

    public function test_result_of_an_older_ruleset_without_the_atherogenic_profile(): void
    {
        $evaluation = $this->evaluation();
        unset($evaluation['assessments']['atherogenic_profile']);
        Http::fake(['*' => Http::response($evaluation)]);

        $this->visitResult()->assertOk()->assertSee('Síndrome metabólico')->assertDontSee('Perfil aterogénico');
    }

    #[Group('US-2.2/AC-7')]
    public function test_result_page_shows_the_macronutrient_distribution(): void
    {
        $evaluation = $this->evaluation();
        $evaluation['macronutrients'] = [
            'status' => 'calculado',
            'energy' => ['bmr' => 1636, 'activity_level' => 'Moderada', 'activity_factor' => 1.55, 'maintenance' => 2536, 'adjustment' => -500, 'target' => 2040],
            'reference_weight_kg' => 68.1,
            'protein_min_g_per_kg' => 1.2,
            'macros' => [
                'carbohydrates' => ['label' => 'Carbohidratos', 'percent' => 45, 'grams' => 230, 'kcal' => 918],
                'proteins' => ['label' => 'Proteínas', 'percent' => 20, 'grams' => 102, 'kcal' => 408, 'g_per_kg' => 1.5],
                'fats' => ['label' => 'Grasas', 'percent' => 35, 'grams' => 79, 'kcal' => 714],
            ],
            'limits' => [
                'saturated_fat' => ['label' => 'Grasas saturadas', 'comparator' => 'max', 'amount' => 16, 'unit' => 'g', 'percent' => 7],
                'sodium' => ['label' => 'Sodio', 'comparator' => 'max', 'amount' => 1500, 'unit' => 'mg'],
            ],
            'rules' => [['rule_id' => 'MAC-04', 'title' => 'Alteración glicémica sin diabetes', 'evidence' => ['Prediabetes'], 'advice' => 'Reducir carbohidratos refinados.']],
            'notes' => [],
        ];
        Http::fake(['*' => Http::response($evaluation)]);

        $this->visitResult()->assertOk()
            ->assertSeeInOrder(['Distribución de macronutrientes sugerida', '2.040 kcal/día', 'déficit 500 kcal'])
            ->assertSeeInOrder(['Carbohidratos', '45 %', '230 g', 'Proteínas', '1,5 g/kg', 'Grasas', '35 %'])
            ->assertSeeInOrder(['Grasas saturadas', '16 g', 'Sodio', '1500 mg'])
            ->assertSeeInOrder(['MAC-04', 'Alteración glicémica sin diabetes', 'Reducir carbohidratos refinados.']);

        Http::assertSent(fn (Request $request) => $this->sentFacts($request)['physical_activity'] === 2);
    }

    public function test_without_linked_data_uses_the_latest_up_to_the_consultation_date(): void
    {
        $this->patient->labResults()->create(['taken_at' => now()->subMonths(2), 'hba1c' => 5.9]);
        $this->patient->labResults()->create(['taken_at' => now()->subWeek(), 'hba1c' => 6.8]); // after the consultation
        Http::fake(['*' => Http::response($this->evaluation())]);

        $this->visitResult()->assertOk()
            ->assertSee('examen del '.now()->subMonths(2)->format('d/m/Y'))
            ->assertSee('peso y talla de la consulta');

        Http::assertSent(fn (Request $request) => $this->sentFacts($request)['labs'] == ['hba1c' => 5.9]
            // No measurement: weight and height of the consultation.
            && $this->sentFacts($request)['anthropometry'] == ['weight_kg' => 80, 'height_cm' => 165]);
    }

    public function test_empty_groups_are_sent_as_objects(): void
    {
        $this->diagnosis->forceFill(['weight' => 0, 'size' => 0])->save();
        Http::fake(['*' => Http::response($this->evaluation())]);

        $this->visitResult()->assertOk();

        Http::assertSent(fn (Request $request) => str_contains($request->body(), '"anthropometry":{}')
            && str_contains($request->body(), '"labs":{}'));
    }

    #[Group('US-4.1/AC-4')]
    public function test_page_works_when_the_service_is_down_or_invalid(): void
    {
        Http::fake(fn () => throw new ConnectionException('down'));
        $this->visitResult()->assertOk()->assertSee('El paciente posee sobrepeso')->assertDontSee('Evaluación del sistema experto');

        Http::fake(['*' => Http::response(['detail' => [['msg' => 'invalid']]], 422)]);
        $this->visitResult()->assertOk()->assertDontSee('Evaluación del sistema experto');
    }

    #[Group('US-4.1/AC-6')]
    public function test_not_called_when_not_configured(): void
    {
        config(['services.expert.url' => null]);
        Http::fake();

        $this->visitResult()->assertOk()->assertDontSee('Evaluación del sistema experto');
        Http::assertNothingSent();
    }

    #[Group('US-4.1/AC-4')]
    public function test_page_works_when_the_token_is_rejected_or_missing(): void
    {
        // Wrong credential (401) and service deployed without one (503): same as a service down.
        foreach ([401 => 'Invalid token', 503 => 'Credencial del servicio no configurada'] as $status => $detail) {
            Http::fake(['*' => Http::response(['detail' => $detail], $status)]);

            $this->visitResult()
                ->assertOk()
                ->assertSee('El paciente posee sobrepeso') // the IMC rules
                ->assertDontSee('Evaluación del sistema experto');
        }

        $this->assertModelExists($this->diagnosis);
    }

    #[Group('US-4.1/AC-5')]
    public function test_invalid_data_is_logged_without_clinical_values(): void
    {
        // What FastAPI answers to an out-of-range value: the value itself comes back in "input".
        $detail = [[
            'type' => 'less_than_equal', 'loc' => ['body', 'labs', 'fasting_glucose'],
            'msg' => 'Input should be less than or equal to 1000', 'input' => 2071.5, 'ctx' => ['le' => 1000],
        ]];
        Http::fake(['*' => Http::response(['detail' => $detail], 422)]);
        Log::spy();

        app(ExpertDiagnosisService::class)->evaluateFacts(['sex' => 'M', 'labs' => ['fasting_glucose' => 2071.5]], ['diagnosis' => $this->diagnosis->id]);
        app(ExpertDiagnosisService::class)->requestMealPlan(['targets' => ['energy' => 1777.3]], ['diagnosis' => $this->diagnosis->id]);

        foreach (['Expert service returned an invalid response', 'Expert service returned an invalid meal plan'] as $message) {
            Log::shouldHaveReceived('warning')->with($message, Mockery::on(function (array $context) {
                $logged = json_encode($context);

                return $context['status'] === 422
                    && $context['errors'] === [['loc' => 'body.labs.fasting_glucose', 'type' => 'less_than_equal']]
                    && ! str_contains($logged, '2071')
                    && ! str_contains($logged, 'input')
                    && ! str_contains($logged, 'Input should');
            }))->once();
        }
    }

    #[Group('US-2.2/AC-9')]
    public function test_administrator_does_not_get_the_clinical_evaluation(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->admin()->create())->get("/result/{$this->diagnosis->id}")
            ->assertOk()
            ->assertDontSee('Evaluación del sistema experto');
        Http::assertNothingSent();
    }
}
