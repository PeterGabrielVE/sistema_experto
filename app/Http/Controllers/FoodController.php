<?php

namespace App\Http\Controllers;

use App\Http\Requests\FoodRequest;
use App\Models\Food;
use App\Services\FoodCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The food catalog (Alimentos, US-4.2): the foods the meal plan generator uses,
 * with the data to review (Food::reviewFlags).
 */
class FoodController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'grupo' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['q'] ?? '');
        $group = $validated['grupo'] ?? null;

        $foods = Food::active()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($group, fn ($query) => $query->where('item', $group))
            ->orderBy('item')->orderBy('name')->orderBy('id')
            ->get();

        return view('foods.index', [
            'foods' => $foods,
            'flags' => $foods->mapWithKeys(fn (Food $food) => [$food->id => $food->reviewFlags()])->filter(),
            'groups' => Food::active()->distinct()->orderBy('item')->pluck('item'),
            'allergens' => collect(config('clinical.meal_plan.allergens'))->map(fn ($allergen) => $allergen['label']),
            'search' => $search,
            'group' => $group,
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Food::class);

        return view('foods.form', $this->formData(new Food));
    }

    public function store(FoodRequest $request, FoodCatalogService $catalog)
    {
        $food = $catalog->save(new Food, $request->validated(), $request->user());

        return $this->saved($food, __('Alimento creado correctamente.'));
    }

    public function edit(Food $food)
    {
        Gate::authorize('update', $food);

        return view('foods.form', $this->formData($food));
    }

    public function update(FoodRequest $request, Food $food, FoodCatalogService $catalog)
    {
        $catalog->save($food, $request->validated(), $request->user());

        return $this->saved($food, __('Alimento actualizado correctamente.'));
    }

    public function retire(Request $request, Food $food, FoodCatalogService $catalog)
    {
        Gate::authorize('retire', $food);
        abort_if($food->retired_at !== null, 422, __('El alimento ya estaba retirado.'));

        $catalog->retire($food, $request->user());

        return redirect()->route('foods.index')
            ->withStatus(__(':name se retiró del catálogo: ya no se usa en propuestas nuevas.', ['name' => $food->name]));
    }

    private function formData(Food $food): array
    {
        return [
            'food' => $food,
            'groups' => Food::groups(),
            'allergens' => collect(config('clinical.meal_plan.allergens'))->map(fn ($allergen) => $allergen['label']),
        ];
    }

    /**
     * Data to review do not stop saving (D-3): the doctor is told which.
     */
    private function saved(Food $food, string $message)
    {
        $redirect = redirect()->route('foods.index')->withStatus($message);

        if ($flags = $food->reviewFlags()) {
            $redirect->with('warning', __(':name se guardó, pero quedó con datos a revisar.', ['name' => $food->name]).' '.implode(' ', $flags));
        }

        return $redirect;
    }
}
