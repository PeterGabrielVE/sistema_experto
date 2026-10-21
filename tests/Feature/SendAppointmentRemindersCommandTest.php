<?php

namespace Tests\Feature;

use App\Jobs\SendAppointmentReminder;
use App\Models\Appointment;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\TestCase;

/**
 * Daily command that queues the reminders of tomorrow's appointments (US-6.1, D-2).
 */
class SendAppointmentRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 09:00 in Santiago (UTC-3 in October): "tomorrow" is 2026-10-22 there.
        Carbon::setTestNow('2026-10-21 12:00:00');
        config(['app.clinic_timezone' => 'America/Santiago']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * An appointment (UTC) of a patient who can receive reminders, unless overridden.
     */
    private function appointment(string $at, array $patient = [], array $attributes = []): Appointment
    {
        return Appointment::factory()->create([
            'patient_id' => Patient::factory()->create(['appointment_reminders' => true, ...$patient])->id,
            'scheduled_at' => $at,
            ...$attributes,
        ]);
    }

    private function recordPage(Appointment $appointment)
    {
        ClinicalRecord::firstOrCreate(['patient_id' => $appointment->patient_id], ['consultation_reason' => 'Control']);

        return $this->actingAs(User::factory()->create())
            ->get(route('patient.clinical-record.show', $appointment->patient_id))
            ->assertOk();
    }

    #[Group('US-6.1/AC-2')]
    public function test_queues_a_reminder_for_tomorrows_appointments_only(): void
    {
        Queue::fake();
        $tomorrow = $this->appointment('2026-10-22 13:30:00');      // 10:30 Santiago
        $today = $this->appointment('2026-10-21 18:00:00');         // later today
        $afterTomorrow = $this->appointment('2026-10-23 13:00:00');

        $this->artisan('appointments:send-reminders')
            ->expectsOutputToContain('Recordatorios para el 22/10/2026: 1 encolados')
            ->assertSuccessful();

        Queue::assertPushed(SendAppointmentReminder::class, 1);
        Queue::assertPushed(SendAppointmentReminder::class, fn ($job) => $job->appointment->is($tomorrow));
        $this->assertSame(Appointment::REMINDER_QUEUED, $tomorrow->refresh()->reminder_status);
        $this->assertNull($today->refresh()->reminder_status);
        $this->assertNull($afterTomorrow->refresh()->reminder_status);
    }

    #[Group('US-6.1/AC-2')]
    public function test_tomorrow_is_computed_in_the_clinic_timezone(): void
    {
        Queue::fake();
        $lateTomorrow = $this->appointment('2026-10-23 02:30:00');  // 22/10 23:30 Santiago, already 23/10 in UTC
        $lateToday = $this->appointment('2026-10-22 01:00:00');     // 21/10 22:00 Santiago, already 22/10 in UTC

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $this->assertSame(Appointment::REMINDER_QUEUED, $lateTomorrow->refresh()->reminder_status);
        $this->assertNull($lateToday->refresh()->reminder_status);
    }

    #[Group('US-6.1/AC-2')]
    public function test_a_date_can_be_given(): void
    {
        Queue::fake();
        $appointment = $this->appointment('2026-10-23 13:00:00');

        $this->artisan('appointments:send-reminders', ['--date' => '2026-10-23'])->assertSuccessful();
        $this->artisan('appointments:send-reminders', ['--date' => '23/10/2026'])->assertFailed();

        $this->assertSame(Appointment::REMINDER_QUEUED, $appointment->refresh()->reminder_status);
    }

    #[Group('US-6.1/AC-2')]
    public function test_command_is_scheduled_daily_at_nine_in_santiago(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'appointments:send-reminders'));

        $this->assertNotNull($event);
        $this->assertSame('0 9 * * *', $event->expression);
        $this->assertSame('America/Santiago', (string) $event->timezone);
        $this->assertTrue($event->withoutOverlapping);
    }

    #[Group('US-6.1/AC-3')]
    public function test_running_the_command_twice_queues_one_reminder(): void
    {
        Queue::fake();
        $appointment = $this->appointment('2026-10-22 13:30:00');

        $this->artisan('appointments:send-reminders')->assertSuccessful();
        $this->artisan('appointments:send-reminders')
            ->expectsOutputToContain('0 encolados')
            ->assertSuccessful();

        Queue::assertPushed(SendAppointmentReminder::class, 1);
        $this->assertSame(Appointment::REMINDER_QUEUED, $appointment->refresh()->reminder_status);
    }

    #[Group('US-6.1/AC-4')]
    public function test_skips_patients_without_email_consent_or_unsubscribed_and_cancelled_appointments(): void
    {
        Queue::fake();
        $noEmail = $this->appointment('2026-10-22 13:00:00', ['email' => null]);
        $noConsent = $this->appointment('2026-10-22 14:00:00', ['appointment_reminders' => false]);
        $unsubscribed = $this->appointment('2026-10-22 15:00:00');
        app(PatientService::class)->unsubscribeFromReminders($unsubscribed->patient);
        $cancelled = $this->appointment('2026-10-22 16:00:00', [], ['status' => Appointment::CANCELLED, 'cancelled_at' => now()]);

        $this->artisan('appointments:send-reminders')
            ->expectsOutputToContain('0 encolados, 4 omitidos')
            ->assertSuccessful();

        Queue::assertNothingPushed();
        foreach (['no_email' => $noEmail, 'no_consent' => $noConsent, 'unsubscribed' => $unsubscribed, 'cancelled' => $cancelled] as $reason => $appointment) {
            $appointment->refresh();
            $this->assertSame(Appointment::REMINDER_SKIPPED, $appointment->reminder_status, $reason);
            $this->assertSame($reason, $appointment->reminder_reason);
        }
    }

    #[Group('US-6.1/AC-4')]
    public function test_clinical_record_shows_why_a_reminder_was_not_sent(): void
    {
        Queue::fake();
        $appointment = $this->appointment('2026-10-22 13:30:00', ['email' => null]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $this->recordPage($appointment)->assertSee('Recordatorio no enviado: el paciente no tiene correo.');
    }

    #[Group('US-6.1/AC-5')]
    public function test_one_failure_does_not_stop_the_other_reminders(): void
    {
        // The queue runs jobs inline in tests (sync): the mail server fails for one patient only.
        Mail::shouldReceive('to')->andReturnUsing(function ($to) {
            if ($to === 'falla@correo.cl') {
                throw new RuntimeException('Connection refused');
            }

            return tap(Mockery::mock(), fn ($pending) => $pending->shouldReceive('send')->once());
        });
        $failing = $this->appointment('2026-10-22 13:00:00', ['email' => 'falla@correo.cl']);
        $other = $this->appointment('2026-10-22 14:00:00', ['email' => 'ok@correo.cl']);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        $failing->refresh();
        $this->assertSame(Appointment::REMINDER_FAILED, $failing->reminder_status);
        $this->assertSame('mail_error', $failing->reminder_reason);
        $this->assertSame(Appointment::REMINDER_SENT, $other->refresh()->reminder_status);
    }

    #[Group('US-6.1/AC-2')]
    #[Group('US-6.1/AC-5')]
    public function test_clinical_record_shows_the_reminder_state(): void
    {
        $appointment = $this->appointment('2026-10-22 13:30:00');
        $this->recordPage($appointment)->assertSee('Recordatorio: se enviará el día anterior.');

        $appointment->markReminder(Appointment::REMINDER_SENT);
        $this->recordPage($appointment)->assertSee('Recordatorio enviado el 21/10/2026 09:00.');

        $appointment->markReminder(Appointment::REMINDER_FAILED, 'mail_error');
        $this->recordPage($appointment)->assertSee('Recordatorio no enviado: falló el servidor de correo.');
    }
}
