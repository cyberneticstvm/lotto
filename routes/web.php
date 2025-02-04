<?php

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::prefix('')->get('/', function () {
        return view('login');
    })->name('login');

    Route::controller(WebController::class)->group(function () {
        Route::post('/', 'authenticate')->name('login.auth');
    });
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::controller(WebController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
        Route::post('/dashboard', 'save')->name('result.save');

        Route::get('/result/edit/{id}', 'edit')->name('result.edit');
        Route::post('/result/update/{id}', 'update')->name('result.update');
        Route::get('/logout', 'logout')->name('logout');
    });
});
