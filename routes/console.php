<?php

use App\Jobs\RetrainInferenceModel;
use App\Services\FoodCatalogImporter;
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

Artisan::command('foods:import {--path= : CSV to load instead of shared/food_catalog.csv}', function (FoodCatalogImporter $importer) {
    try {
        $result = $importer->import($this->option('path') ?: null);
    } catch (RuntimeException $e) {
        $this->error($e->getMessage());

        return 1;
    }

    $this->info("Catálogo de alimentos: {$result['created']} nuevos, {$result['updated']} actualizados, {$result['unchanged']} sin cambios.");
    if ($result['absent']->isNotEmpty()) {
        $this->warn('En la base pero no en el catálogo (se conservan, los menús guardados los usan): '
            .$result['absent']->map(fn ($food) => "{$food->id} {$food->name}")->implode(', ').'.');
    }
})->purpose('Load the food composition catalog (shared/food_catalog.csv) into the foods table');

// Nightly retraining picks up every doctor-confirmed label of the day.
Schedule::job(new RetrainInferenceModel('nightly'))->dailyAt('03:00');
