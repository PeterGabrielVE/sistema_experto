<?php

namespace Tests\Feature;

use App\Enums\FamilyHistoryDiabetes;
use App\Enums\Smoking;
use App\Events\ClinicalRecordSaved;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class ClinicalRecordTest extends TestCase
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
        return "/patient/{$this->patient->id}/clinical-record{$suffix}";
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'consultation_reason' => 'Control de peso y glicemia elevada',
            'has_prediabetes' => '1',
            'has_dyslipidemia' => '1',
            'family_history' => 'Madre con diabetes tipo 2',
            'medications' => 'Metformina 850 mg',
            'smoking' => 'never',
            'sleep_hours' => '6,5',
        ], $overrides);
    }

    public function test_doctor_registers_the_clinical_record(): void
    {
        $this->actingAs($this->doctor)->get($this->url('/edit'))
            ->assertOk()
            ->assertSee('Registrar ficha clínica');

        $this->actingAs($this->doctor)->put($this->url(), $this->payload())
            ->assertRedirect($this->url())
            ->assertSessionHas('status', 'Ficha clínica registrada correctamente.');

        $record = $this->patient->clinicalRecord()->firstOrFail();
        $this->assertTrue($record->has_prediabetes);
        $this->assertFalse($record->has_diabetes); // unchecked box
        $this->assertSame(Smoking::Never, $record->smoking);
        $this->assertSame(6.5, $record->sleep_hours); // decimal comma accepted
        $this->assertSame($this->doctor->id, $record->created_by);
    }

    public function test_doctor_edits_the_clinical_record(): void
    {
        $this->actingAs($this->doctor)->put($this->url(), $this->payload());
        $colleague = User::factory()->chiefDoctor()->create();

        $this->actingAs($colleague)->get($this->url('/edit'))
            ->assertOk()
            ->assertSee('Editar ficha clínica')
            ->assertSee('Metformina 850 mg');

        $this->actingAs($colleague)->put($this->url(), $this->payload([
            'medications' => 'Metformina 1000 mg',
            'has_dyslipidemia' => null,
        ]))->assertSessionHas('status', 'Ficha clínica actualizada correctamente.');

        $record = ClinicalRecord::firstOrFail();
        $this->assertSame('Metformina 1000 mg', $record->medications);
        $this->assertFalse($record->has_dyslipidemia);
        $this->assertSame($this->doctor->id, $record->created_by);
        $this->assertSame($colleague->id, $record->updated_by);
        $this->assertDatabaseCount('clinical_records', 1); // still one per patient
    }

    public function test_show_page_displays_the_latest_waist_and_lab_result(): void
    {
        $this->actingAs($this->doctor)->put($this->url(), $this->payload());

        $this->actingAs($this->doctor)->get($this->url())
            ->assertOk()
            ->assertSee('Prediabetes')
            ->assertSee('Madre con diabetes tipo 2')
            ->assertSee('Sin exámenes registrados.');

        $this->patient->measurements()->create(['measured_at' => now()->subMonth(), 'waist_cm' => 98]);
        $this->patient->measurements()->create(['measured_at' => now()->subDays(3), 'waist_cm' => 94.5]);
        $this->patient->measurements()->create(['measured_at' => now(), 'weight_kg' => 80]); // no waist

        $this->actingAs($this->doctor)->get($this->url())
            ->assertSee('94,5 cm')
            ->assertSee('medición del '.now()->subDays(3)->format('d/m/Y'))
            ->assertDontSee('98 cm');

        $this->patient->labResults()->create(['taken_at' => now()->subYear(), 'fasting_glucose' => 90, 'fasting_insulin' => 5]);
        $this->patient->labResults()->create(['taken_at' => now()->subWeek(), 'fasting_glucose' => 105, 'fasting_insulin' => 18.2]);

        // Latest: 105 × 18.2 / 405 = 4.72
        $this->actingAs($this->doctor)->get($this->url())
            ->assertSee('Último examen de laboratorio')
            ->assertSee(now()->subWeek()->format('d/m/Y'))
            ->assertSee('4,72')
            ->assertDontSee('1,11')
            ->assertSee('Sugiere resistencia a la insulina');
    }

    #[Group('US-5.1/AC-1')]
    #[Group('US-5.1/AC-4')]
    #[Group('US-5.1/AC-7')]
    public function test_show_page_includes_the_evolution_chart(): void
    {
        $this->actingAs($this->doctor)->put($this->url(), $this->payload());

        $this->actingAs($this->doctor)->get($this->url())
            ->assertOk()
            ->assertSee('Evolución del peso y HOMA-IR')
            // The chart is drawn from the JSON endpoint (TK-5.1.2).
            ->assertSee('data-url="'.route('patient.evolution', $this->patient).'"', false)
            ->assertSee('id="patient-evolution-chart"', false)
            // Period filter, with the whole history selected when the page opens.
            ->assertSeeInOrder(['Últimos 3 meses', 'Últimos 6 meses', 'Últimos 12 meses', 'Todo el historial'])
            ->assertSee('data-period="all" aria-pressed="true"', false)
            // Messages for a series without enough data, with the way to record it.
            ->assertSee('se necesitan al menos dos mediciones con peso')
            ->assertSee('se necesitan al menos dos exámenes con glicemia e insulina en ayunas')
            ->assertSee(route('patient.measurements.create', $this->patient))
            ->assertSee(route('patient.lab-results.create', $this->patient));
    }

    public function test_findrisc_answers(): void
    {
        $this->actingAs($this->doctor)->put($this->url(), $this->payload([
            'daily_physical_activity' => '0',
            'daily_fruit_vegetables' => '1',
            'antihypertensive_medication' => '',
            'family_history_diabetes' => 'first_degree',
        ]))->assertSessionHasNoErrors();

        $record = ClinicalRecord::firstOrFail();
        $this->assertFalse($record->daily_physical_activity);
        $this->assertTrue($record->daily_fruit_vegetables);
        $this->assertNull($record->antihypertensive_medication); // not asked
        $this->assertNull($record->high_glucose_history);
        $this->assertSame(FamilyHistoryDiabetes::FirstDegree, $record->family_history_diabetes);

        // "No" stays selected: false is not the empty option.
        $this->actingAs($this->doctor)->get($this->url('/edit'))
            ->assertOk()
            ->assertSee('Cuestionario FINDRISC')
            ->assertSee('<option value="0" selected>No</option>', false)
            ->assertSee('<option value="first_degree" selected>', false);

        $this->actingAs($this->doctor)->get($this->url())
            ->assertSeeInOrder(['Cuestionario FINDRISC', '30 minutos', 'No', 'verduras o frutas', 'Sí', 'Sí: padres, hermanos o hijos']);

        $this->actingAs($this->doctor)->put($this->url(), $this->payload([
            'daily_physical_activity' => 'quizás',
            'family_history_diabetes' => 'neighbours',
        ]))->assertSessionHasErrors(['daily_physical_activity', 'family_history_diabetes']);
    }

    public function test_show_without_record_goes_to_the_form(): void
    {
        $this->actingAs($this->doctor)->get($this->url())->assertRedirect($this->url('/edit'));
    }

    public function test_validation(): void
    {
        $this->actingAs($this->doctor)->put($this->url(), [
            'consultation_reason' => '',
            'smoking' => 'mucho',
            'water_liters' => 'abc',
            'sleep_hours' => '30',
        ])->assertSessionHasErrors([
            'consultation_reason' => 'El campo motivo de consulta es obligatorio.',
            'smoking', 'water_liters', 'sleep_hours',
        ]);

        $this->assertDatabaseCount('clinical_records', 0);
    }

    #[Group('US-5.1/AC-5')]
    public function test_administrator_cannot_read_or_write_clinical_data(): void
    {
        $this->actingAs($this->doctor)->put($this->url(), $this->payload());
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get($this->url())->assertForbidden();
        $this->actingAs($admin)->get($this->url('/edit'))->assertForbidden();
        $this->actingAs($admin)->put($this->url(), $this->payload(['medications' => 'X']))->assertForbidden();
        $this->actingAs($admin)->get('/patient')->assertOk()
            ->assertSee('Ana Rojas')
            ->assertDontSee(route('patient.clinical-record.show', $this->patient));
        $this->actingAs($this->doctor)->get('/patient')
            ->assertSee(route('patient.clinical-record.show', $this->patient));

        $this->assertSame('Metformina 850 mg', ClinicalRecord::firstOrFail()->medications);
    }

    public function test_changes_are_audited_without_values(): void
    {
        Event::fake([ClinicalRecordSaved::class]);

        $this->actingAs($this->doctor)->put($this->url(), $this->payload());
        Event::assertDispatched(ClinicalRecordSaved::class, fn ($e) => $e->created && in_array('medications', $e->changedFields));

        $this->actingAs($this->doctor)->put($this->url(), $this->payload(['sleep_hours' => '7']));
        Event::assertDispatched(ClinicalRecordSaved::class, fn ($e) => ! $e->created && $e->changedFields === ['sleep_hours']);

        // Saving without changes does not create an audit entry.
        $this->actingAs($this->doctor)->put($this->url(), $this->payload(['sleep_hours' => '7']));
        Event::assertDispatchedTimes(ClinicalRecordSaved::class, 2);
    }

    public function test_record_is_deleted_with_the_patient(): void
    {
        $this->actingAs($this->doctor)->put($this->url(), $this->payload());

        $this->actingAs(User::factory()->chiefDoctor()->create())->delete("/patient/{$this->patient->id}");

        $this->assertDatabaseCount('clinical_records', 0);
    }
}
