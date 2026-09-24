<?php

namespace App\Http\Controllers;

use App\Enums\Alcohol;
use App\Enums\Smoking;
use App\Http\Requests\ClinicalRecordRequest;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Services\ClinicalRecordService;
use Illuminate\Support\Facades\Gate;

class ClinicalRecordController extends Controller
{
    public function __construct(private ClinicalRecordService $records)
    {
    }

    public function show(Patient $patient)
    {
        Gate::authorize('viewClinicalRecord', $patient);

        if (! $patient->clinicalRecord) {
            return redirect()->route('patient.clinical-record.edit', $patient)
                ->withStatus(__('El paciente aún no tiene ficha clínica. Complétela a continuación.'));
        }

        return view('clinical_records.show', [
            'patient' => $patient,
            'record' => $patient->clinicalRecord->load(['author', 'editor']),
        ]);
    }

    /**
     * Same form to register the record for the first time or to edit it.
     */
    public function edit(Patient $patient)
    {
        Gate::authorize('updateClinicalRecord', $patient);

        return view('clinical_records.edit', [
            'patient' => $patient,
            'record' => $patient->clinicalRecord ?? new ClinicalRecord,
            'conditions' => ClinicalRecord::CONDITIONS,
            'smokingOptions' => ['' => 'Sin información'] + Smoking::options(),
            'alcoholOptions' => ['' => 'Sin información'] + Alcohol::options(),
        ]);
    }

    public function update(ClinicalRecordRequest $request, Patient $patient)
    {
        $created = ! $patient->clinicalRecord;

        $this->records->save($patient, $request->validated(), $request->user());

        return redirect()->route('patient.clinical-record.show', $patient)->withStatus(
            $created ? __('Ficha clínica registrada correctamente.') : __('Ficha clínica actualizada correctamente.')
        );
    }
}
