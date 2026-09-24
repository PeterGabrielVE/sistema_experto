<?php

namespace App\Services;

use App\Events\DiagnosisCategoryConfirmed;
use App\Events\DiagnosisCreated;
use App\Models\Diagnosis;
use App\Models\Food;
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
     * Everything the result page and the PDF need to render the meal plan.
     */
    public function resultData(Diagnosis $diagnosis): array
    {
        $rule = $this->categoryOf($diagnosis);
        $oils = [72, 73, 74];

        return [
            'diagnosis' => $diagnosis,
            'patient' => Patient::findOrFail($diagnosis->id_patient),
            'foods' => Food::all(),
            'cereales' => Food::whereIn('item', ['Cereales', 'Pan'])->get(),
            'lacteos' => Food::where('item', 'Lácteos')->get(),
            'cereal_leg' => Food::whereIn('item', ['Cereales', 'Pan', 'Legumbres'])->get(),
            'verduras' => Food::where('item', 'Verduras')->get(),
            'proteins' => Food::where('id_group', 4)->get(),
            'proteinas' => Food::whereIn('id', [3, 4, 7])->get(),
            'aceites' => Food::whereIn('id', $oils)->get(),
            'lipids' => Food::where('id_group', 10)->whereNotIn('id', $oils)->get(),
            'lipidos' => Food::where('id_group', 10)->get(),
            'rule' => $rule,
            'categories' => InferenceEngine::CATEGORIES,
            'recomendations' => Recommendation::where('id_rule', $rule)->get(),
        ];
    }
}
