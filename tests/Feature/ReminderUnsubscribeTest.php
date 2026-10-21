<?php

namespace Tests\Feature;

use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Consent to appointment reminders and the signed unsubscribe link of the e-mail (US-6.1, D-3).
 */
class ReminderUnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-21 12:00:00');
        config(['app.clinic_timezone' => 'America/Santiago']);

        $this->patient = Patient::factory()->create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => '11111111-1',
            'email' => 'ana.rojas@correo.cl', 'appointment_reminders' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function recordPage(Patient $patient)
    {
        ClinicalRecord::firstOrCreate(['patient_id' => $patient->id], ['consultation_reason' => 'Control']);

        return $this->actingAs(User::factory()->create())->get(route('patient.clinical-record.show', $patient))->assertOk();
    }

    #[Group('US-6.1/AC-8')]
    public function test_signed_link_shows_confirmation_without_patient_data(): void
    {
        $this->get($this->patient->reminderUnsubscribeUrl())
            ->assertOk()
            ->assertSee('¿Desea dejar de recibir recordatorios de consulta?')
            ->assertDontSee('Ana')
            ->assertDontSee('Rojas')
            ->assertDontSee('ana.rojas@correo.cl')
            ->assertDontSee('11111111-1')
            ->assertDontSee('resistencia a la insulina'); // the guest layout tagline

        // Opening the link (or a mail scanner following it) does not unsubscribe.
        $this->assertTrue($this->patient->refresh()->appointment_reminders);
        $this->assertNull($this->patient->reminders_unsubscribed_at);
    }

    #[Group('US-6.1/AC-8')]
    public function test_confirming_unsubscribes_and_clinical_record_shows_it(): void
    {
        $url = $this->patient->reminderUnsubscribeUrl();

        $this->post($url)->assertOk()->assertSee('No recibirá más recordatorios de consulta por correo.');

        $this->patient->refresh();
        $this->assertFalse($this->patient->appointment_reminders);
        $this->assertTrue($this->patient->reminders_unsubscribed_at->eq(now()));

        // A second confirmation keeps the first date.
        Carbon::setTestNow('2026-10-25 12:00:00');
        $this->post($url)->assertOk();
        $this->assertSame('2026-10-21', $this->patient->refresh()->reminders_unsubscribed_at->toDateString());

        $this->recordPage($this->patient)->assertSee('El paciente se dio de baja el 21/10/2026.');
    }

    #[Group('US-6.1/AC-8')]
    public function test_tampered_or_unsigned_link_is_rejected(): void
    {
        $other = Patient::factory()->create(['appointment_reminders' => true]);
        $url = $this->patient->reminderUnsubscribeUrl();

        $tamperedSignature = preg_replace('/signature=\w/', 'signature=0', $url);
        $otherPatient = str_replace("/{$this->patient->id}?", "/{$other->id}?", $url);
        $missingPatient = str_replace("/{$this->patient->id}?", '/'.($other->id + 100).'?', $url);
        $unsigned = strtok($url, '?');

        foreach ([$tamperedSignature, $otherPatient, $missingPatient, $unsigned] as $bad) {
            $this->assertNotSame($url, $bad);
            $this->get($bad)->assertForbidden(); // also for an unknown id: no 404 that reveals ids
            $this->post($bad)->assertForbidden();
        }

        $this->assertTrue($this->patient->refresh()->appointment_reminders);
        $this->assertTrue($other->refresh()->appointment_reminders);
    }

    #[Group('US-6.1/AC-4')]
    public function test_clinical_record_shows_why_the_patient_gets_no_reminders(): void
    {
        $this->recordPage($this->patient)->assertSee('Recordatorios por correo activados.');

        $noConsent = Patient::factory()->create(['appointment_reminders' => false]);
        $this->recordPage($noConsent)->assertSee('El paciente no ha aceptado recibir recordatorios por correo.');

        $noEmail = Patient::factory()->create(['email' => null, 'appointment_reminders' => true]);
        $this->recordPage($noEmail)->assertSee('El paciente no tiene correo registrado.');
    }

    #[Group('US-6.1/AC-4')]
    public function test_reminder_skip_reason(): void
    {
        $this->assertNull($this->patient->reminderSkipReason());
        $this->assertSame('no_consent', Patient::factory()->make(['appointment_reminders' => false])->reminderSkipReason());
        $this->assertSame('no_email', Patient::factory()->make(['email' => null, 'appointment_reminders' => true])->reminderSkipReason());
        $this->assertSame('unsubscribed', Patient::factory()->make([
            'appointment_reminders' => false, 'reminders_unsubscribed_at' => now(),
        ])->reminderSkipReason());
    }
}
