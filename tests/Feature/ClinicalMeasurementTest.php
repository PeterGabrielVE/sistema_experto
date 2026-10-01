<?php

namespace Tests\Feature;

use App\Events\ClinicalMeasurementRecorded;
use App\Models\ClinicalMeasurement;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ClinicalMeasurementTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = User::factory()->create();
        $this->patient = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => '11111111-1',
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01',
        ]);
    }

    private function url(string $suffix = ''): string
    {
        return "/patient/{$this->patient->id}/measurements{$suffix}";
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'measured_at' => now()->subDay()->toDateString(),
            'weight_kg' => '82,4',
            'height_cm' => '165',
            'waist_cm' => '96',
            'hip_cm' => '108',
            'systolic_bp' => '132',
            'diastolic_bp' => '84',
            'heart_rate' => '76',
            'capillary_glucose' => '112',
        ], $overrides);
    }

    private function measurement(array $attributes = []): ClinicalMeasurement
    {
        return $this->patient->measurements()->create(array_merge([
            'measured_at' => now()->subMonth(),
            'weight_kg' => 85,
            'height_cm' => 165,
            'created_by' => $this->doctor->id,
        ], $attributes));
    }

    public function test_doctor_registers_a_measurement(): void
    {
        $this->actingAs($this->doctor)->get($this->url('/create'))
            ->assertOk()
            ->assertSee('Registrar medición');

        $this->actingAs($this->doctor)->post($this->url(), $this->payload())
            ->assertRedirect($this->url())
            ->assertSessionHas('status', 'Medición registrada correctamente.');

        $m = $this->patient->measurements()->firstOrFail();
        $this->assertSame(82.4, $m->weight_kg); // decimal comma accepted
        $this->assertSame(132, $m->systolic_bp);
        $this->assertSame($this->doctor->id, $m->created_by);
    }

    public function test_form_suggests_the_last_height(): void
    {
        $this->measurement(['height_cm' => 171.5]);

        $this->actingAs($this->doctor)->get($this->url('/create'))
            ->assertOk()
            ->assertSee('value="171.5"', false);
    }

    public function test_history_shows_indicators_and_variation(): void
    {
        $this->measurement(['measured_at' => now()->subMonth(), 'weight_kg' => 85, 'waist_cm' => 100]);
        $this->actingAs($this->doctor)->post($this->url(), $this->payload());

        // 82.4 / 1.65² = 30.3 -> Obesidad; 96 / 165 = 0.58; 132/84 -> stage 1.
        $this->actingAs($this->doctor)->get($this->url())
            ->assertOk()
            ->assertSee('Registro de mediciones clínicas')
            ->assertSee('30,3')
            ->assertSee('Obesidad')
            ->assertSee('-2,6 kg')
            ->assertSee('-4,0 cm')
            ->assertSee('0,58')
            ->assertSee('Riesgo cardiometabólico')
            ->assertSee('132/84')
            ->assertSee('Hipertensión etapa 1')
            ->assertSee('chart-measurements');
    }

    public function test_empty_history(): void
    {
        $this->actingAs($this->doctor)->get($this->url())
            ->assertOk()
            ->assertSee('Aún no hay mediciones registradas')
            ->assertDontSee('chart-measurements');
    }

    public function test_doctor_edits_a_measurement(): void
    {
        $m = $this->measurement();
        $colleague = User::factory()->create();

        $this->actingAs($colleague)->get($this->url("/{$m->id}/edit"))
            ->assertOk()
            ->assertSee('Editar medición');

        $this->actingAs($colleague)->put($this->url("/{$m->id}"), [
            'measured_at' => $m->measured_at->toDateString(),
            'weight_kg' => '84',
            'height_cm' => '165',
        ])->assertRedirect($this->url())
            ->assertSessionHas('status', 'Medición actualizada correctamente.');

        $m->refresh();
        $this->assertSame(84.0, $m->weight_kg);
        $this->assertSame($this->doctor->id, $m->created_by);
        $this->assertSame($colleague->id, $m->updated_by);
    }

    public function test_validation(): void
    {
        $this->actingAs($this->doctor)->post($this->url(), ['measured_at' => now()->toDateString()])
            ->assertSessionHasErrors(['measurements' => 'Registre al menos una medición.']);

        $this->actingAs($this->doctor)->post($this->url(), [
            'measured_at' => now()->addDay()->toDateString(),
            'weight_kg' => '900',
            'systolic_bp' => '120',
            'heart_rate' => 'abc',
        ])->assertSessionHasErrors([
            'measured_at' => 'La fecha de medición no puede ser futura.',
            'weight_kg', 'heart_rate', 'diastolic_bp',
        ]);

        $this->actingAs($this->doctor)->post($this->url(), $this->payload(['systolic_bp' => '80', 'diastolic_bp' => '90']))
            ->assertSessionHasErrors(['diastolic_bp' => 'La presión diastólica debe ser menor que la sistólica.']);

        $this->actingAs($this->doctor)->post($this->url(), $this->payload(['measured_at' => '1980-01-01']))
            ->assertSessionHasErrors(['measured_at' => 'La fecha de medición no puede ser anterior al nacimiento del paciente.']);

        $this->assertDatabaseCount('clinical_measurements', 0);
    }

    public function test_only_author_or_chief_doctor_can_delete(): void
    {
        $m = $this->measurement();
        $other = User::factory()->create();

        $this->actingAs($other)->delete($this->url("/{$m->id}"))->assertForbidden();
        $this->actingAs($other)->get($this->url())
            ->assertSee('Editar medición del')
            ->assertDontSee('Eliminar medición del');

        $this->actingAs($this->doctor)->delete($this->url("/{$m->id}"))
            ->assertRedirect($this->url())
            ->assertSessionHas('status', 'Medición eliminada.');
        $this->assertDatabaseCount('clinical_measurements', 0);

        $m = $this->measurement();
        $this->actingAs(User::factory()->chiefDoctor()->create())->delete($this->url("/{$m->id}"))->assertRedirect();
        $this->assertDatabaseCount('clinical_measurements', 0);
    }

    public function test_administrator_cannot_read_or_write_measurements(): void
    {
        $m = $this->measurement();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get($this->url())->assertForbidden();
        $this->actingAs($admin)->get($this->url('/create'))->assertForbidden();
        $this->actingAs($admin)->post($this->url(), $this->payload())->assertForbidden();
        $this->actingAs($admin)->get($this->url("/{$m->id}/edit"))->assertForbidden();
        $this->actingAs($admin)->put($this->url("/{$m->id}"), $this->payload())->assertForbidden();
        $this->actingAs($admin)->delete($this->url("/{$m->id}"))->assertForbidden();
        $this->actingAs($admin)->get('/patient')->assertDontSee(route('patient.measurements.index', $this->patient));
        $this->actingAs($this->doctor)->get('/patient')->assertSee(route('patient.measurements.index', $this->patient));

        $this->assertDatabaseCount('clinical_measurements', 1);
    }

    public function test_measurement_of_another_patient_is_not_found(): void
    {
        $other = Patient::create([
            'first_name' => 'Luis', 'last_name' => 'Soto', 'rut' => '22222222-2',
            'address' => 'Calle 2', 'gender' => 'H', 'birthdate' => '1985-05-05',
        ]);
        $m = $other->measurements()->create(['measured_at' => now(), 'weight_kg' => 70]);

        $this->actingAs($this->doctor)->get($this->url("/{$m->id}/edit"))->assertNotFound();
        $this->actingAs($this->doctor)->put($this->url("/{$m->id}"), $this->payload())->assertNotFound();
    }

    public function test_changes_are_audited_without_values(): void
    {
        Event::fake([ClinicalMeasurementRecorded::class]);

        $this->actingAs($this->doctor)->post($this->url(), $this->payload());
        Event::assertDispatched(ClinicalMeasurementRecorded::class, fn ($e) => $e->action === 'created'
            && in_array('weight_kg', $e->changedFields) && ! in_array('body_fat_pct', $e->changedFields));

        $m = ClinicalMeasurement::firstOrFail();
        $this->actingAs($this->doctor)->put($this->url("/{$m->id}"), $this->payload(['weight_kg' => '81']));
        Event::assertDispatched(ClinicalMeasurementRecorded::class, fn ($e) => $e->action === 'updated' && $e->changedFields === ['weight_kg']);

        // Saving without changes does not create an audit entry.
        $this->actingAs($this->doctor)->put($this->url("/{$m->id}"), $this->payload(['weight_kg' => '81']));
        Event::assertDispatchedTimes(ClinicalMeasurementRecorded::class, 2);

        $this->actingAs($this->doctor)->delete($this->url("/{$m->id}"));
        Event::assertDispatched(ClinicalMeasurementRecorded::class, fn ($e) => $e->action === 'deleted');
    }

    public function test_measurements_are_deleted_with_the_patient(): void
    {
        $this->measurement();

        $this->actingAs(User::factory()->chiefDoctor()->create())->delete("/patient/{$this->patient->id}");

        $this->assertDatabaseCount('clinical_measurements', 0);
    }

    public function test_new_consultation_is_prefilled_with_last_weight_and_height(): void
    {
        $this->measurement(['measured_at' => now()->subMonths(2), 'weight_kg' => 90, 'height_cm' => 168]);
        $this->measurement(['measured_at' => now()->subWeek(), 'weight_kg' => 86.5, 'height_cm' => null]);

        // Weight from the latest control, height from the last one that has it.
        $this->actingAs($this->doctor)->get("/diagnosis/{$this->patient->id}")
            ->assertOk()
            ->assertSee('value="86.5"', false)
            ->assertSee('value="168"', false)
            ->assertSee('Medición del '.now()->subWeek()->format('d/m/Y'))
            ->assertSee('Peso y talla tomados del registro de mediciones');
    }

    public function test_new_consultation_without_measurements(): void
    {
        $this->actingAs($this->doctor)->get("/diagnosis/{$this->patient->id}")
            ->assertOk()
            ->assertSee('El paciente no tiene mediciones registradas.')
            ->assertDontSee('Medición del');
    }

    public function test_indicators(): void
    {
        $m = new ClinicalMeasurement(['weight_kg' => 60, 'height_cm' => 170, 'waist_cm' => 80, 'hip_cm' => 100]);
        $this->assertSame(20.8, $m->bmi());
        $this->assertSame('Normal', $m->bmiCategory());
        $this->assertSame(0.47, $m->waistToHeight());
        $this->assertSame(0.8, $m->waistToHip());
        $this->assertNull($m->bloodPressureCategory());

        $this->assertNull((new ClinicalMeasurement(['weight_kg' => 60]))->bmi());
        $this->assertSame('Elevada', (new ClinicalMeasurement(['systolic_bp' => 125, 'diastolic_bp' => 75]))->bloodPressureCategory());
        $this->assertSame('Hipertensión etapa 2', (new ClinicalMeasurement(['systolic_bp' => 128, 'diastolic_bp' => 92]))->bloodPressureCategory());
    }
}
