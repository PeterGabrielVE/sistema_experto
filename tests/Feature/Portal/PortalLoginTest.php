<?php

namespace Tests\Feature\Portal;

use App\Events\PatientPortalAccessChanged;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Patients log in and recover their password with the same screens as the team (US-6.3).
 */
class PortalLoginTest extends TestCase
{
    use RefreshDatabase;

    private User $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = User::factory()
            ->patient(Patient::factory()->create(['first_name' => 'Ana']))
            ->create(['email' => 'ana.rojas@correo.cl', 'password' => Hash::make('secret123')]);
    }

    #[Group('US-6.3/AC-4')]
    public function test_patient_logs_in_to_the_portal_and_staff_to_home(): void
    {
        $this->post(route('login'), ['email' => 'ana.rojas@correo.cl', 'password' => 'secret123'])
            ->assertRedirect(route('portal.home'));
        $this->assertAuthenticatedAs($this->account);
        // Already logged in: the login page sends each one to their side.
        $this->get(route('login'))->assertRedirect(route('portal.home'));
        $this->get(route('home'))->assertForbidden();

        $this->post(route('logout'));
        $doctor = User::factory()->create(['password' => Hash::make('secret123')]);
        $this->post(route('login'), ['email' => $doctor->email, 'password' => 'secret123'])->assertRedirect('/home');
        $this->get(route('login'))->assertRedirect('/home');
        $this->get(route('portal.home'))->assertForbidden(); // the team does not open the portal
    }

    #[Group('US-6.3/AC-4')]
    public function test_wrong_credentials_give_the_same_message_and_are_throttled(): void
    {
        $this->from(route('login'))->post(route('login'), ['email' => 'ana.rojas@correo.cl', 'password' => 'mala'])
            ->assertSessionHasErrors(['email' => __('auth.failed')]);
        $this->from(route('login'))->post(route('login'), ['email' => 'nadie@correo.cl', 'password' => 'mala'])
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        for ($i = 0; $i < 4; $i++) {
            $this->post(route('login'), ['email' => 'ana.rojas@correo.cl', 'password' => 'mala']);
        }
        $this->post(route('login'), ['email' => 'ana.rojas@correo.cl', 'password' => 'secret123'])
            ->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Demasiados intentos de acceso.', session('errors')->first('email'));
        $this->assertGuest();
    }

    #[Group('US-6.3/AC-5')]
    public function test_patient_resets_the_password_like_the_staff(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'ana.rojas@correo.cl'])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($this->account, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token, 'email' => 'ana.rojas@correo.cl', 'password' => 'nueva1234', 'password_confirmation' => 'nueva1234',
        ])->assertRedirect(route('portal.home'));

        $this->assertTrue(Hash::check('nueva1234', $this->account->refresh()->password));
        $this->assertAuthenticatedAs($this->account);
    }

    #[Group('US-6.3/AC-5')]
    public function test_reset_request_does_not_reveal_whether_the_email_has_an_account(): void
    {
        Notification::fake();

        $known = $this->from(route('password.request'))->post(route('password.email'), ['email' => 'ana.rojas@correo.cl']);
        $unknown = $this->from(route('password.request'))->post(route('password.email'), ['email' => 'nadie@correo.cl']);

        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors()->assertRedirect(route('password.request'));
        $this->assertSame(
            'Si el correo tiene una cuenta, le enviamos el enlace para restablecer su contraseña.',
            $unknown->getSession()->get('status'),
        );
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    #[Group('US-6.3/AC-9')]
    public function test_patient_login_is_audited(): void
    {
        Event::fake([PatientPortalAccessChanged::class]);

        $this->post(route('login'), ['email' => 'ana.rojas@correo.cl', 'password' => 'secret123']);
        $doctor = User::factory()->create(['password' => Hash::make('secret123')]);
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $doctor->email, 'password' => 'secret123']);

        Event::assertDispatchedTimes(PatientPortalAccessChanged::class, 1); // the team's logins are not portal events
        Event::assertDispatched(PatientPortalAccessChanged::class, fn ($e) => $e->action === 'login'
            && $e->actor->is($this->account) && $e->patient->is($this->account->patient));
    }
}
