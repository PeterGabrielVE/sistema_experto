<?php

namespace App\Http\Controllers;

use App\Http\Requests\LabResultRequest;
use App\Models\LabResult;
use App\Models\Patient;
use App\Services\LabResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Exámenes de laboratorio: history of lab results of a patient.
 */
class LabResultController extends Controller
{
    public function __construct(private LabResultService $labResults)
    {
    }

    public function index(Patient $patient)
    {
        Gate::authorize('viewClinicalRecord', $patient);

        return view('lab_results.index', [
            'patient' => $patient,
            'labResults' => $patient->labResults()->latestFirst()->with(['author', 'diagnosis'])->paginate(15),
            'latest' => $patient->labResults()->latestFirst()->first(),
            'series' => $this->labResults->series($patient),
        ]);
    }

    public function create(Request $request, Patient $patient)
    {
        Gate::authorize('updateClinicalRecord', $patient);

        return view('lab_results.form', [
            'patient' => $patient,
            'labResult' => new LabResult([
                'taken_at' => now(),
                // Coming from a consultation ("Registrar examen" on its result page).
                'diagnosis_id' => $request->integer('diagnosis') ?: null,
            ]),
            'consultations' => $patient->consultationOptions(),
        ]);
    }

    public function store(LabResultRequest $request, Patient $patient)
    {
        $this->labResults->create($patient, $request->validated(), $request->user());

        return redirect()->route('patient.lab-results.index', $patient)
            ->withStatus(__('Examen registrado correctamente.'));
    }

    public function edit(Patient $patient, LabResult $labResult)
    {
        Gate::authorize('update', $labResult);

        return view('lab_results.form', [
            'patient' => $patient,
            'labResult' => $labResult,
            'consultations' => $patient->consultationOptions(),
        ]);
    }

    public function update(LabResultRequest $request, Patient $patient, LabResult $labResult)
    {
        $this->labResults->update($labResult, $request->validated(), $request->user());

        return redirect()->route('patient.lab-results.index', $patient)
            ->withStatus(__('Examen actualizado correctamente.'));
    }

    public function destroy(Request $request, Patient $patient, LabResult $labResult)
    {
        Gate::authorize('delete', $labResult);

        $this->labResults->delete($labResult, $request->user());

        return redirect()->route('patient.lab-results.index', $patient)
            ->withStatus(__('Examen eliminado.'));
    }
}
