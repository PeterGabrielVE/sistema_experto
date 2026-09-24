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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('downloadManual', [UserController::class, 'downloadManualPdf'])->name('downloadManual');

Route::middleware('auth')->group(function () {
    Route::resource('user', UserController::class)->except(['show']);
    Route::resource('patient', PatientController::class)->except(['show']);
    Route::resource('diagnosis', DiagnosisController::class)->except(['show']);
    Route::resource('recommendation', RecommendationController::class);
    Route::resource('schedule', ScheduleController::class)->except(['show']);
    Route::resource('rules', RulesController::class)->except(['show']);
    Route::get('diagnosis/{id}', [DiagnosisController::class, 'create'])->name('diagnosis.new');
    Route::get('result/{diagnosis}', [DiagnosisController::class, 'result'])->name('result');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('{page}', [PageController::class, 'index'])->name('page.index');

    Route::get('diagnoses/chart', [DiagnosisController::class, 'chart'])->name('diagnoses/chart');
    Route::get('patients/chart', [PatientController::class, 'chart'])->name('patients/chart');
    Route::get('users/chart', [UserController::class, 'chart'])->name('users/chart');
    Route::get('diagnoses/all/{id}', [DiagnosisController::class, 'getAllByPatient'])->name('diagnosis.all');
    Route::get('download/{diagnosis}', [DiagnosisController::class, 'download'])->name('download');
});
