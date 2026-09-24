<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecommendationRequest;
use App\Models\Recommendation;
use App\Models\Rule;
use Illuminate\Support\Facades\Gate;

class RecommendationController extends Controller
{
    public function index()
    {
        return view('recommendations.index', ['recommendations' => Recommendation::latest()->paginate(15)]);
    }

    public function create()
    {
        Gate::authorize('create', Recommendation::class);

        return view('recommendations.create', ['options' => $this->ruleOptions()]);
    }

    public function store(RecommendationRequest $request)
    {
        Recommendation::create($request->validated());

        return redirect()->route('recommendation.index')->withStatus(__('Recomendación creada correctamente.'));
    }

    /**
     * Recommendations of one category (rules.id).
     */
    public function show(Rule $recommendation)
    {
        Gate::authorize('view', $recommendation);

        return view('recommendations.index', [
            'recommendations' => Recommendation::where('id_rule', $recommendation->id)->latest()->paginate(15),
        ]);
    }

    public function edit(Recommendation $recommendation)
    {
        Gate::authorize('update', $recommendation);

        return view('recommendations.edit', ['re' => $recommendation]);
    }

    public function update(RecommendationRequest $request, Recommendation $recommendation)
    {
        $recommendation->update($request->validated());

        return redirect()->route('recommendation.index')->withStatus(__('Recomendación actualizada exitosamente.'));
    }

    public function destroy(Recommendation $recommendation)
    {
        Gate::authorize('delete', $recommendation);

        $recommendation->delete();

        return redirect()->route('recommendation.index')->withStatus(__('Recomendación eliminada exitosamente.'));
    }

    private function ruleOptions()
    {
        return Rule::pluck('name', 'id')->prepend('Seleccione...', '');
    }
}
