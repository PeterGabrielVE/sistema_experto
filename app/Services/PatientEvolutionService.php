<?php

namespace App\Services;

use App\Models\ClinicalMeasurement;
use App\Models\LabResult;
use App\Models\Patient;
use Illuminate\Support\Carbon;

/**
 * Weight and HOMA-IR over time for the patient's evolution chart (US-5.1).
 * The chart only draws this: periods, cut-off and data sufficiency are decided here.
 */
class PatientEvolutionService
{
    /** Period key => months back from today (null: the whole history). */
    public const PERIODS = ['all' => null, '3m' => 3, '6m' => 6, '12m' => 12];

    /** A trend needs at least two points. */
    private const MIN_POINTS = 2;

    /**
     * @return array{period: array, series: array{weight: array, homa_ir: array}}
     */
    public function evolution(Patient $patient, string $period = 'all'): array
    {
        $to = Carbon::today();
        $months = self::PERIODS[$period];
        $from = $months ? $to->copy()->subMonths($months) : null;

        return [
            'period' => ['key' => $period, 'from' => $from?->toDateString(), 'to' => $to->toDateString()],
            'series' => [
                'weight' => $this->weight($patient, $from),
                'homa_ir' => $this->homaIr($patient, $from),
            ],
        ];
    }

    private function weight(Patient $patient, ?Carbon $from): array
    {
        $points = $patient->measurements()
            ->whereNotNull('weight_kg')
            ->when($from, fn ($query) => $query->whereDate('measured_at', '>=', $from))
            ->orderBy('measured_at')->orderBy('id')
            ->get()
            ->map(fn (ClinicalMeasurement $m) => ['date' => $m->measured_at->toDateString(), 'value' => $m->weight_kg])
            ->all();

        return [
            'label' => __('Peso'),
            'unit' => 'kg',
            'points' => $points,
            'enough_data' => count($points) >= self::MIN_POINTS,
        ];
    }

    private function homaIr(Patient $patient, ?Carbon $from): array
    {
        $threshold = (float) config('clinical.insulin_resistance.homa_ir');
        $results = $patient->labResults()
            ->when($from, fn ($query) => $query->whereDate('taken_at', '>=', $from))
            ->orderBy('taken_at')->orderBy('id')
            ->get();

        // Same calculation as the lab results page; exams without glucose or insulin give null.
        $points = $results
            ->map(fn (LabResult $r) => ['date' => $r->taken_at->toDateString(), 'value' => $r->homaIr()])
            ->filter(fn (array $point) => $point['value'] !== null)
            ->map(fn (array $point) => [...$point, 'above_threshold' => $point['value'] > $threshold])
            ->values()
            ->all();

        return [
            'label' => __('HOMA-IR'),
            'unit' => '',
            'threshold' => $threshold,
            'points' => $points,
            'excluded' => $results->count() - count($points),
            'enough_data' => count($points) >= self::MIN_POINTS,
        ];
    }
}
