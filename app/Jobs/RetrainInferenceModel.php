<?php

namespace App\Jobs;

use App\Services\InferenceEngine;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Retrains the Python ML model with synthetic data plus doctor-confirmed diagnoses.
 *
 * Unique: while one is pending, further dispatches are dropped. Combined with the
 * delay used by ScheduleModelRetraining, a burst of corrections triggers one run,
 * which reads every confirmed label from the database when it executes.
 */
class RetrainInferenceModel implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;

    /** Training can take a while; above the HTTP timeout used by InferenceEngine::train(). */
    public int $timeout = 330;

    /** Release the unique lock if a worker dies without finishing. */
    public int $uniqueFor = 3600;

    public function __construct(public string $reason = 'manual')
    {
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(InferenceEngine $engine): void
    {
        if (! config('services.inference.url')) {
            Log::info('Model retraining skipped: INFERENCE_URL is not configured.');

            return;
        }

        $result = $engine->train();

        Log::info('Inference model retrained', ['reason' => $this->reason] + $result);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Inference model retraining failed', [
            'reason' => $this->reason,
            'error' => $exception?->getMessage(),
        ]);
    }
}
