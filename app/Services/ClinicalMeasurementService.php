<?php

namespace App\Services;

use App\Events\ClinicalMeasurementRecorded;
use App\Models\ClinicalMeasurement;
use App\Models\Patient;
use App\Models\User;

class ClinicalMeasurementService
{
    public function create(Patient $patient, array $data, User $actor): ClinicalMeasurement
    {
        $measurement = $patient->measurements()->create([
            ...$data,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        ClinicalMeasurementRecorded::dispatch(
            $measurement, $actor, ClinicalMeasurementRecorded::CREATED,
            array_keys(array_filter($measurement->only(ClinicalMeasurement::CLINICAL_FIELDS), fn ($v) => $v !== null)),
        );

        return $measurement;
    }

    public function update(ClinicalMeasurement $measurement, array $data, User $actor): ClinicalMeasurement
    {
        $measurement->fill($data);
        $changed = array_values(array_intersect(array_keys($measurement->getDirty()), ClinicalMeasurement::CLINICAL_FIELDS));

        if ($changed === []) {
            return $measurement;
        }

        $measurement->updated_by = $actor->id;
        $measurement->save();

        ClinicalMeasurementRecorded::dispatch($measurement, $actor, ClinicalMeasurementRecorded::UPDATED, $changed);

        return $measurement;
    }

    public function delete(ClinicalMeasurement $measurement, User $actor): void
    {
        $measurement->delete();

        ClinicalMeasurementRecorded::dispatch($measurement, $actor, ClinicalMeasurementRecorded::DELETED);
    }

    /**
     * Last weight and last height registered (they may come from different controls),
     * used to pre-fill a new consultation.
     *
     * @return array{weight: ?ClinicalMeasurement, height: ?ClinicalMeasurement}
     */
    public function latestAnthropometry(Patient $patient): array
    {
        $latestWith = fn (string $field) => $patient->measurements()->whereNotNull($field)->latestFirst()->first();

        return [
            'weight' => $latestWith('weight_kg'),
            'height' => $latestWith('height_cm'),
        ];
    }

    /**
     * Chronological series for the evolution chart (oldest first).
     *
     * @return array<int, array<string, mixed>>
     */
    public function series(Patient $patient, int $limit = 60): array
    {
        return $patient->measurements()
            ->latestFirst()
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn (ClinicalMeasurement $m) => [
                'date' => $m->measured_at->format('d/m/Y'),
                'weight_kg' => $m->weight_kg,
                'bmi' => $m->bmi(),
                'waist_cm' => $m->waist_cm,
                'systolic_bp' => $m->systolic_bp,
                'diastolic_bp' => $m->diastolic_bp,
                'capillary_glucose' => $m->capillary_glucose,
            ])
            ->values()
            ->all();
    }
}
