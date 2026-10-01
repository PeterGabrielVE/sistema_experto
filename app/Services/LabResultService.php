<?php

namespace App\Services;

use App\Events\LabResultRecorded;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\User;

class LabResultService
{
    public function create(Patient $patient, array $data, User $actor): LabResult
    {
        $labResult = $patient->labResults()->create([
            ...$data,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        LabResultRecorded::dispatch(
            $labResult, $actor, LabResultRecorded::CREATED,
            array_keys(array_filter($labResult->only(LabResult::CLINICAL_FIELDS), fn ($v) => $v !== null)),
        );

        return $labResult;
    }

    public function update(LabResult $labResult, array $data, User $actor): LabResult
    {
        $labResult->fill($data);
        $changed = array_values(array_intersect(array_keys($labResult->getDirty()), LabResult::CLINICAL_FIELDS));

        if ($changed === []) {
            return $labResult;
        }

        $labResult->updated_by = $actor->id;
        $labResult->save();

        LabResultRecorded::dispatch($labResult, $actor, LabResultRecorded::UPDATED, $changed);

        return $labResult;
    }

    public function delete(LabResult $labResult, User $actor): void
    {
        $labResult->delete();

        LabResultRecorded::dispatch($labResult, $actor, LabResultRecorded::DELETED);
    }

    /**
     * Chronological series for the evolution chart (oldest first).
     *
     * @return array<int, array<string, mixed>>
     */
    public function series(Patient $patient, int $limit = 60): array
    {
        return $patient->labResults()
            ->latestFirst()
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn (LabResult $r) => [
                'date' => $r->taken_at->format('d/m/Y'),
                ...$r->only(array_keys(LabResult::ANALYTES)),
                'homa_ir' => $r->homaIr(),
                'tyg' => $r->tygIndex(),
            ])
            ->values()
            ->all();
    }
}
