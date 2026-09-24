<?php

use App\Http\Controllers\DiagnosisController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\RulesController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Permissions are defined in AppServiceProvider (gates) and UserPolicy.
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

    // Account management: administrators.
    Route::middleware('can:viewAny,'.User::class)->group(function () {
        Route::resource('user', UserController::class)->except(['show']);
    });

    // Clinical data, read only: any valid role.
    Route::middleware('can:view-clinical')->group(function () {
        Route::get('patient', [PatientController::class, 'index'])->name('patient.index');
        Route::get('result/{diagnosis}', [DiagnosisController::class, 'result'])->name('result');
        Route::get('download/{diagnosis}', [DiagnosisController::class, 'download'])->name('download');
        Route::get('diagnoses/all/{id}', [DiagnosisController::class, 'getAllByPatient'])->name('diagnosis.all');
        Route::get('diagnoses/chart', [DiagnosisController::class, 'chart'])->name('diagnoses/chart');
        Route::get('patients/chart', [PatientController::class, 'chart'])->name('patients/chart');
        Route::get('users/chart', [UserController::class, 'chart'])->name('users/chart');
    });

    // Patients: doctors.
    Route::middleware('can:manage-patients')->group(function () {
        Route::resource('patient', PatientController::class)->except(['index', 'show']);
    });

    // Diagnoses: doctors.
    Route::middleware('can:manage-diagnoses')->group(function () {
        Route::get('diagnosis/{id}', [DiagnosisController::class, 'create'])->whereNumber('id')->name('diagnosis.new');
        Route::post('diagnosis', [DiagnosisController::class, 'store'])->name('diagnosis.store');
        Route::put('result/{diagnosis}/rule', [DiagnosisController::class, 'updateRule'])->name('diagnosis.rule');
    });

    // Rules, recommendations and schedules: Doctor Jefe.
    Route::middleware('can:manage-clinical-content')->group(function () {
        Route::resource('recommendation', RecommendationController::class);
        Route::resource('schedule', ScheduleController::class)->except(['show']);
        Route::resource('rules', RulesController::class)->except(['show']);
    });

    Route::get('{page}', [PageController::class, 'index'])->name('page.index');
});
