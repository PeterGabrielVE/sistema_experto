<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $table = 'foods';

    protected $fillable = [
        'id_group', 'name', 'item', 'portion', 'gr', 'kcal', 'protein', 'lipid', 'saturated_fat', 'cho', 'glycemic_index', 'allergens', 'price', 'clna_mg', 'k_mg', 'p_mg', 'ca_mg',
    ];

    protected function casts(): array
    {
        return [
            'retired_at' => 'datetime',
        ];
    }

    /**
     * Foods offered to the meal plan generator and the editor (not retired).
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('retired_at');
    }

    /**
     * Exchange groups the meal plan generator uses (meal_plan in shared/clinical_thresholds.json):
     * a food of another group is never chosen.
     *
     * @return array<int, string>
     */
    public static function groups(): array
    {
        $plan = config('clinical.meal_plan');

        return collect(array_keys($plan['daily_portions']))
            ->merge(collect($plan['meals'])->pluck('items')->flatten())
            ->unique()->sort()->values()->all();
    }

    // kcal per gram of carbohydrates, proteins and fats (general Atwater factors).
    private const ATWATER = ['cho' => 4, 'protein' => 4, 'lipid' => 9];

    /**
     * Data to review (US-4.2/AC-1), as the expert service reports them (expert/app/catalog.py),
     * with the thresholds of meal_plan.catalog_review: key => message. They never stop the food
     * from being saved or used.
     *
     * @return array<string, string>
     */
    public function reviewFlags(): array
    {
        $review = config('clinical.meal_plan.catalog_review');
        $flags = [];

        $kcal = $this->number('kcal');
        $computed = array_sum(array_map(fn ($field, $factor) => $this->number($field) * $factor, array_keys(self::ATWATER), self::ATWATER));
        if ($kcal > 0 && abs($computed - $kcal) > $kcal * $review['energy_tolerance_percent'] / 100) {
            $flags['energy'] = __('Energía: :declared kcal declaradas, :computed calculadas con 4/4/9 kcal/g (tolerancia :tolerance %).', [
                'declared' => self::format($kcal),
                'computed' => self::format($computed),
                'tolerance' => self::format($review['energy_tolerance_percent']),
            ]);
        }

        $cho = $this->number('cho');
        if ($this->glycemic_index === null && $cho >= $review['glycemic_index_min_cho']) {
            $flags['glycemic_index'] = __('Sin índice glicémico, con :cho g de carbohidratos por porción.', ['cho' => self::format($cho)]);
        }

        return $flags;
    }

    /**
     * Legacy columns are text, some with a decimal comma ("7,5").
     */
    private function number(string $field): float
    {
        return (float) str_replace(',', '.', (string) $this->getAttribute($field));
    }

    private static function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, ',', ''), '0'), ',');
    }
}
