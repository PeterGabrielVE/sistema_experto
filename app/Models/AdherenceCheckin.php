<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A meal of the menu of a day marked by the patient in the portal (US-6.2).
 *
 * @property int $patient_id
 * @property int|null $meal_plan_id
 * @property Carbon $date
 * @property int $plan_day
 * @property string $meal_key
 * @property string $meal_label
 * @property string $status
 * @property int|null $recorded_by
 */
class AdherenceCheckin extends Model
{
    public const DONE = 'done';

    public const PARTIAL = 'partial';

    public const MISSED = 'missed';

    public const STATUSES = [self::DONE, self::PARTIAL, self::MISSED];

    protected $fillable = ['patient_id', 'meal_plan_id', 'date', 'plan_day', 'meal_key', 'meal_label', 'status', 'recorded_by'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'plan_day' => 'integer',
        ];
    }

    /**
     * Score of the mark (adherence.scores of shared/clinical_thresholds.json, D-5).
     */
    public function score(): float
    {
        return (float) config('clinical.adherence.scores')[$this->status];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }
}
