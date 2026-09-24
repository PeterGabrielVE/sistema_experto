<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use App\Rules\Rut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PatientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_patients_table_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('patients', [
            'first_name', 'last_name', 'rut', 'email', 'address', 'birthdate', 'gender', 'created_by',
        ]));
        $this->assertFalse(Schema::hasColumn('patients', 'age'), 'Age is derived, not stored.');
    }

    public function test_age_is_derived_from_birthdate(): void
    {
        Carbon::setTestNow('2026-09-24');

        $this->assertSame(36, Patient::factory()->born('1990-01-01')->make()->age);
        $this->assertSame(35, Patient::factory()->born('1990-09-25')->make()->age); // birthday tomorrow
        $this->assertNull(Patient::factory()->make(['birthdate' => null])->age);

        Carbon::setTestNow();
    }

    public function test_name_and_gender_helpers(): void
    {
        $patient = Patient::factory()->make(['first_name' => 'Ana', 'last_name' => 'Rojas', 'gender' => 'M']);

        $this->assertSame('Ana Rojas', $patient->fullName());
        $this->assertSame('Mujer', $patient->genderLabel());
    }

    public function test_factory_creates_valid_patients(): void
    {
        $patients = Patient::factory()->count(5)->create();

        foreach ($patients as $patient) {
            $this->assertTrue(Validator::make(['rut' => $patient->rut], ['rut' => [new Rut]])->passes());
            $this->assertArrayHasKey($patient->gender, Patient::GENDERS);
            $this->assertNotNull($patient->email);
        }
    }

    public function test_patient_email_is_optional_validated_and_normalized(): void
    {
        $doctor = User::factory()->create();
        $data = [
            'first_name' => 'Juan', 'last_name' => 'Pérez', 'rut' => '12.345.678-5',
            'address' => 'Calle 123', 'birthdate' => '1985-05-20', 'gender' => 'H',
        ];

        $this->actingAs($doctor)->post('/patient', $data + ['email' => 'no-es-correo'])
            ->assertSessionHasErrors(['email' => 'El campo correo debe ser un correo electrónico válido.']);

        $this->actingAs($doctor)->post('/patient', $data + ['email' => '  Juan.Perez@Correo.CL '])
            ->assertSessionHasNoErrors();
        $this->assertSame('juan.perez@correo.cl', Patient::firstOrFail()->email);

        $patient = Patient::firstOrFail();
        $this->actingAs($doctor)->put("/patient/{$patient->id}", array_diff_key($data, ['rut' => 1]) + ['email' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($patient->fresh()->email);
    }

    public function test_relations(): void
    {
        $doctor = User::factory()->create();
        $patient = Patient::factory()->create(['created_by' => $doctor->id]);

        $this->assertTrue($patient->user->is($doctor));
        $this->assertCount(0, $patient->diagnoses);
        $this->assertNull($patient->clinicalRecord);
    }
}
