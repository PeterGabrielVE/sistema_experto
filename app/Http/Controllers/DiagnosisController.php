<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmDiagnosisCategoryRequest;
use App\Http\Requests\StoreDiagnosisRequest;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Services\ClinicalMeasurementService;
use App\Services\DiagnosisService;
use App\Services\ExpertDiagnosisService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class DiagnosisController extends Controller
{
    public function __construct(private DiagnosisService $diagnoses)
    {
    }

    /**
     * New consultation form for a patient, pre-filled with the last weight and
     * height of the clinical measurements registry.
     */
    public function create(Patient $patient, ClinicalMeasurementService $measurements)
    {
        Gate::authorize('create', Diagnosis::class);

        return view('diagnoses.index', [
            'patient' => $patient,
            'anthropometry' => $measurements->latestAnthropometry($patient),
        ]);
    }

    public function store(StoreDiagnosisRequest $request)
    {
        $patient = Patient::findOrFail($request->validated('id_patient'));

        $diagnosis = $this->diagnoses->create(
            $patient,
            $request->safe()->except('id_patient'),
            $request->user(),
        );

        return redirect()->route('result', $diagnosis)->withStatus(__('Diagnóstico creado correctamente.'));
    }

    /**
     * Consultation history of a patient.
     */
    public function history(Patient $patient)
    {
        Gate::authorize('view', $patient);

        return view('diagnoses.show', [
            'patient' => $patient,
            'diagnoses' => Diagnosis::where('id_patient', $patient->id)->latest()->get(),
        ]);
    }

    public function result(Diagnosis $diagnosis, ExpertDiagnosisService $expert)
    {
        Gate::authorize('view', $diagnosis);

        $data = $this->diagnoses->resultData($diagnosis);

        // Clinical evaluation: medical team only, like the linked clinical data.
        $data['expert'] = Gate::allows('viewClinicalRecord', $data['patient'])
            ? $expert->evaluate($diagnosis, $data['patient'])
            : null;

        return view('diagnoses.result', $data);
    }

    public function download(Diagnosis $diagnosis)
    {
        Gate::authorize('view', $diagnosis);

        return Pdf::loadView('result-pdf', $this->diagnoses->resultData($diagnosis))
            ->stream('diagnostico-'.$diagnosis->id.'.pdf');
    }

    public function updateRule(ConfirmDiagnosisCategoryRequest $request, Diagnosis $diagnosis)
    {
        $this->diagnoses->confirmCategory($diagnosis, (int) $request->validated('id_rule'), $request->user());

        return redirect()->route('result', $diagnosis)->withStatus(__('Categoría actualizada.'));
    }
}
