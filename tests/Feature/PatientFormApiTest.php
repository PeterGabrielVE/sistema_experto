<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Vue patient form (resources/js/components/patients/PatientForm.vue) talks JSON.
 */
class PatientFormApiTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = User::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Carla', 'last_name' => 'Soto', 'rut' => '11.111.117-0',
            'email' => 'carla@correo.cl', 'address' => 'Los Aromos 55',
            'birthdate' => '1982-03-14', 'gender' => 'M', 'comment' => '',
        ], $overrides);
    }

    public function test_create_page_mounts_the_vue_form(): void
    {
        $this->actingAs($this->doctor)->get('/patient/create')
            ->assertOk()
            ->assertSee('id="patient-form"', false)
            ->assertSee('&quot;mode&quot;:&quot;create&quot;', false)
            ->assertSee('&quot;H&quot;:&quot;Hombre&quot;', false);
    }

    public function test_edit_page_passes_the_patient_resource(): void
    {
        $patient = Patient::factory()->create(['first_name' => 'Ana', 'birthdate' => '1990-01-01']);

        $this->actingAs($this->doctor)->get("/patient/{$patient->id}/edit")
            ->assertOk()
            ->assertSee('&quot;mode&quot;:&quot;edit&quot;', false)
            ->assertSee('&quot;first_name&quot;:&quot;Ana&quot;', false)
            ->assertSee('&quot;birthdate&quot;:&quot;1990-01-01&quot;', false);
    }

    public function test_store_returns_the_resource_and_next_step(): void
    {
        $response = $this->actingAs($this->doctor)->postJson('/patient', $this->payload())->assertCreated();

        $patient = Patient::firstOrFail();
        $response->assertJsonPath('data.id', $patient->id)
            ->assertJsonPath('data.rut', '11111117-0')
            ->assertJsonPath('data.full_name', 'Carla Soto')
            ->assertJsonPath('data.gender_label', 'Mujer')
            ->assertJsonPath('meta.redirect', route('patient.clinical-record.edit', $patient));

        // Flash message for the page the form navigates to.
        $this->assertSame('Paciente creado correctamente. Complete ahora su ficha clínica.', session('status'));
    }

    public function test_validation_errors_are_returned_as_json_per_field(): void
    {
        $this->actingAs($this->doctor)->postJson('/patient', $this->payload([
            'rut' => '11.111.117-9', 'email' => 'x', 'first_name' => '',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'rut' => 'El RUT no es válido.',
                'email' => 'El campo correo debe ser un correo electrónico válido.',
                'first_name' => 'El campo nombre es obligatorio.',
            ]);
    }

    public function test_update_via_json_keeps_the_rut(): void
    {
        $patient = Patient::factory()->create(['rut' => '11111117-0']);
        $data = $this->payload(['first_name' => 'Carolina']);
        unset($data['rut']);

        $this->actingAs($this->doctor)->putJson("/patient/{$patient->id}", $data)
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Carolina')
            ->assertJsonPath('data.rut', '11111117-0')
            ->assertJsonPath('meta.redirect', route('patient.index'));
    }

    public function test_list_paginates_and_searches_on_the_server(): void
    {
        Patient::factory()->count(20)->create();
        Patient::factory()->create(['first_name' => 'Carla', 'last_name' => 'Soto', 'rut' => '11111117-0', 'email' => 'cs@correo.cl']);

        $this->actingAs($this->doctor)->get('/patient')->assertOk()
            ->assertSee('21 pacientes')
            ->assertSee('?page=2', false); // patients beyond the first 15 are reachable

        foreach (['carla soto', 'Soto', '11.111.117-0', '111111170', 'cs@correo'] as $q) {
            $this->actingAs($this->doctor)->get('/patient?q='.urlencode($q))
                ->assertOk()->assertSee('1 paciente')->assertSee('Carla Soto');
        }

        $this->actingAs($this->doctor)->get('/patient?q=zzz')
            ->assertSee('No hay pacientes que coincidan con');
    }

    public function test_json_requests_are_authorized(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/patient', $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('patients', 0);
    }
}
