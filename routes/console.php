<?php

use App\Services\InferenceEngine;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('inference:train', function (InferenceEngine $engine) {
    $this->info('Reentrenando el modelo con datos sintéticos y diagnósticos confirmados...');
    $result = $engine->train();

    $this->table(['Versión', 'Sintéticos', 'Confirmados', 'Accuracy'], [[
        $result['version'], $result['samples_synthetic'], $result['samples_manual'], $result['accuracy'],
    ]]);
})->purpose('Retrain the Python ML inference model');
