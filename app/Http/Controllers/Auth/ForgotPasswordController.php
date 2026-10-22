<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * An e-mail without an account gets the same answer as one with it, so the page does not
     * reveal who has an account (US-6.3/AC-5). Other failures (throttling) are still shown.
     */
    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        if ($response === Password::INVALID_USER) {
            return $this->sendResetLinkResponse($request, Password::RESET_LINK_SENT);
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => trans($response)]);
    }
}
