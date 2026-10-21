<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Models\Patient;
use App\Services\AppointmentService;
use Illuminate\Support\Facades\Gate;

/**
 * Next appointment of a patient, from the clinical record (US-6.1). It is also
 * scheduled when a consultation is saved (DiagnosisController::store).
 */
class AppointmentController extends Controller
{
    public function __construct(private AppointmentService $appointments) {}

    /**
     * Schedules the next appointment, or changes the scheduled one.
     */
    public function update(AppointmentRequest $request, Patient $patient)
    {
        $this->appointments->schedule($patient, $request->scheduledAt(), $request->validated('notes'), $request->user());

        return redirect()->route('patient.clinical-record.show', $patient)->withStatus(__('Próxima consulta guardada.'));
    }

    public function destroy(Patient $patient)
    {
        Gate::authorize('update', $patient);

        $appointment = $patient->nextAppointment();
        abort_unless($appointment, 422, __('El paciente no tiene una próxima consulta programada.'));

        $this->appointments->cancel($appointment);

        return redirect()->route('patient.clinical-record.show', $patient)->withStatus(__('Próxima consulta cancelada.'));
    }
}
