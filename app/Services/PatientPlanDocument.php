<?php

namespace App\Services;

use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\Recommendation;

/**
 * The plan for the patient (US-5.2): what the printable view needs, from the saved plan only
 * (D-3), so it is what the doctor reviewed and does not change with the catalog or the rules.
 * No call to the Python services. Neither the BMI nor the category nor other data of the clinical
 * record but the allergies (D-2).
 */
class PatientPlanDocument
{
    /** Totals of each day shown to the patient: energy and macronutrients, not the quality ceilings. */
    private const TOTALS = ['energy', 'carbohydrates', 'proteins', 'fats'];

    public function __construct(private DiagnosisService $diagnoses, private MealPlanService $plans) {}

    /**
     * @return array<string, mixed>|null null without a saved plan
     */
    public function data(Diagnosis $diagnosis): ?array
    {
        $saved = $diagnosis->mealPlan()->first();
        if (! $saved || empty($saved->plan['days'])) {
            return null;
        }

        $patient = Patient::findOrFail($diagnosis->id_patient);

        return [
            'clinic' => [
                'name' => config('clinic.name'),
                'logo' => $this->logo(),
                'contact' => array_filter([
                    config('clinic.address'),
                    config('clinic.phone') ? __('Teléfono :phone', ['phone' => config('clinic.phone')]) : null,
                    config('clinic.email'),
                ]),
            ],
            'patientName' => $patient->fullName(),
            'date' => $diagnosis->created_at->copy()->setTimezone(config('app.clinic_timezone'))->format('d/m/Y'),
            'professional' => $diagnosis->user?->name,
            'days' => array_map(fn (array $day) => [
                'day' => $day['day'],
                'totals' => array_intersect_key($day['totals'] ?? [], array_flip(self::TOTALS)),
                'meals' => array_values(array_filter($day['meals'], fn (array $meal) => ! empty($meal['items']))),
            ], $saved->plan['days']),
            // Of the category, without naming it.
            'recommendations' => Recommendation::where('id_rule', $this->diagnoses->categoryOf($diagnosis))
                ->orderBy('id')->pluck('description')->all(),
            // The text the generator received; read now, as a safety warning it must be the current one.
            'allergies' => $this->plans->allergies($diagnosis)[0] ?? null,
        ];
    }

    /**
     * The logo as a data: URI, so DomPDF needs no file or network access; null when it is missing.
     */
    private function logo(): ?string
    {
        $path = config('clinic.logo_path');
        if (! $path || ! is_file($path)) {
            return null;
        }

        $type = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => null,
        };

        return $type ? 'data:'.$type.';base64,'.base64_encode(file_get_contents($path)) : null;
    }
}
