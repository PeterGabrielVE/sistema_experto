<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmDiagnosisCategoryRequest;
use App\Http\Requests\MacroPlanRequest;
use App\Http\Requests\StoreDiagnosisRequest;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Services\ClinicalMeasurementService;
use App\Services\DiagnosisService;
use App\Services\ExpertDiagnosisService;
use App\Services\MealPlanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DiagnosisController extends Controller
{
    public function __construct(private DiagnosisService $diagnoses)
    {
    }

    /**
     * New consultation form for a patient, pre-filled with the last weight and
     * height of the clinical measurements registry.
     */
    public function create(Patient $patient, ClinicalMeasurementService $measurements)
    {
        Gate::authorize('create', Diagnosis::class);

        return view('diagnoses.index', [
            'patient' => $patient,
            'anthropometry' => $measurements->latestAnthropometry($patient),
        ]);
    }

    /**
     * Suggested macronutrient distribution of the expert service for the consultation
     * form, with the values of its fields: grams per day, grams per main meal (a third,
     * used by the meal plan tables), energy target and kcal per kg. data is null when
     * the service is not configured or fails; the doctor then fills in the fields.
     */
    public function macros(MacroPlanRequest $request, Patient $patient, ExpertDiagnosisService $expert): JsonResponse
    {
        $plan = $expert->macroPlan($patient, $request->validated());

        if (($plan['status'] ?? null) !== 'calculado') {
            return response()->json(['data' => $plan, 'fields' => null]);
        }

        $grams = array_map(fn ($macro) => $macro['grams'], $plan['macros']);

        return response()->json(['data' => $plan, 'fields' => [
            'carbohydrate' => $grams['carbohydrates'],
            'lipido' => $grams['fats'],
            'protein' => $grams['proteins'],
            'isocaloric_carbohydrate' => round($grams['carbohydrates'] / 3, 2),
            'isocaloric_lipido' => round($grams['fats'] / 3, 2),
            'isocaloric_protein' => round($grams['proteins'] / 3, 2),
            'result_pulgar' => $plan['energy']['target'],
            'imc_desired' => round($plan['energy']['target'] / $request->validated('weight'), 1),
        ]]);
    }

    public function store(StoreDiagnosisRequest $request)
    {
        $patient = Patient::findOrFail($request->validated('id_patient'));

        $diagnosis = $this->diagnoses->create(
            $patient,
            $request->safe()->except('id_patient'),
            $request->user(),
        );

        return redirect()->route('result', $diagnosis)->withStatus(__('Diagnóstico creado correctamente.'));
    }

    /**
     * Consultation history of a patient.
     */
    public function history(Patient $patient)
    {
        Gate::authorize('view', $patient);

        return view('diagnoses.show', [
            'patient' => $patient,
            'diagnoses' => Diagnosis::where('id_patient', $patient->id)->latest()->get(),
        ]);
    }

    public function result(Request $request, Diagnosis $diagnosis, ExpertDiagnosisService $expert, MealPlanService $plans)
    {
        Gate::authorize('view', $diagnosis);

        $data = $this->diagnoses->resultData($diagnosis);

        // Clinical evaluation: medical team only, like the linked clinical data.
        $data['expert'] = Gate::allows('viewClinicalRecord', $data['patient'])
            ? $expert->evaluate($diagnosis, $data['patient'])
            : null;

        // The saved menu proposal, otherwise a generated one (?variante=n, ?dias=n, ?presupuesto=CLP).
        $data['variant'] = MealPlanController::variant($request);
        $data['days'] = MealPlanController::days($request);
        $data['savedPlan'] = $diagnosis->mealPlan()->with('author')->first();
        $data['mealPlan'] = $data['savedPlan']?->plan ?? $plans->generate($diagnosis, $data['variant'], $data['days'], $data['expert'], MealPlanController::budget($request));

        return view('diagnoses.result', $data);
    }

    /**
     * The result as PDF, with the saved menu proposal or the same generated one shown on the page.
     */
    public function download(Request $request, Diagnosis $diagnosis, MealPlanService $plans)
    {
        Gate::authorize('view', $diagnosis);

        return Pdf::loadView('result-pdf', [
            ...$this->diagnoses->resultData($diagnosis),
            'mealPlan' => $diagnosis->mealPlan?->plan
                ?? $plans->generate($diagnosis, MealPlanController::variant($request), MealPlanController::days($request), budget: MealPlanController::budget($request)),
        ])->stream('diagnostico-'.$diagnosis->id.'.pdf');
    }

    public function updateRule(ConfirmDiagnosisCategoryRequest $request, Diagnosis $diagnosis)
    {
        $this->diagnoses->confirmCategory($diagnosis, (int) $request->validated('id_rule'), $request->user());

        return redirect()->route('result', $diagnosis)->withStatus(__('Categoría actualizada.'));
    }
}
