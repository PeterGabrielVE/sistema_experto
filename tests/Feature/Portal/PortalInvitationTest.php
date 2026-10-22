<?php

namespace Tests\Feature\Portal;

use App\Events\PatientPortalAccessChanged;
use App\Listeners\RecordAuditTrail;
use App\Mail\PortalInvitationMail;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\PatientInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\TestCase;

/**
 * Inviting a patient to the portal from the clinical record (US-6.3).
 */
class PortalInvitationTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Patient $patient;

    private string $terms;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-21 12:00:00'); // 09:00 in Santiago
        config(['app.clinic_timezone' => 'America/Santiago']);

        $this->terms = tempnam(sys_get_temp_dir(), 'terms');
        file_put_contents($this->terms, "# Términos de uso\n\nTexto de la clínica.\n");
        config(['portal.terms_path' => $this->terms]);

        $this->doctor = User::factory()->create();
        $this->patient = Patient::factory()->create(['first_name' => 'Ana', 'email' => 'ana.rojas@correo.cl']);
        ClinicalRecord::create(['patient_id' => $this->patient->id, 'consultation_reason' => 'Prediabetes y acantosis']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        @unlink($this->terms);
        parent::tearDown();
    }

    private function invite(?User $user = null, ?Patient $patient = null)
    {
        $patient ??= $this->patient;

        return $this->actingAs($user ?? $this->doctor)->post(route('patient.portal.invite', $patient));
    }

    private function recordPage()
    {
        return $this->actingAs($this->doctor)->get(route('patient.clinical-record.show', $this->patient))->assertOk();
    }

    #[Group('US-6.3/AC-1')]
    public function test_doctor_invites_and_the_patient_gets_a_welcome_mail_without_clinical_data(): void
    {
        Mail::fake();

        $this->invite()
            ->assertRedirect(route('patient.clinical-record.show', $this->patient))
            ->assertSessionHas('status', 'Invitación al portal enviada a ana.rojas@correo.cl.');

        $invitation = PatientInvitation::sole();
        $this->assertSame($this->patient->id, $invitation->patient_id);
        $this->assertSame($this->doctor->id, $invitation->invited_by);
        $this->assertSame('ana.rojas@correo.cl', $invitation->email);
        $this->assertSame(PatientInvitation::SENT, $invitation->status);
        $this->assertSame('2026-10-28 12:00:00', $invitation->expires_at->format('Y-m-d H:i:s')); // 7 days (D-2)
        $this->assertTrue($invitation->sent_at->eq(now()));

        Mail::assertSent(PortalInvitationMail::class, function (PortalInvitationMail $mail) use ($invitation) {
            $this->assertTrue($mail->hasTo('ana.rojas@correo.cl'));
            // Only the hash is stored: the token travels in the mail.
            $this->assertSame(hash('sha256', $mail->token), $invitation->token_hash);
            $this->assertSame(64, strlen($mail->token));

            $mail->assertHasSubject('Su acceso al portal de pacientes');
            $mail->assertSeeInHtml('Hola Ana');
            $mail->assertSeeInHtml(url('portal/invitacion/'.$mail->token), false);
            $mail->assertSeeInText('válido hasta el 28/10/2026');
            $html = $mail->render();
            foreach (['Prediabetes', 'acantosis', 'IMC', 'diagnóstico', 'resistencia', 'Rojas'] as $clinical) {
                $this->assertStringNotContainsStringIgnoringCase($clinical, $html);
            }

            return true;
        });
    }

    #[Group('US-6.3/AC-1')]
    public function test_clinical_record_shows_the_invitation_state(): void
    {
        Mail::fake();
        $this->recordPage()
            ->assertSee('Sin acceso al portal.')
            ->assertSee(route('patient.portal.invite', $this->patient));

        $this->invite();
        $this->recordPage()->assertSee('Invitación enviada el 21/10/2026 09:00; vence el 28/10/2026 09:00.');

        Carbon::setTestNow('2026-10-29 12:00:00');
        $this->recordPage()
            ->assertSee('La invitación venció el 28/10/2026 09:00.')
            ->assertSee('Invitar de nuevo');

        // The team sees the state; only doctors get the button.
        $this->actingAs(User::factory()->chiefDoctor()->create())->get(route('patient.clinical-record.show', $this->patient))
            ->assertSee(route('patient.portal.invite', $this->patient));
    }

    #[Group('US-6.3/AC-1')]
    public function test_administrator_cannot_invite(): void
    {
        Mail::fake();

        $this->invite(User::factory()->admin()->create())->assertForbidden();

        $this->assertSame(0, PatientInvitation::count());
        Mail::assertNothingSent();
    }

    public static function notInvitable(): array
    {
        return [
            'without e-mail' => ['without', 'El paciente no tiene correo registrado.'],
            'e-mail of a member of the team' => ['staff', 'El correo ana.rojas@correo.cl ya lo usa otra cuenta del sistema: cada correo tiene una sola cuenta.'],
            'e-mail of another patient account' => ['patient', 'El correo ana.rojas@correo.cl ya lo usa otra cuenta del sistema: cada correo tiene una sola cuenta.'],
            'already has an account' => ['own', 'El paciente ya tiene acceso al portal.'],
        ];
    }

    #[Group('US-6.3/AC-2')]
    #[DataProvider('notInvitable')]
    public function test_patient_without_email_or_with_an_email_in_use_is_not_invited(string $case, string $reason): void
    {
        Mail::fake();
        match ($case) {
            'without' => $this->patient->update(['email' => null]),
            'staff' => User::factory()->create(['email' => 'ana.rojas@correo.cl']),
            'patient' => User::factory()->patient()->create(['email' => 'ana.rojas@correo.cl']),
            'own' => User::factory()->patient($this->patient)->create(['email' => 'ana.rojas@correo.cl']),
        };

        $this->invite()
            ->assertRedirect(route('patient.clinical-record.show', $this->patient))
            ->assertSessionHasErrors(['portal' => $reason]);

        $this->assertSame(0, PatientInvitation::count());
        Mail::assertNothingSent();
        $this->recordPage()->assertSee($case === 'own' ? 'Tiene acceso al portal' : 'Sin acceso al portal.');
    }

    #[Group('US-6.3/AC-8')]
    public function test_mail_failure_marks_the_invitation_and_inviting_again_retries(): void
    {
        // Queued jobs run inline in tests: the mail server fails on every attempt.
        Mail::shouldReceive('to')->andThrow(new RuntimeException('Connection refused'));

        $this->invite()
            ->assertRedirect(route('patient.clinical-record.show', $this->patient))
            ->assertSessionHasNoErrors();

        $failed = PatientInvitation::sole();
        $this->assertSame(PatientInvitation::FAILED, $failed->status);
        $this->assertTrue($failed->failed_at->eq(now()));
        $this->recordPage()
            ->assertSee('No se pudo enviar la invitación (21/10/2026 09:00).')
            ->assertSee('Reintentar');

        // Retrying is inviting again: the failed one is left unusable.
        Mail::swap(new MailManager(app())); // the mail server is back
        Mail::fake();
        $this->invite()->assertSessionHas('status');

        $this->assertSame(PatientInvitation::REVOKED, $failed->refresh()->status);
        $this->assertSame(PatientInvitation::SENT, PatientInvitation::latest('id')->first()->status);
        Mail::assertSentCount(1);
    }

    #[Group('US-6.3/AC-8')]
    public function test_patient_can_be_edited_while_the_mail_server_is_down(): void
    {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('Connection refused'));
        $this->invite();

        $this->actingAs($this->doctor)->putJson(route('patient.update', $this->patient), [
            'first_name' => 'Ana María', 'last_name' => $this->patient->last_name, 'email' => 'ana.rojas@correo.cl',
            'address' => 'Calle 1', 'birthdate' => '1990-01-01', 'gender' => 'M',
        ])->assertOk();

        $this->assertSame('Ana María', $this->patient->refresh()->first_name);
    }

    #[Group('US-6.3/AC-10')]
    public function test_cannot_invite_without_the_terms_text(): void
    {
        Mail::fake();
        $message = 'Falta el texto de los términos de uso del portal: no se puede invitar hasta que la clínica lo entregue.';

        config(['portal.terms_path' => $this->terms.'-missing']);
        $this->invite()->assertSessionHasErrors(['portal' => $message]);
        $this->recordPage()->assertSee($message);

        file_put_contents($this->terms, "  \n");
        config(['portal.terms_path' => $this->terms]);
        $this->invite()->assertSessionHasErrors(['portal' => $message]);

        $this->assertSame(0, PatientInvitation::count());
        Mail::assertNothingSent();
    }

    #[Group('US-6.3/AC-9')]
    public function test_invitation_is_audited_without_clinical_data(): void
    {
        Mail::fake();
        Event::fake([PatientPortalAccessChanged::class]);

        $this->invite();

        $invitation = PatientInvitation::sole();
        Event::assertDispatched(PatientPortalAccessChanged::class, fn ($e) => $e->action === 'invited'
            && $e->patient->is($this->patient) && $e->actor->is($this->doctor)
            && $e->context === ['invitation_id' => $invitation->id]);

        $channel = Mockery::mock();
        $channel->shouldReceive('info')->once()->with('portal.invited', Mockery::on(
            fn ($context) => $context['actor_id'] === $this->doctor->id
                && $context['patient_id'] === $this->patient->id
                && $context['invitation_id'] === $invitation->id
                && ! array_key_exists('email', $context)
        ));
        Log::shouldReceive('channel')->with('audit')->andReturn($channel);

        (new RecordAuditTrail)->handlePatientPortalAccessChanged(
            new PatientPortalAccessChanged($this->patient, $this->doctor, 'invited', ['invitation_id' => $invitation->id]),
        );
    }
}
