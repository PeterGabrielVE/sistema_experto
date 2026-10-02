<?php

namespace App\Http\Requests\Api;

use App\Models\Diagnosis;
use App\Models\LabResult;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Parameters of POST /api/v1/diagnoses/evaluate. Same shape and bounds as the
 * facts of the expert service (expert/app/schemas.py), plus the physical
 * activity that the category classifier needs.
 */
class EvaluateDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Diagnosis::class);
    }

    public function rules(): array
    {
        $rules = [
            'sex' => ['required', Rule::in(array_keys(Patient::GENDERS))],
            'age' => ['required', 'integer', 'between:0,120'],
            'physical_activity' => ['required', 'integer', 'between:0,4'],
            'anthropometry' => ['required', 'array:weight_kg,height_cm,waist_cm,hip_cm'],
            'anthropometry.weight_kg' => ['required', 'numeric', 'between:2,400'],
            'anthropometry.height_cm' => ['required', 'numeric', 'between:40,250'],
            'anthropometry.waist_cm' => ['nullable', 'numeric', 'between:30,250'],
            'anthropometry.hip_cm' => ['nullable', 'numeric', 'between:40,250'],
            'vitals' => ['nullable', 'array:systolic_bp,diastolic_bp'],
            'vitals.systolic_bp' => ['nullable', 'required_with:vitals.diastolic_bp', 'integer', 'between:60,260'],
            'vitals.diastolic_bp' => ['nullable', 'required_with:vitals.systolic_bp', 'integer', 'between:30,160', 'lt:vitals.systolic_bp'],
            'labs' => ['nullable', 'array:'.implode(',', array_keys(LabResult::ANALYTES))],
            'conditions' => ['nullable', 'array:diabetes,prediabetes,hypertension,dyslipidemia,pcos'],
            'conditions.*' => ['boolean'],
        ];

        foreach (LabResult::ANALYTES as $analyte => $range) {
            $rules["labs.$analyte"] = ['nullable', 'numeric', 'between:'.implode(',', $range['limits'])];
        }

        return $rules;
    }

    /**
     * The validated parameters as facts for ExpertDiagnosisService::evaluateFacts().
     */
    public function facts(): array
    {
        return [
            'sex' => $this->validated('sex'),
            'age' => (int) $this->validated('age'),
            'anthropometry' => $this->numbers('anthropometry'),
            'vitals' => array_map('intval', $this->numbers('vitals')),
            'labs' => $this->numbers('labs'),
            'conditions' => array_map('boolval', $this->validated('conditions') ?? []),
        ];
    }

    private function numbers(string $group): array
    {
        return array_map('floatval', array_filter($this->validated($group) ?? [], fn ($v) => $v !== null));
    }
}
