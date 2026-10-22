<?php

namespace App\Http\Requests;

use App\Models\AdherenceCheckin;
use App\Models\MealPlan;
use App\Services\AdherenceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * The marks of one day of the menu (US-6.2). Only today and yesterday (D-3), only the meals of that
 * day's menu, and always for the patient of the session.
 */
class AdherenceRequest extends FormRequest
{
    private ?array $menu = null;

    public function authorize(): bool
    {
        return (bool) $this->user()?->isPatient();
    }

    protected function prepareForValidation(): void
    {
        $date = Carbon::createFromFormat('!Y-m-d', $this->route('date'), config('app.clinic_timezone'));
        $service = app(AdherenceService::class);

        // A crafted request: the page only offers today and yesterday, with a plan.
        abort_unless($date && $date->toDateString() === $this->route('date') && $service->isRegistrable($date), 422, __('Solo puede registrar hoy o ayer.'));
        $this->menu = $service->menuFor($this->user()->patient, $date);
        abort_unless($this->menu, 422, __('Aún no tiene un plan alimentario para ese día.'));
    }

    public function rules(): array
    {
        $keys = array_column($this->menu['meals'], 'key');

        return [
            'meals' => ['required', 'array', 'min:1', function ($attribute, $value, $fail) use ($keys) {
                if (array_diff(array_keys($value), $keys)) {
                    $fail(__('Hay comidas que no están en el menú de ese día.'));
                }
            }],
            'meals.*' => ['nullable', Rule::in(AdherenceCheckin::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'meals.required' => __('Marque al menos una comida.'),
            'meals.*.in' => __('Elija cumplí, en parte, no cumplí o sin marcar.'),
        ];
    }

    /**
     * @return array{meal_plan: MealPlan, date: Carbon, day: int, meals: array<int, array>}
     */
    public function menu(): array
    {
        return $this->menu;
    }
}
