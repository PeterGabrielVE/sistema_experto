<?php

namespace Tests\Feature\Portal;

use App\Enums\Role;
use App\Events\PatientPortalAccessChanged;
use App\Models\Patient;
use App\Models\PatientInvitation;
use App\Models\User;
use App\Support\PortalTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * The patient accepts the invitation: password, terms, account (US-6.3).
 */
class PortalAcceptInvitationTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'k3Y9wQ2mZ7pL4tR8vB1nC6xD0sF5gH2jK9qW3eR7tY1uI4oP8aS2dF6gH0jK3lZ5';

    private Patient $patient;

    private PatientInvitation $invitation;

    private string $terms;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-21 12:00:00');

        $this->terms = tempnam(sys_get_temp_dir(), 'terms');
        file_put_contents($this->terms, "# Términos de uso\n\nTexto de la clínica sobre sus datos.\n");
        config(['portal.terms_path' => $this->terms]);

        $this->patient = Patient::factory()->create(['first_name' => 'Ana', 'last_name' => 'Rojas', 'email' => 'ana.rojas@correo.cl']);
        $this->invitation = PatientInvitation::create([
            'patient_id' => $this->patient->id, 'invited_by' => User::factory()->create()->id, 'email' => 'ana.rojas@correo.cl',
            'token_hash' => hash('sha256', self::TOKEN), 'expires_at' => now()->addDays(7),
            'status' => PatientInvitation::SENT, 'sent_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        @unlink($this->terms);
        parent::tearDown();
    }

    private function accept(array $overrides = [], string $token = self::TOKEN)
    {
        return $this->post(route('portal.invitation', $token), [
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'terms' => '1', 'terms_version' => PortalTerms::version(),
            ...$overrides,
        ]);
    }

    #[Group('US-6.3/AC-3')]
    public function test_patient_creates_the_password_accepts_the_terms_and_enters(): void
    {
        $this->get(route('portal.invitation', self::TOKEN))
            ->assertOk()
            ->assertSee('Texto de la clínica sobre sus datos.')
            ->assertSee('Acepto los términos de uso y el tratamiento de mis datos personales')
            ->assertSee(PortalTerms::version())
            ->assertDontSee('Rojas'); // nothing of the patient on the page

        $this->accept()->assertRedirect(route('portal.home'));

        $account = User::where('email', 'ana.rojas@correo.cl')->sole();
        $this->assertAuthenticatedAs($account);
        $this->assertSame(Role::Patient, $account->role());
        $this->assertSame($this->patient->id, $account->patient_id);
        $this->assertSame('Ana Rojas', $account->name);
        $this->assertTrue(Hash::check('secret123', $account->password));
        $this->assertSame(PortalTerms::version(), $account->terms_version);
        $this->assertTrue($account->terms_accepted_at->eq(now()));

        $this->invitation->refresh();
        $this->assertSame(PatientInvitation::ACCEPTED, $this->invitation->status);
        $this->assertTrue($this->invitation->accepted_at->eq(now()));

        $this->get(route('portal.home'))->assertOk()->assertSee('Hola Ana');
        // The link is used: it does not work again.
        $this->post(route('logout'));
        $this->get(route('portal.invitation', self::TOKEN))->assertSee('Pida una nueva invitación a su profesional.');
    }

    #[Group('US-6.3/AC-3')]
    public function test_without_accepting_the_terms_no_account_is_created(): void
    {
        $this->accept(['terms' => null])->assertSessionHasErrors('terms');

        // The text changed while the page was open: it is shown again to accept the new one.
        $this->accept(['terms_version' => 'sha256:000000000000'])
            ->assertSessionHasErrors(['terms' => 'Los términos cambiaron mientras tenía la página abierta. Léalos de nuevo y acéptelos.']);

        $this->assertSame(0, User::where('email', 'ana.rojas@correo.cl')->count());
        $this->assertSame(PatientInvitation::SENT, $this->invitation->refresh()->status);
        $this->assertGuest();
    }

    #[Group('US-6.3')] // NFR-2
    public function test_password_rules_match_the_staff(): void
    {
        $this->accept(['password' => 'abc12', 'password_confirmation' => 'abc12'])->assertSessionHasErrors('password');
        $this->accept(['password' => 'solamenteletras', 'password_confirmation' => 'solamenteletras'])->assertSessionHasErrors('password');
        $this->accept(['password_confirmation' => 'otra123'])->assertSessionHasErrors('password');

        $this->assertSame(0, User::where('email', 'ana.rojas@correo.cl')->count());
        // The session lasts as long as the staff one: there is no separate setting.
        $this->assertArrayNotHasKey('session_lifetime', config('portal'));
    }

    public static function unusableLinks(): array
    {
        return [
            'expired (more than 7 days)' => ['expired'],
            'already used' => ['used'],
            'revoked by a newer invitation' => ['revoked'],
            'tampered token' => ['tampered'],
            'e-mail taken by another account since' => ['taken'],
        ];
    }

    #[Group('US-6.3/AC-3')]
    #[DataProvider('unusableLinks')]
    public function test_expired_used_revoked_or_tampered_link_creates_nothing(string $case): void
    {
        $token = self::TOKEN;
        match ($case) {
            'expired' => Carbon::setTestNow('2026-10-28 12:00:01'),
            'used' => $this->invitation->update(['status' => PatientInvitation::ACCEPTED]),
            'revoked' => $this->invitation->update(['status' => PatientInvitation::REVOKED]),
            'tampered' => $token = substr(self::TOKEN, 0, -1).'X',
            'taken' => User::factory()->create(['email' => 'ana.rojas@correo.cl']),
        };
        $users = User::count();

        $this->get(route('portal.invitation', $token))
            ->assertOk()
            ->assertSee('Este enlace ya no sirve.')
            ->assertSee('Pida una nueva invitación a su profesional.')
            ->assertDontSee('Ana');
        $this->accept([], $token)->assertSee('Pida una nueva invitación a su profesional.');

        $this->assertSame($users, User::count());
        $this->assertGuest();
    }

    #[Group('US-6.3/AC-9')]
    public function test_activation_is_audited_with_the_terms_version(): void
    {
        Event::fake([PatientPortalAccessChanged::class]);

        $this->accept();

        $account = User::where('email', 'ana.rojas@correo.cl')->sole();
        Event::assertDispatched(PatientPortalAccessChanged::class, fn ($e) => $e->action === 'activated'
            && $e->patient->is($this->patient) && $e->actor->is($account)
            && $e->context === ['invitation_id' => $this->invitation->id, 'terms_version' => PortalTerms::version()]);
    }
}
