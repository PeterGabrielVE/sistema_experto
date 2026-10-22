<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The staff side (every route but the patient portal): a patient with a portal account gets 403
 * (US-6.3/AC-6). It runs before route model binding, so the answer never depends on whether a
 * record exists.
 */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->isPatient(), 403);

        return $next($request);
    }
}
