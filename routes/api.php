<?php

use App\Http\Controllers\APIController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('user')->controller(APIController::class)->group(function () {
    Route::post('/auth', 'getAuthUser')->name('get.auth.user');
    Route::post('/nextplay', 'getNextPlay')->name('get.next.play');
    Route::post('/current/users', 'getCurrentUsers')->name('get.current.users');
});
