<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\PatientEvolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Weight and HOMA-IR history in JSON (?period=all|3m|6m|12m), source of the patient's evolution chart.
 */
class PatientEvolutionController extends Controller
{
    public function __construct(private PatientEvolutionService $evolution) {}

    public function __invoke(Request $request, Patient $patient): JsonResponse
    {
        Gate::authorize('viewClinicalRecord', $patient);

        $validated = $request->validate([
            'period' => ['nullable', Rule::in(array_keys(PatientEvolutionService::PERIODS))],
        ]);

        return response()->json($this->evolution->evolution($patient, $validated['period'] ?? 'all'));
    }
}
