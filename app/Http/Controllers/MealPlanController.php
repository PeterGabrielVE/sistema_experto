<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveMealPlanRequest;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Services\MealPlanService;
use App\Services\PatientPlanDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Menu proposal editor of a consultation: start from the saved proposal or a generated
 * one (?nueva=1, ?variante=n, ?dias=n, ?presupuesto=CLP), change foods and portions, save.
 */
class MealPlanController extends Controller
{
    public function __construct(private MealPlanService $plans) {}

    /**
     * The plan for the patient in PDF (US-5.2): only the saved plan (D-3), for the doctors (D-4),
     * without calling the Python services (AC-6).
     */
    public function pdf(Diagnosis $diagnosis, PatientPlanDocument $document)
    {
        Gate::authorize('viewClinicalRecord', Patient::findOrFail($diagnosis->id_patient));

        $data = $document->data($diagnosis);
        // Only reached by typing the URL: the result page offers no button without a saved plan.
        abort_unless($data, response(__('La consulta no tiene un plan alimentario guardado.'), 404));

        // The date of the consultation, not the patient's name, in the file name.
        $date = $diagnosis->created_at->copy()->setTimezone(config('app.clinic_timezone'))->toDateString();

        return Pdf::loadView('meal_plans.pdf', $data)->download("plan-alimentario-{$date}.pdf");
    }

    public function edit(Request $request, Diagnosis $diagnosis)
    {
        Gate::authorize('create', Diagnosis::class);

        $targets = $this->plans->targets($diagnosis);
        if (! $targets) {
            return redirect()->route('result', $diagnosis)
                ->withErrors(['menu' => __('La consulta necesita requerimiento energético y gramos de carbohidratos, proteínas y lípidos.')]);
        }

        $saved = $diagnosis->mealPlan;
        $variant = self::variant($request);
        $days = self::days($request);
        $budget = self::budget($request);
        $plan = $saved && ! $request->boolean('nueva') ? $saved->plan : $this->plans->generate($diagnosis, $variant, $days, budget: $budget);

        return view('meal_plans.edit', [
            'diagnosis' => $diagnosis,
            'plan' => $plan,
            'saved' => $saved,
            // Saving it untouched keeps it as generated.
            'generated' => $plan !== null && ($plan['status'] ?? null) !== 'editado',
            'variant' => $variant,
            'days' => $plan ? count($plan['days']) : $days,
            'budget' => $plan['restrictions']['budget'] ?? $budget,
            'targets' => $targets,
            'limits' => $plan['limits'] ?? $this->plans->limits($diagnosis, $targets),
            'meals' => config('clinical.meal_plan.meals'),
            'catalog' => $this->plans->editorCatalog(),
        ]);
    }

    public function update(SaveMealPlanRequest $request, Diagnosis $diagnosis)
    {
        if (! $this->plans->targets($diagnosis)) {
            return back()->withErrors(['menu' => __('La consulta necesita requerimiento energético y gramos de macronutrientes.')]);
        }

        $edited = ! $request->validated('generated');
        $plan = $this->plans->rebuild($diagnosis, $request->validated('days'), edited: $edited);
        $this->plans->save($diagnosis, $plan, $request->user(), $edited);

        return redirect()->route('result', $diagnosis)->withFragment('meal-plan')
            ->withStatus(__('Propuesta de menú guardada.'));
    }

    /**
     * Back to the automatic proposal.
     */
    public function destroy(Diagnosis $diagnosis)
    {
        Gate::authorize('create', Diagnosis::class);

        $diagnosis->mealPlan?->delete();

        return redirect()->route('result', $diagnosis)->withFragment('meal-plan')
            ->withStatus(__('Propuesta descartada; se muestra la generada automáticamente.'));
    }

    public static function variant(Request $request): int
    {
        return min(max((int) $request->query('variante', 0), 0), 999);
    }

    public static function days(Request $request): int
    {
        return min(max((int) $request->query('dias', 1), 1), (int) config('clinical.meal_plan.max_days'));
    }

    /**
     * Daily budget in CLP (?presupuesto=n); none when missing or out of the expert service's range.
     */
    public static function budget(Request $request): ?int
    {
        $budget = (int) $request->query('presupuesto');

        return $budget >= 500 && $budget <= 1_000_000 ? $budget : null;
    }
}
