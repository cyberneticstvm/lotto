<?php

use Illuminate\Support\Facades\Route;

Route::prefix('')->get('/', function () {
    return view('welcome');
})->name('index');
