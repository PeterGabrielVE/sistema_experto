<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;

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

        $foods = Food::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($group, fn ($query) => $query->where('item', $group))
            ->orderBy('item')->orderBy('name')->orderBy('id')
            ->get();

        return view('foods.index', [
            'foods' => $foods,
            'flags' => $foods->mapWithKeys(fn (Food $food) => [$food->id => $food->reviewFlags()])->filter(),
            'groups' => Food::query()->distinct()->orderBy('item')->pluck('item'),
            'allergens' => collect(config('clinical.meal_plan.allergens'))->map(fn ($allergen) => $allergen['label']),
            'search' => $search,
            'group' => $group,
        ]);
    }
}
