<?php

namespace App\Http\Requests;

use App\Models\Food;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A food of the catalog (US-4.2/AC-2), with the column names of shared/food_catalog.csv.
 * Energy that does not match the macronutrients is not an error: FoodController warns.
 */
class FoodRequest extends FormRequest
{
    public const NUMBERS = ['grams', 'kcal', 'protein', 'fat', 'saturated_fat', 'cho', 'glycemic_index', 'price'];

    public function authorize(): bool
    {
        $food = $this->route('food');

        return $food
            ? $this->user()->can('update', $food)
            : $this->user()->can('create', Food::class);
    }

    protected function prepareForValidation(): void
    {
        // Decimal comma, as the rest of the interface shows numbers (7,5).
        $numbers = collect(self::NUMBERS)
            ->filter(fn ($field) => is_string($this->input($field)))
            ->mapWithKeys(fn ($field) => [$field => str_replace(',', '.', $this->input($field))]);

        $this->merge([...$numbers->all(), 'allergens' => $this->input('allergens', [])]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'group' => ['required', Rule::in(Food::groups())],
            'portion' => ['nullable', 'string', 'max:50'],
            'grams' => ['required', 'numeric', 'gt:0', 'max:5000'],
            'kcal' => ['required', 'numeric', 'gt:0', 'max:5000'],
            'protein' => ['required', 'numeric', 'min:0', 'max:1000'],
            'fat' => ['required', 'numeric', 'min:0', 'max:1000'],
            'saturated_fat' => ['nullable', 'numeric', 'min:0', 'lte:fat'],
            'cho' => ['required', 'numeric', 'min:0', 'max:1000'],
            'glycemic_index' => ['nullable', 'integer', 'between:0,150'],
            'allergens' => ['array'],
            'allergens.*' => ['string', Rule::in(array_keys(config('clinical.meal_plan.allergens')))],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'group' => 'grupo',
            'portion' => 'porción',
            'grams' => 'gramos por porción',
            'kcal' => 'energía',
            'protein' => 'proteínas',
            'fat' => 'grasas',
            'saturated_fat' => 'grasa saturada',
            'cho' => 'carbohidratos',
            'glycemic_index' => 'índice glicémico',
            'allergens' => 'alérgenos',
            'allergens.*' => 'alérgeno',
            'price' => 'precio',
        ];
    }

    public function messages(): array
    {
        return [
            'group.in' => __('El grupo debe ser uno de los que usa el generador de menús.'),
            'saturated_fat.lte' => __('La grasa saturada no puede ser mayor que las grasas totales.'),
            'allergens.*.in' => __('El alérgeno no está en el vocabulario de las pautas (meal_plan.allergens).'),
        ];
    }
}
