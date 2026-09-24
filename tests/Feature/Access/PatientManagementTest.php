<?php

namespace Tests\Feature\Access;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = User::factory()->create();
    }

    private function validPatient(array $overrides = []): array
    {
        return array_merge([
            'first_name' => ' Juan ',
            'last_name' => 'Pérez',
            'rut' => '12.345.678-5',
            'address' => 'Av. Siempre Viva 742',
            'birthdate' => '1985-05-20',
            'gender' => 'H',
            'comment' => '',
        ], $overrides);
    }

    public function test_doctor_registers_patient(): void
    {
        $this->actingAs($this->doctor)->post('/patient', $this->validPatient())
            ->assertRedirect('/patient')
            ->assertSessionHasNoErrors();

        $patient = Patient::firstOrFail();
        $this->assertSame('Juan', $patient->first_name);
        $this->assertSame('12345678-5', $patient->rut);
        $this->assertSame($this->doctor->id, (int) $patient->created_by);
    }

    public function test_patient_validation_rules(): void
    {
        $this->actingAs($this->doctor)->post('/patient', [
            'first_name' => 'Jo',
            'last_name' => '',
            'rut' => '12345678-9',
            'address' => '',
            'birthdate' => now()->addDay()->toDateString(),
            'gender' => 'X',
        ])->assertSessionHasErrors(['first_name', 'last_name', 'rut', 'address', 'birthdate', 'gender']);

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_rut_must_be_unique_in_any_format(): void
    {
        $this->actingAs($this->doctor)->post('/patient', $this->validPatient());

        $this->actingAs($this->doctor)
            ->post('/patient', $this->validPatient(['rut' => '123456785']))
            ->assertSessionHasErrors(['rut' => 'Ya existe un paciente con este RUT.']);

        $this->assertDatabaseCount('patients', 1);
    }

    public function test_client_cannot_set_author_or_delete_files(): void
    {
        $this->actingAs($this->doctor)->post('/patient', $this->validPatient([
            'created_by' => 999,
            'oldImage' => '../../index.php',
        ]))->assertRedirect('/patient');

        $this->assertSame($this->doctor->id, (int) Patient::firstOrFail()->created_by);
        $this->assertFileExists(public_path('index.php'));
    }

    public function test_doctor_updates_patient_keeping_rut(): void
    {
        $this->actingAs($this->doctor)->post('/patient', $this->validPatient());
        $patient = Patient::firstOrFail();

        $this->actingAs($this->doctor)->put("/patient/{$patient->id}", $this->validPatient([
            'rut' => null,
            'first_name' => 'Juana',
            'gender' => 'M',
        ]))->assertSessionHasErrors('rut');

        $data = $this->validPatient(['first_name' => 'Juana', 'gender' => 'M']);
        unset($data['rut']);
        $this->actingAs($this->doctor)->put("/patient/{$patient->id}", $data)
            ->assertRedirect('/patient')
            ->assertSessionHasNoErrors();

        $patient->refresh();
        $this->assertSame('Juana', $patient->first_name);
        $this->assertSame('12345678-5', $patient->rut);
    }

    public function test_missing_patient_returns_404(): void
    {
        $this->actingAs($this->doctor)->get('/patient/999/edit')->assertNotFound();
    }
}
