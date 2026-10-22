<?php

namespace App\Services;

use App\Models\MealPlan;
use App\Models\Patient;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * How the patient follows the meal plan (US-6.2): the menu of each day and its record.
 */
class AdherenceService
{
    /**
     * The menu of a day of the clinic (D-4): the latest saved plan rotates in order, day 1 being
     * the day it was saved, in clinic_timezone. Only the meals with foods. Null without a saved
     * plan, or for a day before it was saved.
     *
     * @return array{meal_plan: MealPlan, date: Carbon, day: int, meals: array<int, array>}|null
     */
    public function menuFor(Patient $patient, CarbonInterface $date): ?array
    {
        $plan = $patient->latestMealPlan();
        $days = $plan?->plan['days'] ?? [];
        if (! $days) {
            return null;
        }

        $timezone = config('app.clinic_timezone');
        $day = Carbon::parse($date->toDateString(), $timezone);
        $saved = $plan->updated_at->copy()->setTimezone($timezone)->startOfDay();
        if ($day->lt($saved)) {
            return null;
        }

        $index = (int) $saved->diffInDays($day) % count($days);

        return [
            'meal_plan' => $plan,
            'date' => $day,
            'day' => $index + 1,
            'meals' => array_values(array_filter($days[$index]['meals'] ?? [], fn (array $meal) => ! empty($meal['items']))),
        ];
    }

    /**
     * Today in the clinic.
     */
    public function today(): Carbon
    {
        return now(config('app.clinic_timezone'))->startOfDay();
    }
}
