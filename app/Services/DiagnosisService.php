<?php

namespace App\Services;

use App\Events\DiagnosisCategoryConfirmed;
use App\Events\DiagnosisCreated;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\Recommendation;
use App\Models\User;

class DiagnosisService
{
    public function __construct(private InferenceEngine $engine)
    {
    }

    /**
     * Creates a diagnosis and classifies the patient with the inference engine.
     */
    public function create(Patient $patient, array $data, User $author): Diagnosis
    {
        $inference = $this->engine->classify([
            'weight' => $data['weight'],
            'size' => $data['size'],
            'age' => $data['age'],
            'gender' => $patient->gender,
            'physical_activity' => $data['physical_activity'],
        ]);

        $diagnosis = Diagnosis::create([
            ...$data,
            ...$inference,
            'id_patient' => $patient->id,
            'created_by' => $author->id,
        ]);

        DiagnosisCreated::dispatch($diagnosis, $author);

        return $diagnosis;
    }

    /**
     * A doctor confirms or corrects the category; these labels feed model retraining
     * (see ScheduleModelRetraining).
     */
    public function confirmCategory(Diagnosis $diagnosis, int $ruleId, User $actor): Diagnosis
    {
        $previousRule = $diagnosis->id_rule !== null ? (int) $diagnosis->id_rule : null;
        $previousSource = $diagnosis->inference_source;

        $diagnosis->update(['id_rule' => $ruleId, 'inference_source' => 'manual']);

        DiagnosisCategoryConfirmed::dispatch($diagnosis, $actor, $previousRule, $previousSource);

        return $diagnosis;
    }

    /**
     * Diagnoses created before the inference engine have no stored category.
     */
    public function categoryOf(Diagnosis $diagnosis): int
    {
        return $diagnosis->id_rule ?? InferenceEngine::ruleForImc((float) $diagnosis->imc);
    }

    /**
     * Everything the result page and the PDF need, but the generated meal plan
     * (ExpertDiagnosisService::mealPlan).
     */
    public function resultData(Diagnosis $diagnosis): array
    {
        $rule = $this->categoryOf($diagnosis);

        return [
            'diagnosis' => $diagnosis,
            'patient' => Patient::findOrFail($diagnosis->id_patient),
            'rule' => $rule,
            'categories' => InferenceEngine::CATEGORIES,
            'recomendations' => Recommendation::where('id_rule', $rule)->get(),
            // Linked clinical data (shown to the medical team only).
            'measurements' => $diagnosis->measurements()->latestFirst()->get(),
            'labResults' => $diagnosis->labResults()->latestFirst()->get(),
        ];
    }
}
