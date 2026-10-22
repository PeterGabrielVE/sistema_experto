<?php

namespace App\Http\Controllers;

use App\Services\AdherenceService;
use Illuminate\Http\Request;

/**
 * The patient portal (US-6.3). Always the patient of the session: no route takes an id.
 */
class PortalController extends Controller
{
    /**
     * Name, next appointment and the latest saved meal plan, read only (D-3): no diagnoses,
     * exams nor other data of the clinical record.
     */
    public function home(Request $request, AdherenceService $adherence)
    {
        $patient = $request->user()->patient;
        $today = $adherence->today();

        return view('portal.home', [
            'patient' => $patient,
            'appointment' => $patient->nextAppointment()?->load('doctor'),
            'mealPlan' => $patient->latestMealPlan(),
            // The days the patient can record (US-6.2, D-3): menu of today and of yesterday.
            'days' => [
                ['title' => __('Menú de hoy'), 'date' => $today, 'menu' => $adherence->menuFor($patient, $today)],
                ['title' => __('Menú de ayer'), 'date' => $today->copy()->subDay(), 'menu' => $adherence->menuFor($patient, $today->copy()->subDay())],
            ],
        ]);
    }
}
