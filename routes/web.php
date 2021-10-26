<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::group(['middleware' => 'auth'], function () {
	Route::resource('user', 'UserController', ['except' => ['show']]);
	Route::resource('patient', 'PatientController', ['except' => ['show']]);
	Route::resource('diagnosis', 'DiagnosisController', ['except' => ['show']]);
    Route::resource('recommendation', 'RecommendationController', ['except' => ['show']]);
    Route::resource('schedule', 'ScheduleController', ['except' => ['show']]);
	Route::get('diagnosis/{id}','DiagnosisController@create')->name('diagnosis.new');
	Route::get('diagnosis/{patient}','DiagnosisController@index')->name('diagnosis.index');
	Route::get('profile', ['as' => 'profile.edit', 'uses' => 'ProfileController@edit']);
	Route::put('profile', ['as' => 'profile.update', 'uses' => 'ProfileController@update']);
	Route::put('profile/password', ['as' => 'profile.password', 'uses' => 'ProfileController@password']);
	Route::get('{page}', ['as' => 'page.index', 'uses' => 'PageController@index']);
});

