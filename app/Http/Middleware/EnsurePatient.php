<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The patient portal (US-6.3): only patients with a portal account.
 */
class EnsurePatient
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isPatient() && $request->user()->patient_id, 403);

        return $next($request);
    }
}
