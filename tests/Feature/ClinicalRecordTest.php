<?php

namespace Tests\Feature;

use App\Enums\Smoking;
use App\Events\ClinicalRecordSaved;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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
            'waist_cm' => '94',
            'lab_date' => now()->subWeek()->toDateString(),
            'fasting_glucose' => '105',
            'fasting_insulin' => '18,2',
            'hba1c' => '5,9',
            'triglycerides' => '180',
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
        $this->assertSame(18.2, $record->fasting_insulin);
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

    public function test_show_page_displays_homa_ir_and_interpretation(): void
    {
        $this->actingAs($this->doctor)->put($this->url(), $this->payload());

        // 105 × 18.2 / 405 = 4.72
        $this->actingAs($this->doctor)->get($this->url())
            ->assertOk()
            ->assertSee('4,72')
            ->assertSee('Sugiere resistencia a la insulina')
            ->assertSee('Prediabetes')
            ->assertSee('Madre con diabetes tipo 2');
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
            'fasting_glucose' => '2000',
            'hba1c' => 'abc',
            'sleep_hours' => '30',
            'lab_date' => '',
        ])->assertSessionHasErrors([
            'consultation_reason' => 'El campo motivo de consulta es obligatorio.',
            'smoking', 'fasting_glucose', 'hba1c', 'sleep_hours',
            'lab_date' => 'Indique la fecha de los exámenes de laboratorio.',
        ]);

        $this->actingAs($this->doctor)->put($this->url(), $this->payload(['lab_date' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors(['lab_date' => 'La fecha de exámenes no puede ser futura.']);

        $this->assertDatabaseCount('clinical_records', 0);
    }

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

        $this->actingAs($this->doctor)->put($this->url(), $this->payload(['hba1c' => '6,1']));
        Event::assertDispatched(ClinicalRecordSaved::class, fn ($e) => ! $e->created && $e->changedFields === ['hba1c']);

        // Saving without changes does not create an audit entry.
        $this->actingAs($this->doctor)->put($this->url(), $this->payload(['hba1c' => '6,1']));
        Event::assertDispatchedTimes(ClinicalRecordSaved::class, 2);
    }

    public function test_record_is_deleted_with_the_patient(): void
    {
        $this->actingAs($this->doctor)->put($this->url(), $this->payload());

        $this->actingAs(User::factory()->chiefDoctor()->create())->delete("/patient/{$this->patient->id}");

        $this->assertDatabaseCount('clinical_records', 0);
    }

    public function test_homa_ir_calculation(): void
    {
        $this->assertNull((new ClinicalRecord(['fasting_glucose' => 90]))->homaIr());
        $this->assertSame(1.78, (new ClinicalRecord(['fasting_glucose' => 90, 'fasting_insulin' => 8]))->homaIr());
        $this->assertFalse((new ClinicalRecord(['fasting_glucose' => 90, 'fasting_insulin' => 8]))->suggestsInsulinResistance());
    }
}
