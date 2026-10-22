<?php

namespace App\Services;

use App\Events\FoodCatalogChanged;
use App\Models\Food;
use App\Models\User;

/**
 * Changes to the food catalog made in the application (US-4.2). The foods table is the
 * source the meal plan generator receives (MealPlanService), so new proposals use the
 * new values and saved ones keep theirs (meal_plans.plan).
 */
class FoodCatalogService
{
    /** Field of the form and of shared/food_catalog.csv => foods column */
    private const COLUMNS = [
        'name' => 'name',
        'group' => 'item',
        'portion' => 'portion',
        'grams' => 'gr',
        'kcal' => 'kcal',
        'protein' => 'protein',
        'fat' => 'lipid',
        'saturated_fat' => 'saturated_fat',
        'cho' => 'cho',
        'glycemic_index' => 'glycemic_index',
        'allergens' => 'allergens',
        'price' => 'price',
    ];

    /** Legacy NOT NULL columns the form does not ask for, as the importer leaves them. */
    private const LEGACY_ZERO = ['clna_mg', 'k_mg', 'p_mg', 'ca_mg'];

    /**
     * Creates or updates a food with the validated fields of FoodRequest. Nothing is
     * saved or audited when no value changes.
     */
    public function save(Food $food, array $data, User $actor): Food
    {
        $action = $food->exists ? FoodCatalogChanged::UPDATED : FoodCatalogChanged::CREATED;

        $values = [];
        foreach (self::COLUMNS as $field => $column) {
            $values[$field] = match ($field) {
                'allergens' => implode(';', $data['allergens'] ?? []) ?: null,
                'portion' => $data['portion'] ?? '',
                'name', 'group' => $data[$field],
                'glycemic_index' => isset($data[$field]) ? (int) $data[$field] : null,
                default => isset($data[$field]) ? self::number($data[$field]) : null,
            };
        }

        $changes = [];
        foreach (self::COLUMNS as $field => $column) {
            $before = $food->exists ? self::comparable($food->getOriginal($column)) : null;
            $after = self::comparable($values[$field]);
            if ($before !== $after) {
                $changes[$field] = [$before, $after];
            }
        }
        if (! $changes) {
            return $food;
        }

        foreach (self::COLUMNS as $field => $column) {
            $food->{$column} = $values[$field];
        }
        if (! $food->exists || $food->isDirty('item')) {
            // Legacy column the generator does not use: the one of the other foods of the group.
            $food->id_group = Food::where('item', $food->item)->whereKeyNot($food->id)->value('id_group') ?? 0;
        }
        if (! $food->exists) {
            foreach (self::LEGACY_ZERO as $column) {
                $food->{$column} = '0';
            }
        }
        $food->save();

        FoodCatalogChanged::dispatch($food, $actor, $action, $changes);

        return $food;
    }

    /**
     * Stops offering the food to new proposals; it is kept for the saved menus (AC-4).
     */
    public function retire(Food $food, User $actor): Food
    {
        $food->retired_at = now();
        $food->save();

        FoodCatalogChanged::dispatch($food, $actor, FoodCatalogChanged::RETIRED, [
            'retired_at' => [null, $food->retired_at->toIso8601String()],
        ]);

        return $food;
    }

    /**
     * "7.50" and 7.5 are the same value: numbers as their shortest text.
     */
    private static function number(mixed $value): string
    {
        return (string) (float) $value;
    }

    private static function comparable(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = str_replace(',', '.', (string) $value);

        return is_numeric($text) ? self::number($text) : (string) $value;
    }
}
