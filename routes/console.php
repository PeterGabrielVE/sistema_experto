<?php

use App\Jobs\RetrainInferenceModel;
use App\Services\InferenceEngine;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('inference:train {--queue : Dispatch the RetrainInferenceModel job instead of waiting}', function (InferenceEngine $engine) {
    if ($this->option('queue')) {
        RetrainInferenceModel::dispatch('artisan');
        $this->info('Reentrenamiento encolado (si ya había uno pendiente, se usará ese).');

        return;
    }

    $this->info('Reentrenando el modelo con datos sintéticos y diagnósticos confirmados...');
    $result = $engine->train();

    $this->table(['Versión', 'Sintéticos', 'Confirmados', 'Accuracy'], [[
        $result['version'], $result['samples_synthetic'], $result['samples_manual'], $result['accuracy'],
    ]]);
})->purpose('Retrain the Python ML inference model');

// Nightly retraining picks up every doctor-confirmed label of the day.
Schedule::job(new RetrainInferenceModel('nightly'))->dailyAt('03:00');
