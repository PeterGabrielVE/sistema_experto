<?php

use App\Http\Controllers\DiagnosisController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\RulesController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\UserController;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\Recommendation;
use App\Models\Rule;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Each group requires the "viewAny" ability of its model policy (App\Policies).
| Actions on a specific record are authorized in the controller or FormRequest.
*/

Route::get('/', function () {
    return view('auth.login');
});

// Accounts are created by administrators only.
Auth::routes(['register' => false]);

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('downloadManual', [UserController::class, 'downloadManualPdf'])->name('downloadManual');

Route::middleware('auth')->group(function () {
    // Own profile: every authenticated user.
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');

    Route::middleware('can:viewAny,'.User::class)->group(function () {
        Route::resource('user', UserController::class)->except(['show']);
    });

    Route::middleware('can:viewAny,'.Patient::class)->group(function () {
        Route::resource('patient', PatientController::class)->except(['show']);
    });

    Route::middleware('can:viewAny,'.Diagnosis::class)->group(function () {
        Route::get('diagnosis/{patient}', [DiagnosisController::class, 'create'])->whereNumber('patient')->name('diagnosis.new');
        Route::post('diagnosis', [DiagnosisController::class, 'store'])->name('diagnosis.store');
        Route::get('diagnoses/all/{patient}', [DiagnosisController::class, 'history'])->name('diagnosis.all');
        Route::get('result/{diagnosis}', [DiagnosisController::class, 'result'])->name('result');
        Route::put('result/{diagnosis}/rule', [DiagnosisController::class, 'updateRule'])->name('diagnosis.rule');
        Route::get('download/{diagnosis}', [DiagnosisController::class, 'download'])->name('download');

        // Dashboard series (JSON, MonthlyCountResource).
        Route::get('diagnoses/chart', [StatisticsController::class, 'diagnoses'])->name('diagnoses/chart');
        Route::get('patients/chart', [StatisticsController::class, 'patients'])->name('patients/chart');
        Route::get('users/chart', [StatisticsController::class, 'users'])->name('users/chart');
    });

    Route::resource('recommendation', RecommendationController::class)
        ->middleware('can:viewAny,'.Recommendation::class);
    Route::resource('schedule', ScheduleController::class)->except(['show'])
        ->middleware('can:viewAny,'.Schedule::class);
    Route::get('rules', [RulesController::class, 'index'])->name('rules.index')
        ->middleware('can:viewAny,'.Rule::class);

    Route::get('{page}', [PageController::class, 'index'])->name('page.index');
});
