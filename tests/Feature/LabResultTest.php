<?php

namespace Tests\Feature;

use App\Events\LabResultRecorded;
use App\Models\Diagnosis;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LabResultTest extends TestCase
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
        return "/patient/{$this->patient->id}/lab-results{$suffix}";
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'taken_at' => now()->subWeek()->toDateString(),
            'fasting_glucose' => '105',
            'fasting_insulin' => '18,2',
            'hba1c' => '5,9',
            'total_cholesterol' => '210',
            'hdl' => '38',
            'ldl' => '135',
            'triglycerides' => '180',
        ], $overrides);
    }

    private function labResult(array $attributes = []): LabResult
    {
        return $this->patient->labResults()->create(array_merge([
            'taken_at' => now()->subMonths(3),
            'fasting_glucose' => 110,
            'fasting_insulin' => 20,
            'created_by' => $this->doctor->id,
        ], $attributes));
    }

    private function consultation(?Patient $patient = null): Diagnosis
    {
        return Diagnosis::create(['id_patient' => ($patient ?? $this->patient)->id, 'age' => 36, 'weight' => 80, 'size' => 165]);
    }

    public function test_doctor_registers_a_lab_result(): void
    {
        $this->actingAs($this->doctor)->get($this->url('/create'))
            ->assertOk()
            ->assertSee('Registrar examen');

        $this->actingAs($this->doctor)->post($this->url(), $this->payload())
            ->assertRedirect($this->url())
            ->assertSessionHas('status', 'Examen registrado correctamente.');

        $r = $this->patient->labResults()->firstOrFail();
        $this->assertSame(18.2, $r->fasting_insulin); // decimal comma accepted
        $this->assertSame(5.9, $r->hba1c);
        $this->assertNull($r->diagnosis_id);
        $this->assertSame($this->doctor->id, $r->created_by);
    }

    public function test_history_shows_indicators_and_out_of_range_values(): void
    {
        $this->labResult();
        $this->actingAs($this->doctor)->post($this->url(), $this->payload());

        // HOMA-IR 105 × 18.2 / 405 = 4.72; TyG ln(180 × 105 / 2) = 9.15; TG/HDL 180 / 38 = 4.74.
        $this->actingAs($this->doctor)->get($this->url())
            ->assertOk()
            ->assertSee('Exámenes de laboratorio')
            ->assertSee('4,72')
            ->assertSee('9,15')
            ->assertSee('4,74')
            ->assertSee('Sugiere resistencia a la insulina')
            ->assertSee('Fuera de rango')
            ->assertSee('evolution-chart');
    }

    public function test_empty_history(): void
    {
        $this->actingAs($this->doctor)->get($this->url())
            ->assertOk()
            ->assertSee('Aún no hay exámenes registrados')
            ->assertDontSee('evolution-chart');
    }

    public function test_doctor_edits_a_lab_result(): void
    {
        $r = $this->labResult();
        $colleague = User::factory()->create();

        $this->actingAs($colleague)->get($this->url("/{$r->id}/edit"))
            ->assertOk()
            ->assertSee('Editar examen');

        $this->actingAs($colleague)->put($this->url("/{$r->id}"), [
            'taken_at' => $r->taken_at->toDateString(),
            'fasting_glucose' => '98',
            'fasting_insulin' => '20',
        ])->assertRedirect($this->url())
            ->assertSessionHas('status', 'Examen actualizado correctamente.');

        $r->refresh();
        $this->assertSame(98.0, $r->fasting_glucose);
        $this->assertSame($this->doctor->id, $r->created_by);
        $this->assertSame($colleague->id, $r->updated_by);
    }

    public function test_validation(): void
    {
        $this->actingAs($this->doctor)->post($this->url(), ['taken_at' => now()->toDateString()])
            ->assertSessionHasErrors(['analytes' => 'Registre al menos un resultado de examen.']);

        $this->actingAs($this->doctor)->post($this->url(), [
            'taken_at' => now()->addDay()->toDateString(),
            'fasting_glucose' => '2000',
            'hba1c' => 'abc',
        ])->assertSessionHasErrors([
            'taken_at' => 'La fecha de toma de muestra no puede ser futura.',
            'fasting_glucose', 'hba1c',
        ]);

        $this->actingAs($this->doctor)->post($this->url(), $this->payload(['taken_at' => '1980-01-01']))
            ->assertSessionHasErrors(['taken_at' => 'La fecha de toma de muestra no puede ser anterior al nacimiento del paciente.']);

        $this->assertDatabaseCount('lab_results', 0);
    }

    public function test_links_to_a_consultation_of_the_same_patient_only(): void
    {
        $consultation = $this->consultation();
        $other = Patient::create([
            'first_name' => 'Luis', 'last_name' => 'Soto', 'rut' => '22222222-2',
            'address' => 'Calle 2', 'gender' => 'H', 'birthdate' => '1985-05-05',
        ]);

        // The form preselects the consultation it comes from.
        $this->actingAs($this->doctor)->get($this->url("/create?diagnosis={$consultation->id}"))
            ->assertOk()
            ->assertSee('<option value="'.$consultation->id.'" selected', false);

        $this->actingAs($this->doctor)->post($this->url(), $this->payload(['diagnosis_id' => $this->consultation($other)->id]))
            ->assertSessionHasErrors('diagnosis_id');

        $this->actingAs($this->doctor)->post($this->url(), $this->payload(['diagnosis_id' => $consultation->id]))
            ->assertSessionHasNoErrors();
        $this->assertSame($consultation->id, LabResult::firstOrFail()->diagnosis_id);
        $this->assertCount(1, $consultation->labResults);

        // Deleting the consultation keeps the result, unlinked.
        $consultation->delete();
        $this->assertNull(LabResult::firstOrFail()->diagnosis_id);
    }

    public function test_consultation_result_shows_linked_measurements_and_lab_results(): void
    {
        config(['services.inference.url' => null]);
        $this->seed(RulesSeeder::class);
        $consultation = $this->consultation();
        $this->labResult(['diagnosis_id' => $consultation->id, 'triglycerides' => 180, 'fasting_glucose' => 105]);
        $this->patient->measurements()->create(['diagnosis_id' => $consultation->id, 'measured_at' => now(), 'weight_kg' => 80, 'height_cm' => 165]);
        $this->labResult(['fasting_glucose' => 140]); // not linked

        $this->actingAs($this->doctor)->get("/result/{$consultation->id}")
            ->assertOk()
            ->assertSee('Exámenes de la consulta')
            ->assertSee('9,15') // TyG of the linked result
            ->assertDontSee('140 mg/dL')
            ->assertSee('29,4 (Sobrepeso)') // 80 / 1.65²
            ->assertSee(route('patient.lab-results.create', [$this->patient, 'diagnosis' => $consultation->id]), false);
    }

    public function test_only_author_or_chief_doctor_can_delete(): void
    {
        $r = $this->labResult();
        $other = User::factory()->create();

        $this->actingAs($other)->delete($this->url("/{$r->id}"))->assertForbidden();
        $this->actingAs($other)->get($this->url())
            ->assertSee('Editar examen del')
            ->assertDontSee('Eliminar examen del');

        $this->actingAs($this->doctor)->delete($this->url("/{$r->id}"))
            ->assertRedirect($this->url())
            ->assertSessionHas('status', 'Examen eliminado.');
        $this->assertDatabaseCount('lab_results', 0);

        $r = $this->labResult();
        $this->actingAs(User::factory()->chiefDoctor()->create())->delete($this->url("/{$r->id}"))->assertRedirect();
        $this->assertDatabaseCount('lab_results', 0);
    }

    public function test_administrator_cannot_read_or_write_lab_results(): void
    {
        $r = $this->labResult();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get($this->url())->assertForbidden();
        $this->actingAs($admin)->get($this->url('/create'))->assertForbidden();
        $this->actingAs($admin)->post($this->url(), $this->payload())->assertForbidden();
        $this->actingAs($admin)->get($this->url("/{$r->id}/edit"))->assertForbidden();
        $this->actingAs($admin)->put($this->url("/{$r->id}"), $this->payload())->assertForbidden();
        $this->actingAs($admin)->delete($this->url("/{$r->id}"))->assertForbidden();
        $this->actingAs($admin)->get('/patient')->assertDontSee(route('patient.lab-results.index', $this->patient));
        $this->actingAs($this->doctor)->get('/patient')->assertSee(route('patient.lab-results.index', $this->patient));

        $this->assertDatabaseCount('lab_results', 1);
    }

    public function test_lab_result_of_another_patient_is_not_found(): void
    {
        $other = Patient::create([
            'first_name' => 'Luis', 'last_name' => 'Soto', 'rut' => '22222222-2',
            'address' => 'Calle 2', 'gender' => 'H', 'birthdate' => '1985-05-05',
        ]);
        $r = $other->labResults()->create(['taken_at' => now(), 'hba1c' => 6]);

        $this->actingAs($this->doctor)->get($this->url("/{$r->id}/edit"))->assertNotFound();
        $this->actingAs($this->doctor)->put($this->url("/{$r->id}"), $this->payload())->assertNotFound();
    }

    public function test_changes_are_audited_without_values(): void
    {
        Event::fake([LabResultRecorded::class]);

        $this->actingAs($this->doctor)->post($this->url(), $this->payload(['ldl' => '']));
        Event::assertDispatched(LabResultRecorded::class, fn ($e) => $e->action === 'created'
            && in_array('fasting_insulin', $e->changedFields) && ! in_array('ldl', $e->changedFields));

        $r = LabResult::firstOrFail();
        $this->actingAs($this->doctor)->put($this->url("/{$r->id}"), $this->payload(['ldl' => '', 'hba1c' => '6,1']));
        Event::assertDispatched(LabResultRecorded::class, fn ($e) => $e->action === 'updated' && $e->changedFields === ['hba1c']);

        // Saving without changes does not create an audit entry.
        $this->actingAs($this->doctor)->put($this->url("/{$r->id}"), $this->payload(['ldl' => '', 'hba1c' => '6,1']));
        Event::assertDispatchedTimes(LabResultRecorded::class, 2);

        $this->actingAs($this->doctor)->delete($this->url("/{$r->id}"));
        Event::assertDispatched(LabResultRecorded::class, fn ($e) => $e->action === 'deleted');
    }

    public function test_lab_results_are_deleted_with_the_patient(): void
    {
        $this->labResult();

        $this->actingAs(User::factory()->chiefDoctor()->create())->delete("/patient/{$this->patient->id}");

        $this->assertDatabaseCount('lab_results', 0);
    }

    public function test_indicators(): void
    {
        $r = new LabResult(['fasting_glucose' => 90, 'fasting_insulin' => 8, 'triglycerides' => 100, 'hdl' => 50]);
        $this->assertSame(1.78, $r->homaIr());
        $this->assertSame(8.41, $r->tygIndex());
        $this->assertSame(2.0, $r->triglyceridesToHdl());
        $this->assertSame(['HOMA-IR', 'Índice TyG', 'TG/HDL'], array_keys($r->insulinResistanceIndicators()));
        $this->assertFalse($r->insulinResistanceIndicators()['Índice TyG']['high']);

        $this->assertSame([], (new LabResult(['hba1c' => 5.5]))->insulinResistanceIndicators());
        $this->assertFalse((new LabResult(['hba1c' => 5.5]))->isOutOfRange('hba1c'));
        $this->assertTrue((new LabResult(['hdl' => 35]))->isOutOfRange('hdl'));
        $this->assertNull((new LabResult)->isOutOfRange('ldl'));
    }
}
