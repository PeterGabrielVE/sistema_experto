<?php

use App\Jobs\RetrainInferenceModel;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Services\FoodCatalogImporter;
use App\Services\FoodCatalogService;
use App\Services\InferenceEngine;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Output\OutputInterface;

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

    $this->info("Catálogo de alimentos: {$result['created']} nuevos; {$result['existing']} ya estaban en la base y se conservan (los cambios se hacen en Alimentos).");
})->purpose('Initial load of the food catalog (shared/food_catalog.csv): adds the foods the table lacks');

Artisan::command('foods:export {--path= : File to write instead of the standard output}', function (FoodCatalogService $catalog) {
    $csv = $catalog->export();

    if ($path = $this->option('path')) {
        file_put_contents($path, $csv);
        $this->info("Catálogo exportado a {$path}.");

        return;
    }

    $this->getOutput()->write($csv, false, OutputInterface::OUTPUT_RAW);
})->purpose('Write the active foods in the format of shared/food_catalog.csv');

Artisan::command('appointments:send-reminders {--date= : Day of the appointments (Y-m-d, clinic timezone); tomorrow by default}', function (AppointmentService $appointments) {
    $timezone = config('app.clinic_timezone');
    $day = $this->option('date') ?: now($timezone)->addDay()->toDateString();

    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) || ! checkdate((int) substr($day, 5, 2), (int) substr($day, 8, 2), (int) substr($day, 0, 4))) {
        $this->error('Fecha no válida, use AAAA-MM-DD.');

        return 1;
    }

    $result = $appointments->queueReminders($day);

    // Counts only: no patient data in the output.
    $skipped = collect($result['skipped'])
        ->map(fn ($count, $reason) => $count.' '.(Appointment::REMINDER_REASONS[$reason] ?? $reason))
        ->implode(', ');
    $this->info(sprintf(
        'Recordatorios para el %s: %d encolados, %d omitidos%s, %d con error.',
        Carbon::parse($day)->format('d/m/Y'),
        $result['queued'],
        array_sum($result['skipped']),
        $skipped ? " ({$skipped})" : '',
        $result['failed'],
    ));
})->purpose('Queue the reminder e-mails of the appointments of tomorrow (US-6.1)');

// Nightly retraining picks up every doctor-confirmed label of the day.
Schedule::job(new RetrainInferenceModel('nightly'))->dailyAt('03:00');

// Reminders of tomorrow's appointments, at 09:00 clinic time (US-6.1, D-2).
Schedule::command('appointments:send-reminders')
    ->dailyAt('09:00')->timezone(config('app.clinic_timezone'))->withoutOverlapping();
