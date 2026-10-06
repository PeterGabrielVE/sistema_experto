<?php

namespace App\Http\Requests;

use App\Models\Diagnosis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The menu the doctor accepts in the editor: foods and portions per meal and day
 * (the amounts are recomputed by MealPlanService::rebuild). The editor sends them as
 * JSON in the "plan" field; "generated" tells whether it was left as generated.
 */
class SaveMealPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Diagnosis::class);
    }

    protected function prepareForValidation(): void
    {
        $plan = json_decode((string) $this->input('plan'), true);
        $this->merge([
            'days' => is_array($plan) ? ($plan['days'] ?? null) : null,
            'generated' => $this->boolean('generated'),
        ]);
    }

    public function rules(): array
    {
        return [
            'days' => ['required', 'array', 'min:1', 'max:'.config('clinical.meal_plan.max_days')],
            'days.*.meals' => ['required', 'array'],
            'days.*.meals.*.key' => ['required', Rule::in(array_column(config('clinical.meal_plan.meals'), 'key'))],
            'days.*.meals.*.items' => ['present', 'array'],
            'days.*.meals.*.items.*.food_id' => ['required', 'integer', 'exists:foods,id'],
            'days.*.meals.*.items.*.portions' => ['required', 'numeric', 'between:0.5,10', 'multiple_of:0.5'],
            'generated' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'days' => 'menú',
            'days.*.meals.*.items.*.food_id' => 'alimento',
            'days.*.meals.*.items.*.portions' => 'porciones',
        ];
    }
}
