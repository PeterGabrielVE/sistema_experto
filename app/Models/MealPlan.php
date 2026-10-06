<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Menu proposal saved for a consultation (MealPlanService::save).
 */
class MealPlan extends Model
{
    protected $fillable = ['diagnosis_id', 'plan', 'edited', 'created_by'];

    protected function casts(): array
    {
        return [
            'plan' => 'array',
            'edited' => 'boolean',
        ];
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
