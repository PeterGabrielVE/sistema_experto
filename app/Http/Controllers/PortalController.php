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
            'days' => collect([[__('Menú de hoy'), $today], [__('Menú de ayer'), $today->copy()->subDay()]])
                ->map(fn ($day) => [
                    'title' => $day[0],
                    'date' => $day[1],
                    'menu' => $adherence->menuFor($patient, $day[1]),
                    'marks' => $adherence->marksFor($patient, $day[1]),
                ])->all(),
        ]);
    }
}
