<?php

namespace App\Http\Controllers;

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
    public function home(Request $request)
    {
        $patient = $request->user()->patient;

        return view('portal.home', [
            'patient' => $patient,
            'appointment' => $patient->nextAppointment()?->load('doctor'),
            'mealPlan' => $patient->latestMealPlan(),
        ]);
    }
}
