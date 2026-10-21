<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;

class AppointmentService
{
    /**
     * Schedules the next appointment of the patient, or changes the scheduled one:
     * a patient has at most one. A new date makes its reminder pending again.
     */
    public function schedule(Patient $patient, Carbon $at, ?string $notes, User $doctor, ?Diagnosis $diagnosis = null): Appointment
    {
        $appointment = $patient->nextAppointment()
            ?? new Appointment(['patient_id' => $patient->id, 'status' => Appointment::SCHEDULED]);

        $appointment->fill([
            'scheduled_at' => $at,
            'notes' => $notes,
            'user_id' => $doctor->id,
        ]);

        if ($diagnosis) {
            $appointment->diagnosis_id = $diagnosis->id;
        }

        if ($appointment->exists && $appointment->isDirty('scheduled_at')) {
            $appointment->fill(['reminder_status' => null, 'reminder_reason' => null, 'reminder_at' => null]);
        }

        $appointment->save();

        return $appointment;
    }

    public function cancel(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => Appointment::CANCELLED, 'cancelled_at' => now()]);

        return $appointment;
    }
}
