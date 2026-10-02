<?php

use App\Http\Controllers\Api\DiagnosisController;
use App\Http\Controllers\Api\TokenController;
use App\Models\Diagnosis;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API (prefix /api, JSON responses)
|--------------------------------------------------------------------------
| Authentication: "Authorization: Bearer <token>", token from POST /api/v1/tokens.
*/

Route::prefix('v1')->name('api.')->group(function () {
    Route::post('tokens', [TokenController::class, 'store'])->middleware('throttle:5,1')->name('tokens.store');

    Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
        Route::delete('tokens', [TokenController::class, 'destroy'])->name('tokens.destroy');

        Route::middleware('can:viewAny,'.Diagnosis::class)->group(function () {
            Route::post('diagnoses/evaluate', [DiagnosisController::class, 'evaluate'])->name('diagnoses.evaluate');
            Route::get('diagnoses/{diagnosis}', [DiagnosisController::class, 'show'])->whereNumber('diagnosis')->name('diagnoses.show');
        });
    });
});
