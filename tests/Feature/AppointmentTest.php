<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicalRecord;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Next appointment of a patient (US-6.1): scheduled with the consultation or from the
 * clinical record. Dates are entered and shown in the clinic timezone and stored in UTC.
 */
class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        // 09:00 in Santiago (UTC-3 in October).
        Carbon::setTestNow('2026-10-21 12:00:00');
        config(['app.clinic_timezone' => 'America/Santiago']);
        config(['services.inference.url' => null]); // IMC rules, no HTTP
        $this->seed(RulesSeeder::class); // diagnoses.id_rule references rules.id

        $this->doctor = User::factory()->create(['name' => 'Dra. Carolina Muñoz']);
        $this->patient = Patient::factory()->create();
        ClinicalRecord::create(['patient_id' => $this->patient->id, 'consultation_reason' => 'Control']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function consultation(array $overrides = []): array
    {
        return array_merge([
            'id_patient' => $this->patient->id,
            'weight' => 80, 'size' => 170, 'age' => 35, 'physical_activity' => 2, 'imc' => 27.68,
            'insulin_index' => 1, 'carbohydrate' => 250, 'isocaloric_carbohydrate' => 1000,
            'lipido' => 60, 'isocaloric_lipido' => 540, 'protein' => 90, 'isocaloric_protein' => 360,
            'imc_desired' => 25, 'result_pulgar' => 2000,
        ], $overrides);
    }

    private function recordUrl(): string
    {
        return route('patient.clinical-record.show', $this->patient);
    }

    private function appointmentUrl(): string
    {
        return route('patient.appointment.update', $this->patient);
    }

    private function scheduled(array $attributes = []): Appointment
    {
        return Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id' => $this->doctor->id,
            'scheduled_at' => '2026-11-04 13:30:00',
            ...$attributes,
        ]);
    }

    #[Group('US-6.1/AC-1')]
    public function test_saving_a_consultation_schedules_the_next_appointment(): void
    {
        $this->actingAs($this->doctor)->post('/diagnosis', $this->consultation([
            'next_appointment_at' => '2026-11-04T10:30',
            'next_appointment_notes' => 'Venir en ayuno de 8 horas.',
        ]))->assertSessionHasNoErrors();

        $diagnosis = Diagnosis::firstOrFail();
        $appointment = Appointment::sole();
        $this->assertSame($this->patient->id, $appointment->patient_id);
        $this->assertSame($this->doctor->id, $appointment->user_id);
        $this->assertSame($diagnosis->id, $appointment->diagnosis_id);
        $this->assertSame('2026-11-04 13:30:00', $appointment->scheduled_at->format('Y-m-d H:i:s')); // UTC
        $this->assertSame(Appointment::SCHEDULED, $appointment->status);
        $this->assertSame('Venir en ayuno de 8 horas.', $appointment->notes);
        $this->assertNull($appointment->reminder_status);
    }

    #[Group('US-6.1/AC-1')]
    public function test_consultation_without_next_appointment_creates_none(): void
    {
        $this->actingAs($this->doctor)->post('/diagnosis', $this->consultation())->assertSessionHasNoErrors();
        // Notes alone are ignored: they belong to an appointment.
        $this->actingAs($this->doctor)->post('/diagnosis', $this->consultation([
            'next_appointment_at' => '',
            'next_appointment_notes' => 'Venir en ayuno.',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(2, Diagnosis::count());
        $this->assertSame(0, Appointment::count());
    }

    #[Group('US-6.1/AC-1')]
    public function test_clinical_record_shows_the_next_appointment_and_professional(): void
    {
        $this->actingAs($this->doctor)->get($this->recordUrl())
            ->assertOk()
            ->assertSee('Sin próxima consulta programada.');

        $this->scheduled(['notes' => 'Venir en ayuno de 8 horas.']);

        $this->actingAs($this->doctor)->get($this->recordUrl())
            ->assertOk()
            ->assertSee('Próxima consulta')
            ->assertSee('04/11/2026 10:30') // Santiago time
            ->assertSee('Dra. Carolina Muñoz')
            ->assertSee('Venir en ayuno de 8 horas.')
            ->assertDontSee('Sin próxima consulta programada.');
    }

    #[Group('US-6.1/AC-7')]
    public function test_doctor_reschedules_from_the_clinical_record_and_reminder_resets(): void
    {
        $appointment = $this->scheduled(['reminder_status' => 'sent', 'reminder_at' => now()]);
        $colleague = User::factory()->chiefDoctor()->create();

        $this->actingAs($colleague)->put($this->appointmentUrl(), [
            'scheduled_at' => '2026-11-10T09:00',
            'notes' => 'Traer exámenes.',
        ])->assertRedirect($this->recordUrl())->assertSessionHas('status', 'Próxima consulta guardada.');

        $appointment->refresh();
        $this->assertSame(1, Appointment::count());
        $this->assertSame('2026-11-10 12:00:00', $appointment->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame('Traer exámenes.', $appointment->notes);
        $this->assertSame($colleague->id, $appointment->user_id); // who schedules is in charge
        $this->assertNull($appointment->reminder_status); // to be sent for the new date
        $this->assertNull($appointment->reminder_at);
    }

    #[Group('US-6.1/AC-7')]
    public function test_changing_only_the_notes_keeps_the_reminder_state(): void
    {
        $appointment = $this->scheduled(['reminder_status' => 'queued', 'reminder_at' => now()]);

        $this->actingAs($this->doctor)->put($this->appointmentUrl(), [
            'scheduled_at' => '2026-11-04T10:30',
            'notes' => 'Traer exámenes.',
        ])->assertRedirect($this->recordUrl());

        $this->assertSame('queued', $appointment->refresh()->reminder_status);
    }

    #[Group('US-6.1/AC-7')]
    public function test_doctor_schedules_from_the_clinical_record_when_there_is_none(): void
    {
        $this->actingAs($this->doctor)->put($this->appointmentUrl(), ['scheduled_at' => '2026-11-10T09:00'])
            ->assertRedirect($this->recordUrl());

        $appointment = Appointment::sole();
        $this->assertSame(Appointment::SCHEDULED, $appointment->status);
        $this->assertNull($appointment->diagnosis_id);
        $this->assertNull($appointment->notes);
    }

    #[Group('US-6.1/AC-7')]
    public function test_scheduling_again_replaces_the_scheduled_one(): void
    {
        $past = $this->scheduled(['scheduled_at' => '2026-09-01 13:00:00']); // already happened
        $next = $this->scheduled();

        $this->actingAs($this->doctor)->post('/diagnosis', $this->consultation([
            'next_appointment_at' => '2026-12-01T11:00',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(2, Appointment::count());
        $this->assertSame('2026-12-01 14:00:00', $next->refresh()->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame(Diagnosis::firstOrFail()->id, $next->diagnosis_id);
        $this->assertSame('2026-09-01 13:00:00', $past->refresh()->scheduled_at->format('Y-m-d H:i:s'));
    }

    #[Group('US-6.1/AC-7')]
    public function test_doctor_cancels_the_next_appointment(): void
    {
        $appointment = $this->scheduled();

        $this->actingAs($this->doctor)->delete($this->appointmentUrl())
            ->assertRedirect($this->recordUrl())
            ->assertSessionHas('status', 'Próxima consulta cancelada.');

        $appointment->refresh();
        $this->assertSame(Appointment::CANCELLED, $appointment->status);
        $this->assertTrue($appointment->cancelled_at->eq(now()));

        $this->actingAs($this->doctor)->get($this->recordUrl())->assertSee('Sin próxima consulta programada.');
        // Nothing left to cancel.
        $this->actingAs($this->doctor)->delete($this->appointmentUrl())->assertStatus(422);
    }

    #[Group('US-6.1/AC-7')]
    public function test_rejects_an_appointment_in_the_past(): void
    {
        $this->actingAs($this->doctor)->put($this->appointmentUrl(), ['scheduled_at' => '2026-10-21T08:00'])
            ->assertSessionHasErrors('scheduled_at');
        $this->actingAs($this->doctor)->put($this->appointmentUrl(), ['scheduled_at' => '21/10/2026 10:00'])
            ->assertSessionHasErrors('scheduled_at');
        $this->actingAs($this->doctor)->post('/diagnosis', $this->consultation(['next_appointment_at' => '2026-10-20T10:00']))
            ->assertSessionHasErrors('next_appointment_at');
        $this->assertSame(0, Appointment::count());
        $this->assertSame(0, Diagnosis::count());

        // 10:00 in Santiago is 13:00 UTC, after "now" (12:00 UTC): the time is read in the clinic timezone.
        $this->actingAs($this->doctor)->put($this->appointmentUrl(), ['scheduled_at' => '2026-10-21T10:00'])
            ->assertSessionHasNoErrors();
        $this->assertSame('2026-10-21 13:00:00', Appointment::sole()->scheduled_at->format('Y-m-d H:i:s'));
    }

    #[Group('US-6.1/AC-7')]
    public function test_administrator_cannot_schedule_or_cancel(): void
    {
        $appointment = $this->scheduled();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put($this->appointmentUrl(), ['scheduled_at' => '2026-11-10T09:00'])->assertForbidden();
        $this->actingAs($admin)->delete($this->appointmentUrl())->assertForbidden();

        $appointment->refresh();
        $this->assertSame(Appointment::SCHEDULED, $appointment->status);
        $this->assertSame('2026-11-04 13:30:00', $appointment->scheduled_at->format('Y-m-d H:i:s'));
    }
}
