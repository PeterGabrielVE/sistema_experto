<?php

use App\Http\Controllers\ClinicalMeasurementController;
use App\Http\Controllers\ClinicalRecordController;
use App\Http\Controllers\DiagnosisController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LabResultController;
use App\Http\Controllers\MacroRuleController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\RulesController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\UserController;
use App\Models\Diagnosis;
use App\Models\MacroRule;
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

        // Clinical record (ficha clínica), one per patient.
        Route::get('patient/{patient}/clinical-record', [ClinicalRecordController::class, 'show'])->name('patient.clinical-record.show');
        Route::get('patient/{patient}/clinical-record/edit', [ClinicalRecordController::class, 'edit'])->name('patient.clinical-record.edit');
        Route::put('patient/{patient}/clinical-record', [ClinicalRecordController::class, 'update'])->name('patient.clinical-record.update');

        // Clinical measurements (registro de mediciones), many per patient.
        Route::resource('patient.measurements', ClinicalMeasurementController::class)
            ->except(['show'])
            ->scoped();

        // Lab results (exámenes de laboratorio), many per patient.
        Route::resource('patient.lab-results', LabResultController::class)
            ->parameters(['lab-results' => 'lab_result'])
            ->except(['show'])
            ->scoped();
    });

    Route::middleware('can:viewAny,'.Diagnosis::class)->group(function () {
        Route::get('diagnosis/{patient}', [DiagnosisController::class, 'create'])->whereNumber('patient')->name('diagnosis.new');
        Route::post('diagnosis', [DiagnosisController::class, 'store'])->name('diagnosis.store');
        Route::post('diagnosis/{patient}/macros', [DiagnosisController::class, 'macros'])->whereNumber('patient')->name('diagnosis.macros');
        Route::get('diagnoses/all/{patient}', [DiagnosisController::class, 'history'])->name('diagnosis.all');
        Route::get('result/{diagnosis}', [DiagnosisController::class, 'result'])->name('result');
        Route::put('result/{diagnosis}/rule', [DiagnosisController::class, 'updateRule'])->name('diagnosis.rule');
        Route::get('download/{diagnosis}', [DiagnosisController::class, 'download'])->name('download');
        // Menu proposal of the consultation: editor, save, back to the automatic one.
        Route::get('result/{diagnosis}/menu', [MealPlanController::class, 'edit'])->name('meal-plan.edit');
        Route::put('result/{diagnosis}/menu', [MealPlanController::class, 'update'])->name('meal-plan.update');
        Route::delete('result/{diagnosis}/menu', [MealPlanController::class, 'destroy'])->name('meal-plan.destroy');

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
    Route::resource('macro-rules', MacroRuleController::class)->except(['show'])
        ->middleware('can:viewAny,'.MacroRule::class);

    Route::get('{page}', [PageController::class, 'index'])->name('page.index');
});
