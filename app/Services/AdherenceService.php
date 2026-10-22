<?php

namespace App\Services;

use App\Models\AdherenceCheckin;
use App\Models\MealPlan;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

    /**
     * The days the patient can still record (D-3): today and yesterday, in the clinic.
     */
    public function isRegistrable(CarbonInterface $date): bool
    {
        $today = $this->today();

        return in_array($date->toDateString(), [$today->toDateString(), $today->copy()->subDay()->toDateString()], true);
    }

    /**
     * What the patient marked on a day: meal_key => status.
     *
     * @return array<string, string>
     */
    public function marksFor(Patient $patient, CarbonInterface $date): array
    {
        return $patient->adherenceCheckins()->whereDate('date', $date->toDateString())->pluck('status', 'meal_key')->all();
    }

    /**
     * Saves the marks of a day of the menu: a status creates or replaces the meal's record, an
     * empty one deletes it ("sin marcar"). The meals were checked against the menu (AdherenceRequest).
     *
     * @param  array{meal_plan: MealPlan, date: Carbon, day: int, meals: array<int, array>}  $menu
     * @param  array<string, string|null>  $marks  meal_key => done | partial | missed | empty
     */
    public function record(Patient $patient, User $account, array $menu, array $marks): void
    {
        $labels = array_column($menu['meals'], 'label', 'key');
        $date = $menu['date']->toDateString();

        DB::transaction(function () use ($patient, $account, $menu, $marks, $labels, $date) {
            foreach ($marks as $key => $status) {
                // whereDate: the date column is stored with its time in SQLite.
                $existing = $patient->adherenceCheckins()->whereDate('date', $date)->where('meal_key', $key);

                if (! $status) {
                    $existing->delete();

                    continue;
                }

                ($existing->first() ?? new AdherenceCheckin(['patient_id' => $patient->id, 'date' => $date, 'meal_key' => $key]))
                    ->fill([
                        'meal_plan_id' => $menu['meal_plan']->id,
                        'plan_day' => $menu['day'],
                        'meal_label' => $labels[$key],
                        'status' => $status,
                        'recorded_by' => $account->id,
                    ])->save();
            }
        });
    }
}
