<?php

namespace Tests\Feature\Api;

use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\Recommendation;
use App\Models\User;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiagnosisApiTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.inference.url' => null,
            'services.expert.url' => 'http://expert:8000',
            'services.expert.token' => 't0k',
        ]);
        $this->seed(RulesSeeder::class);
        Recommendation::create(['id_rule' => 3, 'description' => 'Reducir azúcares simples.']);

        $this->doctor = User::factory()->create(['email' => 'doc@correo.cl', 'password' => 'secret123']);
    }

    private function token(?User $user = null): string
    {
        $user ??= $this->doctor;

        return $this->postJson('/api/v1/tokens', ['email' => $user->email, 'password' => 'secret123'])
            ->assertCreated()
            ->json('token');
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'sex' => 'H',
            'age' => 40,
            'physical_activity' => 2,
            'anthropometry' => ['weight_kg' => 80, 'height_cm' => 165, 'waist_cm' => 98],
            'vitals' => ['systolic_bp' => 128, 'diastolic_bp' => 82],
            'labs' => ['fasting_glucose' => 105, 'fasting_insulin' => 18.2, 'triglycerides' => 180, 'hdl' => 38],
            'conditions' => ['hypertension' => true],
        ], $overrides);
    }

    /**
     * A trimmed response of POST /evaluate (expert/app/rules.py).
     */
    private function evaluation(): array
    {
        return [
            'indices' => ['homa_ir' => ['label' => 'HOMA-IR', 'value' => 4.72, 'unit' => '', 'reference' => '≤ 2,5', 'high' => true]],
            'assessments' => ['insulin_resistance' => ['status' => 'probable', 'positive' => 3, 'evaluated' => 3, 'evidence' => []]],
            'findings' => [['rule_id' => 'RI-01', 'severity' => 'warning', 'title' => 'Resistencia a la insulina probable', 'evidence' => [], 'recommendation' => null]],
            'ruleset_version' => '2026.10.1',
        ];
    }

    public function test_issues_a_token_stored_as_a_hash(): void
    {
        $response = $this->postJson('/api/v1/tokens', ['email' => 'doc@correo.cl', 'password' => 'secret123'])
            ->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.role', 'Doctor');

        $this->assertSame(hash('sha256', $response->json('token')), $this->doctor->fresh()->api_token);
        $this->assertArrayNotHasKey('api_token', $this->doctor->fresh()->toArray());
    }

    public function test_rejects_wrong_credentials(): void
    {
        $this->postJson('/api/v1/tokens', ['email' => 'doc@correo.cl', 'password' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertNull($this->doctor->fresh()->api_token);
    }

    public function test_requires_a_valid_token(): void
    {
        $this->postJson('/api/v1/diagnoses/evaluate', $this->payload())->assertUnauthorized();
        $this->withToken('invalid')->postJson('/api/v1/diagnoses/evaluate', $this->payload())->assertUnauthorized();
    }

    public function test_revoked_token_no_longer_works(): void
    {
        $token = $this->token();

        $this->withToken($token)->deleteJson('/api/v1/tokens')->assertNoContent();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->postJson('/api/v1/diagnoses/evaluate', $this->payload())->assertUnauthorized();
    }

    public function test_evaluate_returns_the_diagnosis(): void
    {
        Http::fake(['expert:8000/evaluate' => Http::response($this->evaluation())]);

        $this->withToken($this->token())->postJson('/api/v1/diagnoses/evaluate', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.bmi', 29.38)
            ->assertJsonPath('data.category.id', 3)
            ->assertJsonPath('data.category.label', 'Sobrepeso')
            ->assertJsonPath('data.category.source', 'rules')
            ->assertJsonPath('data.recommendations', ['Reducir azúcares simples.'])
            ->assertJsonPath('data.evaluation.assessments.insulin_resistance.status', 'probable')
            ->assertJsonPath('data.evaluation.findings.0.rule_id', 'RI-01');

        Http::assertSent(function (Request $request) {
            $facts = json_decode($request->body(), true);

            return $request->url() === 'http://expert:8000/evaluate'
                && $request->hasHeader('Authorization', 'Bearer t0k')
                && $facts['sex'] === 'H'
                && $facts['age'] === 40
                && $facts['anthropometry'] == ['weight_kg' => 80, 'height_cm' => 165, 'waist_cm' => 98]
                && $facts['vitals'] === ['systolic_bp' => 128, 'diastolic_bp' => 82]
                && $facts['labs'] == ['fasting_glucose' => 105, 'fasting_insulin' => 18.2, 'triglycerides' => 180, 'hdl' => 38]
                && $facts['conditions'] === ['hypertension' => true]
                && ! array_key_exists('physical_activity', $facts);
        });
        $this->assertSame(0, Diagnosis::count());
    }

    public function test_evaluate_with_only_the_required_parameters(): void
    {
        Http::fake(['*' => Http::response($this->evaluation())]);

        $this->withToken($this->token())->postJson('/api/v1/diagnoses/evaluate', [
            'sex' => 'M', 'age' => 30, 'physical_activity' => 1,
            'anthropometry' => ['weight_kg' => 55, 'height_cm' => 160],
        ])->assertOk()->assertJsonPath('data.category.id', 2);

        Http::assertSent(fn (Request $request) => str_contains($request->body(), '"vitals":{}')
            && str_contains($request->body(), '"labs":{}')
            && str_contains($request->body(), '"conditions":{}'));
    }

    public function test_evaluate_without_the_expert_service_still_classifies(): void
    {
        Http::fake(fn () => throw new ConnectionException('down'));

        $this->withToken($this->token())->postJson('/api/v1/diagnoses/evaluate', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.category.id', 3)
            ->assertJsonPath('data.evaluation', null);
    }

    public function test_evaluate_validates_the_parameters(): void
    {
        Http::fake();
        $token = $this->token();

        $this->withToken($token)->postJson('/api/v1/diagnoses/evaluate', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sex', 'age', 'physical_activity', 'anthropometry.weight_kg', 'anthropometry.height_cm']);

        $this->withToken($token)->postJson('/api/v1/diagnoses/evaluate', $this->payload([
            'sex' => 'X',
            'labs' => ['fasting_glucose' => 2000, 'cortisol' => 10],
            'vitals' => ['systolic_bp' => 80, 'diastolic_bp' => 90],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sex', 'labs', 'labs.fasting_glucose', 'vitals.diastolic_bp']);

        $this->withToken($token)->postJson('/api/v1/diagnoses/evaluate', $this->payload(['vitals' => ['systolic_bp' => 120, 'diastolic_bp' => null]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vitals.diastolic_bp');

        Http::assertNothingSent();
    }

    public function test_administrator_cannot_evaluate(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret123']);

        $this->withToken($this->token($admin))->postJson('/api/v1/diagnoses/evaluate', $this->payload())->assertForbidden();
    }

    private function storedDiagnosis(): Diagnosis
    {
        $patient = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => '11111111-1',
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01',
        ]);

        return Diagnosis::create([
            'id_patient' => $patient->id, 'age' => 36, 'weight' => 80, 'size' => 165, 'imc' => 29.38,
            'physical_activity' => 2, 'id_rule' => 3, 'inference_source' => 'ml', 'inference_confidence' => 0.91,
            'model_version' => 'v1', 'created_by' => $this->doctor->id,
        ]);
    }

    public function test_show_returns_a_stored_diagnosis(): void
    {
        $diagnosis = $this->storedDiagnosis();
        Http::fake(['*' => Http::response($this->evaluation())]);
        $token = $this->token();

        $this->withToken($token)->getJson("/api/v1/diagnoses/{$diagnosis->id}")
            ->assertOk()
            ->assertJsonPath('data.patient_id', $diagnosis->id_patient)
            ->assertJsonPath('data.bmi', 29.38)
            ->assertJsonPath('data.category.label', 'Sobrepeso')
            ->assertJsonPath('data.category.source', 'ml')
            ->assertJsonPath('data.recommendations', ['Reducir azúcares simples.'])
            ->assertJsonPath('data.evaluation.ruleset_version', '2026.10.1');

        $this->withToken($token)->getJson('/api/v1/diagnoses/999')->assertNotFound();
    }

    public function test_administrator_gets_the_diagnosis_without_the_clinical_evaluation(): void
    {
        $diagnosis = $this->storedDiagnosis();
        Http::fake();
        $admin = User::factory()->admin()->create(['password' => 'secret123']);

        $this->withToken($this->token($admin))->getJson("/api/v1/diagnoses/{$diagnosis->id}")
            ->assertOk()
            ->assertJsonPath('data.category.id', 3)
            ->assertJsonPath('data.evaluation', null);
        Http::assertNothingSent();
    }
}
