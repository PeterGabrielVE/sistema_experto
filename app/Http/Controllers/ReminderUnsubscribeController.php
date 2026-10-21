<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\PatientService;

/**
 * Public page of the reminder e-mail link (US-6.1, D-3): no session, the signature
 * protects it. Opening the link only asks; confirming (POST to the same signed URL)
 * unsubscribes, so mail scanners that follow links do not. The pages show no patient
 * data, and the patient is looked up only after the signature is checked (no 404
 * that reveals which ids exist).
 */
class ReminderUnsubscribeController extends Controller
{
    public function __construct(private PatientService $patients) {}

    public function show(int $patient)
    {
        Patient::findOrFail($patient);

        return view('reminders.unsubscribe');
    }

    public function store(int $patient)
    {
        $this->patients->unsubscribeFromReminders(Patient::findOrFail($patient));

        return view('reminders.unsubscribed');
    }
}
