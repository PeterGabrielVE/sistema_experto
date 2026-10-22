<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * The patient portal (US-6.3). Always the patient of the session: no route takes an id.
 */
class PortalController extends Controller
{
    public function home(Request $request)
    {
        return view('portal.home', ['patient' => $request->user()->patient]);
    }
}
