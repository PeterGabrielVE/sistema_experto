<?php

namespace App\Services;

use App\Models\ClinicalMeasurement;
use App\Models\ClinicalRecord;
use App\Models\Diagnosis;
use App\Models\LabResult;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Expert evaluation of a consultation by the Python expert diagnosis service:
 * clinical indices, glycemic status, insulin resistance, metabolic syndrome and
 * explained findings. Optional: null when the service is not configured or fails.
 */
class ExpertDiagnosisService
{
    public const STATUS_LABELS = [
        'glycemic_status' => [
            'normal' => 'Normal',
            'prediabetes' => 'Prediabetes',
            'rango_diabetes' => 'Rango de diabetes',
            'diabetes_conocida' => 'Diabetes registrada',
            'indeterminado' => 'Sin datos',
        ],
        'insulin_resistance' => [
            'no_sugerida' => 'No sugerida',
            'posible' => 'Posible',
            'probable' => 'Probable',
            'indeterminado' => 'Sin datos',
        ],
        'metabolic_syndrome' => [
            'ausente' => 'Ausente',
            'presente' => 'Presente',
            'indeterminado' => 'Indeterminado',
        ],
        'atherogenic_profile' => [
            'normal' => 'Normal',
            'limitrofe' => 'Limítrofe',
            'alterado' => 'Alterado',
            'indeterminado' => 'Sin datos',
        ],
    ];

    /**
     * @return array{facts: array, sources: array{measurement: ?ClinicalMeasurement, labResult: ?LabResult}, result: array}|null
     */
    public function evaluate(Diagnosis $diagnosis, Patient $patient): ?array
    {
        if (! config('services.expert.url') || ! isset(Patient::GENDERS[$patient->gender])) {
            return null;
        }

        $measurement = $this->dataOf($diagnosis, $patient->measurements(), 'measured_at');
        $labResult = $this->dataOf($diagnosis, $patient->labResults(), 'taken_at');
        $facts = $this->facts($diagnosis, $patient, $measurement, $labResult);
        $result = $this->evaluateFacts($facts, ['diagnosis' => $diagnosis->id]);

        return $result === null ? null : [
            'facts' => $facts,
            'sources' => ['measurement' => $measurement, 'labResult' => $labResult],
            'result' => $result,
        ];
    }

    /**
     * POST /evaluate with any facts (schema in expert/app/schemas.py): the result
     * page sends a stored consultation, the REST API the parameters it receives.
     *
     * @param  array  $context  added to the log entries
     */
    public function evaluateFacts(array $facts, array $context = []): ?array
    {
        if (! config('services.expert.url')) {
            return null;
        }

        // Missing values are left out; objects so that an empty group is sent as {} and not [].
        foreach (['anthropometry', 'vitals', 'labs', 'risk_factors'] as $group) {
            $facts[$group] = (object) array_filter((array) ($facts[$group] ?? []), fn ($v) => $v !== null);
        }
        $facts['conditions'] = (object) ($facts['conditions'] ?? []);

        try {
            $response = $this->client()->post('/evaluate', $facts);

            if ($response->successful() && is_array($response->json('findings'))) {
                return $response->json();
            }

            Log::warning('Expert service returned an invalid response', [
                ...$context,
                'status' => $response->status(),
                'errors' => $response->status() === 422 ? $response->json('detail') : null,
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Expert service unreachable', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Facts of a stored consultation (schema in expert/app/schemas.py).
     */
    public function facts(Diagnosis $diagnosis, Patient $patient, ?ClinicalMeasurement $measurement, ?LabResult $labResult): array
    {
        $record = $patient->clinicalRecord;

        return [
            'sex' => $patient->gender,
            'age' => $diagnosis->age ?? $patient->age,
            'anthropometry' => [
                // The consultation itself records weight and height.
                'weight_kg' => $measurement?->weight_kg ?? ((float) $diagnosis->weight ?: null),
                'height_cm' => $measurement?->height_cm ?? ((float) $diagnosis->size ?: null),
                'waist_cm' => $measurement?->waist_cm,
                'hip_cm' => $measurement?->hip_cm,
            ],
            'vitals' => [
                'systolic_bp' => $measurement?->systolic_bp,
                'diastolic_bp' => $measurement?->diastolic_bp,
            ],
            'labs' => $labResult?->only(array_keys(LabResult::ANALYTES)) ?? [],
            'conditions' => [
                'diabetes' => (bool) $record?->has_diabetes,
                'prediabetes' => (bool) $record?->has_prediabetes,
                'hypertension' => (bool) $record?->has_hypertension,
                'dyslipidemia' => (bool) $record?->has_dyslipidemia,
                'pcos' => (bool) $record?->has_pcos,
            ],
            // FINDRISC answers; null (not asked) is left out.
            'risk_factors' => [
                ...($record?->only(array_keys(ClinicalRecord::FINDRISC_QUESTIONS)) ?? []),
                'family_history_diabetes' => $record?->family_history_diabetes?->value,
            ],
        ];
    }

    /**
     * The latest record linked to the consultation; otherwise the patient's
     * latest one up to the consultation date (never later data).
     */
    private function dataOf(Diagnosis $diagnosis, HasMany $records, string $dateColumn): ClinicalMeasurement|LabResult|null
    {
        return (clone $records)->where('diagnosis_id', $diagnosis->id)->latestFirst()->first()
            ?? $records->whereDate($dateColumn, '<=', $diagnosis->created_at ?? now())->latestFirst()->first();
    }

    private function client()
    {
        return Http::baseUrl(config('services.expert.url'))
            ->withToken((string) config('services.expert.token'))
            ->timeout((int) config('services.expert.timeout'))
            ->acceptJson();
    }
}
