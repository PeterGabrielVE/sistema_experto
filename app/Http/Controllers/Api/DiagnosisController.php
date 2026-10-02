<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EvaluateDiagnosisRequest;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\Recommendation;
use App\Services\DiagnosisService;
use App\Services\ExpertDiagnosisService;
use App\Services\InferenceEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * REST API of the diagnosis: nutritional category (InferenceEngine), its
 * recommendations and the clinical evaluation of the expert service.
 * "evaluation" is null when the expert service is not configured or fails.
 */
class DiagnosisController extends Controller
{
    public function __construct(
        private InferenceEngine $engine,
        private ExpertDiagnosisService $expert,
    ) {
    }

    /**
     * Processes the parameters and returns the diagnosis. Nothing is stored.
     */
    public function evaluate(EvaluateDiagnosisRequest $request): JsonResponse
    {
        $facts = $request->facts();
        $weight = $facts['anthropometry']['weight_kg'];
        $height = $facts['anthropometry']['height_cm'];

        $inference = $this->engine->classify([
            'weight' => $weight,
            'size' => $height,
            'age' => $facts['age'],
            'gender' => $facts['sex'],
            'physical_activity' => (int) $request->validated('physical_activity'),
        ]);

        return response()->json(['data' => [
            'bmi' => round($weight / ($height / 100) ** 2, 2),
            'category' => $this->category($inference['id_rule'], $inference),
            'recommendations' => $this->recommendations($inference['id_rule']),
            'evaluation' => $this->expert->evaluateFacts($facts, ['api_user' => $request->user()->id]),
        ]]);
    }

    /**
     * A stored consultation. The clinical evaluation is for the medical team
     * only, like on the result page.
     */
    public function show(Diagnosis $diagnosis, DiagnosisService $diagnoses): JsonResponse
    {
        Gate::authorize('view', $diagnosis);

        $patient = Patient::findOrFail($diagnosis->id_patient);
        $rule = $diagnoses->categoryOf($diagnosis);
        $expert = Gate::allows('viewClinicalRecord', $patient) ? $this->expert->evaluate($diagnosis, $patient) : null;

        return response()->json(['data' => [
            'id' => $diagnosis->id,
            'patient_id' => $patient->id,
            'created_at' => $diagnosis->created_at?->toIso8601String(),
            'age' => $diagnosis->age,
            'weight_kg' => (float) $diagnosis->weight,
            'height_cm' => (float) $diagnosis->size,
            'physical_activity' => $diagnosis->physical_activity,
            'bmi' => (float) $diagnosis->imc,
            'category' => $this->category($rule, $diagnosis->only(['inference_source', 'inference_confidence', 'model_version'])),
            'recommendations' => $this->recommendations($rule),
            'evaluation' => $expert['result'] ?? null,
        ]]);
    }

    private function category(int $rule, array $inference): array
    {
        return [
            'id' => $rule,
            'label' => InferenceEngine::CATEGORIES[$rule] ?? null,
            'source' => $inference['inference_source'] ?? null,
            'confidence' => $inference['inference_confidence'] ?? null,
            'model_version' => $inference['model_version'] ?? null,
        ];
    }

    private function recommendations(int $rule): array
    {
        return Recommendation::where('id_rule', $rule)->pluck('description')->all();
    }
}
