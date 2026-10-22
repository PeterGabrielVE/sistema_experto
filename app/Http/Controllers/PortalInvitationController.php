<?php

namespace App\Http\Controllers;

use App\Services\PortalAccessService;
use App\Support\PortalTerms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Link of the welcome mail (US-6.3/AC-3): the patient creates the password and accepts the
 * terms. Public, protected by the token; the pages show nothing of the patient.
 */
class PortalInvitationController extends Controller
{
    public function __construct(private PortalAccessService $portal) {}

    public function show(string $token)
    {
        if (! $this->portal->usableInvitation($token) || ! PortalTerms::available()) {
            return view('portal.invitation-unusable');
        }

        return view('portal.invitation', [
            'token' => $token,
            'terms' => PortalTerms::html(),
            'termsVersion' => PortalTerms::version(),
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $invitation = $this->portal->usableInvitation($token);
        if (! $invitation || ! PortalTerms::available()) {
            return view('portal.invitation-unusable');
        }

        $data = $request->validate([
            // Same rules as the team's passwords (NFR-2, PasswordRequest).
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
            'terms_version' => ['required', 'string'],
        ], [
            'terms.accepted' => __('Debe aceptar los términos de uso para crear su cuenta.'),
        ], ['password' => 'contraseña']);

        if ($data['terms_version'] !== PortalTerms::version()) {
            return back()->withErrors(['terms' => __('Los términos cambiaron mientras tenía la página abierta. Léalos de nuevo y acéptelos.')]);
        }

        $account = $this->portal->accept($invitation, $data['password'], $data['terms_version']);
        if (! $account) {
            return view('portal.invitation-unusable');
        }

        Auth::login($account);
        $request->session()->regenerate();

        return redirect()->route('portal.home');
    }
}
