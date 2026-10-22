<?php

namespace Tests\Feature\Portal;

use App\Mail\PortalInvitationMail;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\User;
use App\Support\PortalTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * The whole portal access of a patient leaves an audit trail with ids only (US-6.3/AC-9).
 */
class PortalAuditTest extends TestCase
{
    use RefreshDatabase;

    #[Group('US-6.3/AC-9')]
    public function test_invite_activate_login_and_revoke_are_audited_without_clinical_data(): void
    {
        $terms = tempnam(sys_get_temp_dir(), 'terms');
        file_put_contents($terms, "# Términos\n\nTexto.\n");
        config(['portal.terms_path' => $terms]);
        Mail::fake();

        $doctor = User::factory()->create();
        $patient = Patient::factory()->create(['first_name' => 'Ana', 'last_name' => 'Rojas', 'email' => 'ana.rojas@correo.cl']);
        ClinicalRecord::create(['patient_id' => $patient->id, 'consultation_reason' => 'Prediabetes']);

        $entries = [];
        $channel = Mockery::mock();
        $channel->shouldReceive('info')->andReturnUsing(function (string $action, array $context) use (&$entries) {
            $entries[] = [$action, $context];
        });
        Log::shouldReceive('channel')->with('audit')->andReturn($channel);

        // Invite, accept (activation and its login), log out and in again, revoke.
        $this->actingAs($doctor)->post(route('patient.portal.invite', $patient));
        $token = null;
        Mail::assertSent(PortalInvitationMail::class, function ($mail) use (&$token) {
            $token = $mail->token;

            return true;
        });
        $this->app['auth']->forgetGuards();
        $this->post(route('logout'));
        $this->post(route('portal.invitation.accept', $token), [
            'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => '1', 'terms_version' => PortalTerms::version(),
        ])->assertRedirect(route('portal.home'));
        $account = $patient->account()->sole();
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => 'ana.rojas@correo.cl', 'password' => 'secret123']);
        $this->post(route('logout'));
        $this->actingAs($doctor)->delete(route('patient.portal.revoke', $patient));

        $portal = array_values(array_filter($entries, fn ($entry) => str_starts_with($entry[0], 'portal.')));
        $this->assertSame(
            ['portal.invited', 'portal.activated', 'portal.login', 'portal.login', 'portal.revoked'],
            array_column($portal, 0),
        );

        [$invited, $activated, , $login, $revoked] = array_column($portal, 1);
        $this->assertSame([$doctor->id, $patient->id], [$invited['actor_id'], $invited['patient_id']]);
        $this->assertSame([$account->id, 'Patient', PortalTerms::version()], [$activated['actor_id'], $activated['actor_role'], $activated['terms_version']]);
        $this->assertSame([$account->id, $patient->id], [$login['actor_id'], $login['patient_id']]);
        $this->assertSame([$doctor->id, $patient->id, $account->id], [$revoked['actor_id'], $revoked['patient_id'], $revoked['account_id']]);

        // Who, when (the log line) and which patient: no names, e-mails nor clinical data.
        $logged = json_encode($portal);
        foreach (['Ana', 'Rojas', 'ana.rojas', 'Prediabetes', 'secret123', $token] as $private) {
            $this->assertStringNotContainsString($private, $logged);
        }

        @unlink($terms);
    }
}
