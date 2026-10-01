<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClinicalMeasurementRequest;
use App\Models\ClinicalMeasurement;
use App\Models\Patient;
use App\Services\ClinicalMeasurementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Registro de mediciones clínicas: history of controls of a patient.
 */
class ClinicalMeasurementController extends Controller
{
    public function __construct(private ClinicalMeasurementService $measurements)
    {
    }

    public function index(Patient $patient)
    {
        Gate::authorize('viewClinicalRecord', $patient);

        $measurements = $patient->measurements()->latestFirst()->with('author')->paginate(15);
        // Latest and previous measurement of the patient (not of the page) to show the variation.
        [$latest, $previous] = array_pad($patient->measurements()->latestFirst()->limit(2)->get()->all(), 2, null);

        return view('clinical_measurements.index', [
            'patient' => $patient,
            'measurements' => $measurements,
            'latest' => $latest,
            'previous' => $previous,
            'series' => $this->measurements->series($patient),
        ]);
    }

    public function create(Request $request, Patient $patient)
    {
        Gate::authorize('updateClinicalRecord', $patient);

        // Height rarely changes in adults: suggest the last one registered.
        $lastHeight = $patient->measurements()->whereNotNull('height_cm')->latestFirst()->value('height_cm');

        return view('clinical_measurements.form', [
            'patient' => $patient,
            'measurement' => new ClinicalMeasurement([
                'measured_at' => now(),
                'height_cm' => $lastHeight,
                // Coming from a consultation ("Registrar medición" on its result page).
                'diagnosis_id' => $request->integer('diagnosis') ?: null,
            ]),
            'consultations' => $patient->consultationOptions(),
        ]);
    }

    public function store(ClinicalMeasurementRequest $request, Patient $patient)
    {
        $this->measurements->create($patient, $request->validated(), $request->user());

        return redirect()->route('patient.measurements.index', $patient)
            ->withStatus(__('Medición registrada correctamente.'));
    }

    public function edit(Patient $patient, ClinicalMeasurement $measurement)
    {
        Gate::authorize('update', $measurement);

        return view('clinical_measurements.form', [
            'patient' => $patient,
            'measurement' => $measurement,
            'consultations' => $patient->consultationOptions(),
        ]);
    }

    public function update(ClinicalMeasurementRequest $request, Patient $patient, ClinicalMeasurement $measurement)
    {
        $this->measurements->update($measurement, $request->validated(), $request->user());

        return redirect()->route('patient.measurements.index', $patient)
            ->withStatus(__('Medición actualizada correctamente.'));
    }

    public function destroy(Request $request, Patient $patient, ClinicalMeasurement $measurement)
    {
        Gate::authorize('delete', $measurement);

        $this->measurements->delete($measurement, $request->user());

        return redirect()->route('patient.measurements.index', $patient)
            ->withStatus(__('Medición eliminada.'));
    }
}
