<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->header('authorization', '1a2b3c4d5e6f7g8h9i');
