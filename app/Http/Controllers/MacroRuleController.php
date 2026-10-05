<?php

namespace App\Http\Controllers;

use App\Http\Requests\MacroRuleRequest;
use App\Models\MacroRule;
use Illuminate\Support\Facades\Gate;

/**
 * Macronutrient rules configured by the Doctor Jefe. The active ones are sent to
 * the expert service with every evaluation (ExpertDiagnosisService).
 */
class MacroRuleController extends Controller
{
    public function index()
    {
        return view('macro_rules.index', ['rules' => MacroRule::orderBy('id')->get()]);
    }

    public function create()
    {
        Gate::authorize('create', MacroRule::class);

        return view('macro_rules.form', ['rule' => new MacroRule(['operator' => '>', 'active' => true, 'actions' => []])]);
    }

    public function store(MacroRuleRequest $request)
    {
        MacroRule::create([...$request->ruleData(), 'created_by' => $request->user()->id]);

        return redirect()->route('macro-rules.index')->withStatus(__('Regla creada correctamente.'));
    }

    public function edit(MacroRule $macroRule)
    {
        Gate::authorize('update', $macroRule);

        return view('macro_rules.form', ['rule' => $macroRule]);
    }

    public function update(MacroRuleRequest $request, MacroRule $macroRule)
    {
        $macroRule->update($request->ruleData());

        return redirect()->route('macro-rules.index')->withStatus(__('Regla actualizada correctamente.'));
    }

    public function destroy(MacroRule $macroRule)
    {
        Gate::authorize('delete', $macroRule);

        $macroRule->delete();

        return redirect()->route('macro-rules.index')->withStatus(__('Regla eliminada correctamente.'));
    }
}
