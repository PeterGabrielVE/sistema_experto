<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdherenceRequest;
use App\Services\AdherenceService;

/**
 * The patient records how they followed the menu of today or yesterday (US-6.2).
 */
class PortalAdherenceController extends Controller
{
    public function update(AdherenceRequest $request, AdherenceService $adherence)
    {
        $adherence->record($request->user()->patient, $request->user(), $request->menu(), $request->validated('meals'));

        return redirect()->route('portal.home')->withStatus(__('Registro guardado.'));
    }
}
