<?php

namespace App\Services;

use App\Events\ClinicalRecordSaved;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\User;

class ClinicalRecordService
{
    /**
     * Creates the patient's clinical record or updates it (one per patient).
     */
    public function save(Patient $patient, array $data, User $actor): ClinicalRecord
    {
        $record = $patient->clinicalRecord ?? new ClinicalRecord([
            'patient_id' => $patient->id,
            'created_by' => $actor->id,
        ]);

        $created = ! $record->exists;
        $record->fill($data);
        $changed = array_values(array_intersect(array_keys($record->getDirty()), ClinicalRecord::CLINICAL_FIELDS));

        if (! $created && $changed === []) {
            return $record;
        }

        $record->updated_by = $actor->id;
        $record->save();

        ClinicalRecordSaved::dispatch($record, $actor, $created, $changed);

        return $record;
    }
}
