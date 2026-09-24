<?php

namespace App\Listeners;

use App\Events\DiagnosisCategoryConfirmed;
use App\Jobs\RetrainInferenceModel;

/**
 * Every doctor correction is a new training label. Retraining is delayed so
 * corrections made in a short period are learned in a single run.
 */
class ScheduleModelRetraining
{
    public const DELAY_MINUTES = 10;

    public function handle(DiagnosisCategoryConfirmed $event): void
    {
        if (! config('services.inference.url')) {
            return;
        }

        RetrainInferenceModel::dispatch('doctor-confirmation')
            ->delay(now()->addMinutes(self::DELAY_MINUTES));
    }
}
