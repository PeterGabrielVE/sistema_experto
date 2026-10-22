<?php

namespace Tests\Feature\Portal;

use App\Events\PatientPortalAccessChanged;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\PatientInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Removing a patient's portal access from the clinical record (US-6.3/AC-7).
 */
#[Group('US-6.3/AC-7')]
class PortalRevokeTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Patient $patient;

    private User $account;

    private string $terms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->terms = tempnam(sys_get_temp_dir(), 'terms');
        file_put_contents($this->terms, "# Términos\n\nTexto.\n");
        config(['portal.terms_path' => $this->terms]);

        $this->doctor = User::factory()->create();
        $this->patient = Patient::factory()->create(['email' => 'ana.rojas@correo.cl']);
        ClinicalRecord::create(['patient_id' => $this->patient->id, 'consultation_reason' => 'Control']);
        $this->account = User::factory()->patient($this->patient)
            ->create(['email' => 'ana.rojas@correo.cl', 'password' => Hash::make('secret123')]);
    }

    protected function tearDown(): void
    {
        @unlink($this->terms);
        parent::tearDown();
    }

    private function recordPage()
    {
        return $this->actingAs($this->doctor)->get(route('patient.clinical-record.show', $this->patient))->assertOk();
    }

    public function test_revoking_ends_the_session_and_blocks_login_until_a_new_invitation(): void
    {
        // The patient is in the portal, with a real session.
        $this->post(route('login'), ['email' => 'ana.rojas@correo.cl', 'password' => 'secret123']);
        $this->get(route('portal.home'))->assertOk();
        $session = session()->all();

        $this->recordPage()
            ->assertSee('Tiene acceso al portal')
            ->assertSee(route('patient.portal.revoke', $this->patient));
        $this->actingAs($this->doctor)->delete(route('patient.portal.revoke', $this->patient))
            ->assertRedirect(route('patient.clinical-record.show', $this->patient))
            ->assertSessionHas('status', 'Se quitó el acceso al portal. Para devolverlo, invítelo de nuevo.');

        $this->assertModelMissing($this->account);
        $this->assertModelExists($this->patient);

        // The next request of the open session finds no account: it is a guest again.
        $this->app['auth']->forgetGuards();
        $this->withSession($session)->get(route('portal.home'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login'), ['email' => 'ana.rojas@correo.cl', 'password' => 'secret123'])
            ->assertSessionHasErrors(['email' => __('auth.failed')]);
        $this->assertGuest();

        // A new invitation is possible.
        $this->recordPage()
            ->assertSee('Sin acceso al portal.')
            ->assertSee(route('patient.portal.invite', $this->patient));
    }

    public function test_revoking_also_cancels_a_pending_invitation(): void
    {
        $this->account->delete();
        $invitation = PatientInvitation::create([
            'patient_id' => $this->patient->id, 'email' => 'ana.rojas@correo.cl', 'token_hash' => hash('sha256', 'token'),
            'expires_at' => now()->addDays(7), 'status' => PatientInvitation::SENT, 'sent_at' => now(),
        ]);

        $this->recordPage()->assertSee('Anular invitación');
        $this->actingAs($this->doctor)->delete(route('patient.portal.revoke', $this->patient))->assertRedirect();

        $invitation->refresh();
        $this->assertSame(PatientInvitation::REVOKED, $invitation->status);
        $this->assertNull($invitation->token_hash);

        // Nothing left to revoke.
        $this->actingAs($this->doctor)->delete(route('patient.portal.revoke', $this->patient))->assertStatus(422);
    }

    public function test_only_doctors_revoke(): void
    {
        $this->actingAs(User::factory()->admin()->create())->delete(route('patient.portal.revoke', $this->patient))->assertForbidden();
        $this->actingAs($this->account)->delete(route('patient.portal.revoke', $this->patient))->assertForbidden();

        $this->actingAs(User::factory()->chiefDoctor()->create())->delete(route('patient.portal.revoke', $this->patient))->assertRedirect();
        $this->assertModelMissing($this->account);
    }

    public function test_deleting_the_patient_deletes_the_account(): void
    {
        Event::fake([PatientPortalAccessChanged::class]);

        $this->actingAs(User::factory()->chiefDoctor()->create())->delete(route('patient.destroy', $this->patient));

        $this->assertModelMissing($this->patient);
        $this->assertModelMissing($this->account);
        $this->assertSame(0, PatientInvitation::count());
    }
}
