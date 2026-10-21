<?php

namespace Tests\Feature;

use App\Jobs\SendAppointmentReminder;
use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Models\ClinicalRecord;
use App\Models\Diagnosis;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientService;
use Database\Seeders\RulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\TestCase;

/**
 * Reminder e-mail of the next appointment and the job that sends it (US-6.1).
 */
class AppointmentReminderTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();
        // 09:00 in Santiago; the appointment is tomorrow at 10:30 (13:30 UTC).
        Carbon::setTestNow('2026-10-21 12:00:00');
        config(['app.clinic_timezone' => 'America/Santiago']);

        $this->patient = Patient::factory()->create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'email' => 'ana.rojas@correo.cl', 'appointment_reminders' => true,
        ]);
        $this->appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id' => User::factory()->create(['name' => 'Dra. Carolina Muñoz'])->id,
            'scheduled_at' => '2026-10-22 13:30:00',
            'notes' => 'Venir en ayuno de 8 horas.',
            'reminder_status' => Appointment::REMINDER_QUEUED, // claimed by the daily command
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Group('US-6.1/AC-2')]
    public function test_reminder_mail_has_date_time_professional_notes_and_unsubscribe_link(): void
    {
        $mail = new AppointmentReminderMail($this->appointment);

        $mail->assertHasSubject('Recordatorio: su consulta es mañana, jueves 22 de octubre, a las 10:30');
        $mail->assertSeeInHtml('Hola Ana');
        $mail->assertSeeInText('jueves 22 de octubre de 2026');
        $mail->assertSeeInText('10:30');
        $mail->assertSeeInText('Dra. Carolina Muñoz');
        $mail->assertSeeInText('Venir en ayuno de 8 horas.');
        $mail->assertSeeInHtml($this->patient->reminderUnsubscribeUrl(), false);
        $mail->assertSeeInText('Dejar de recibir recordatorios');
    }

    #[Group('US-6.1/AC-2')]
    public function test_job_sends_the_reminder_to_the_patient_and_marks_it_sent(): void
    {
        Mail::fake();

        SendAppointmentReminder::dispatch($this->appointment);

        Mail::assertSent(AppointmentReminderMail::class, fn ($mail) => $mail->hasTo('ana.rojas@correo.cl')
            && $mail->appointment->is($this->appointment));
        $this->appointment->refresh();
        $this->assertSame(Appointment::REMINDER_SENT, $this->appointment->reminder_status);
        $this->assertNull($this->appointment->reminder_reason);
        $this->assertTrue($this->appointment->reminder_at->eq(now()));
    }

    #[Group('US-6.1/AC-6')]
    public function test_reminder_mail_has_no_clinical_data(): void
    {
        $this->seed(RulesSeeder::class); // diagnoses.id_rule references rules.id
        Diagnosis::create([
            'id_patient' => $this->patient->id, 'weight' => 83.4, 'size' => 162, 'age' => 41,
            'physical_activity' => 1, 'imc' => 31.78, 'id_rule' => 4, 'result_pulgar' => 1850,
        ]);
        ClinicalRecord::create(['patient_id' => $this->patient->id, 'consultation_reason' => 'Prediabetes y acantosis']);
        LabResult::create([
            'patient_id' => $this->patient->id, 'taken_at' => '2026-10-01',
            'fasting_glucose' => 112.5, 'fasting_insulin' => 18.4, 'hba1c' => 5.9,
        ]);

        $mail = new AppointmentReminderMail($this->appointment->fresh());
        $html = $mail->render();

        foreach (['83,4', '83.4', '31,78', '31.78', '1850', '112', '18,4', '18.4', '5,9', '5.9', 'Prediabetes', 'acantosis',
            'IMC', 'HOMA', 'glicemia', 'insulina', 'diagnóstico', 'Obesidad', 'Rojas'] as $clinical) {
            $this->assertStringNotContainsStringIgnoringCase($clinical, $html, "The reminder must not include: {$clinical}");
        }
    }

    #[Group('US-6.1/AC-5')]
    public function test_job_retries_with_backoff(): void
    {
        $job = new SendAppointmentReminder($this->appointment);

        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 300], $job->backoff());
    }

    #[Group('US-6.1/AC-5')]
    public function test_a_mail_server_error_is_rethrown_for_the_queue_to_retry(): void
    {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('Connection refused'));

        try {
            (new SendAppointmentReminder($this->appointment))->handle();
            $this->fail('The error must reach the queue so it retries.');
        } catch (RuntimeException) {
        }

        // Still queued: the next attempt sends it.
        $this->assertSame(Appointment::REMINDER_QUEUED, $this->appointment->refresh()->reminder_status);
    }

    #[Group('US-6.1/AC-5')]
    public function test_a_failed_job_marks_the_reminder_as_failed(): void
    {
        (new SendAppointmentReminder($this->appointment))->failed(new RuntimeException('Connection refused'));

        $this->appointment->refresh();
        $this->assertSame(Appointment::REMINDER_FAILED, $this->appointment->reminder_status);
        $this->assertSame('mail_error', $this->appointment->reminder_reason);
        $this->assertTrue($this->appointment->reminder_at->eq(now()));
    }

    #[Group('US-6.1/AC-3')]
    public function test_a_retried_job_does_not_send_twice(): void
    {
        Mail::fake();
        $job = new SendAppointmentReminder($this->appointment);

        $job->handle();
        $job->handle(); // the queue runs it again (retry or duplicate)

        Mail::assertSentCount(1);
        $this->assertSame(Appointment::REMINDER_SENT, $this->appointment->refresh()->reminder_status);
    }

    #[Group('US-6.1/AC-3')]
    public function test_a_rescheduled_appointment_is_not_reminded_by_the_old_job(): void
    {
        Mail::fake();
        $job = new SendAppointmentReminder($this->appointment);
        // Changing the date makes the reminder pending again (AppointmentService::schedule).
        $this->appointment->update(['scheduled_at' => '2026-11-10 12:00:00', 'reminder_status' => null]);

        $job->handle();

        Mail::assertNothingSent();
        $this->assertNull($this->appointment->refresh()->reminder_status);
    }

    #[Group('US-6.1/AC-4')]
    public function test_job_skips_when_the_appointment_or_consent_changed_after_queueing(): void
    {
        Mail::fake();

        $this->appointment->update(['status' => Appointment::CANCELLED, 'cancelled_at' => now()]);
        (new SendAppointmentReminder($this->appointment))->handle();
        $this->appointment->refresh();
        $this->assertSame(Appointment::REMINDER_SKIPPED, $this->appointment->reminder_status);
        $this->assertSame('cancelled', $this->appointment->reminder_reason);

        $other = Appointment::factory()->create([
            'patient_id' => $this->patient->id, 'reminder_status' => Appointment::REMINDER_QUEUED,
        ]);
        app(PatientService::class)->unsubscribeFromReminders($this->patient);
        (new SendAppointmentReminder($other))->handle();
        $other->refresh();
        $this->assertSame(Appointment::REMINDER_SKIPPED, $other->reminder_status);
        $this->assertSame('unsubscribed', $other->reminder_reason);

        Mail::assertNothingSent();
    }
}
