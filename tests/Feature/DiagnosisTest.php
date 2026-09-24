<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosisTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.inference.url' => null]); // IMC rules, no HTTP
        $this->seed(RulesSeeder::class); // diagnoses.id_rule references rules.id

        $this->doctor = User::factory()->create();
        $this->patient = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => '11111111-1',
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'id_patient' => $this->patient->id,
            'weight' => 80, 'size' => 170, 'age' => 35, 'physical_activity' => 2, 'imc' => 27.68,
            'insulin_index' => 1, 'carbohydrate' => 250, 'isocaloric_carbohydrate' => 1000,
            'lipido' => 60, 'isocaloric_lipido' => 540, 'protein' => 90, 'isocaloric_protein' => 360,
            'imc_desired' => 25, 'result_pulgar' => 2000,
        ], $overrides);
    }

    public function test_doctor_creates_diagnosis_classified_by_the_engine(): void
    {
        $response = $this->actingAs($this->doctor)->post('/diagnosis', $this->payload([
            // Not in the form request: must be ignored.
            'id_rule' => 1, 'inference_source' => 'manual', 'created_by' => 999,
        ]));

        $diagnosis = Diagnosis::firstOrFail();
        $response->assertRedirect(route('result', $diagnosis));
        $this->assertSame(3, (int) $diagnosis->id_rule);
        $this->assertSame('rules', $diagnosis->inference_source);
        $this->assertSame($this->doctor->id, (int) $diagnosis->created_by);
    }

    public function test_diagnosis_validation(): void
    {
        $this->actingAs($this->doctor)->post('/diagnosis', $this->payload([
            'id_patient' => 999, 'weight' => 'abc', 'size' => 0, 'physical_activity' => 7, 'imc' => '',
        ]))->assertSessionHasErrors(['id_patient', 'weight', 'size', 'physical_activity', 'imc']);

        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_result_page_and_pdf_share_the_same_data(): void
    {
        $this->actingAs($this->doctor)->post('/diagnosis', $this->payload());
        $diagnosis = Diagnosis::firstOrFail();

        $this->actingAs($this->doctor)->get("/result/{$diagnosis->id}")
            ->assertOk()
            ->assertSee('El paciente posee sobrepeso');

        $this->actingAs($this->doctor)->get("/download/{$diagnosis->id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_doctor_confirms_category(): void
    {
        $this->actingAs($this->doctor)->post('/diagnosis', $this->payload());
        $diagnosis = Diagnosis::firstOrFail();

        $this->actingAs($this->doctor)->put("/result/{$diagnosis->id}/rule", ['id_rule' => 9])
            ->assertSessionHasErrors('id_rule');

        $this->actingAs($this->doctor)->put("/result/{$diagnosis->id}/rule", ['id_rule' => 4])
            ->assertRedirect(route('result', $diagnosis));

        $diagnosis->refresh();
        $this->assertSame(4, (int) $diagnosis->id_rule);
        $this->assertSame('manual', $diagnosis->inference_source);
    }

    public function test_missing_records_return_404(): void
    {
        $this->actingAs($this->doctor)->get('/result/999')->assertNotFound();
        $this->actingAs($this->doctor)->get('/diagnosis/999')->assertNotFound();
        $this->actingAs($this->doctor)->get('/diagnoses/all/999')->assertNotFound();
    }
}
