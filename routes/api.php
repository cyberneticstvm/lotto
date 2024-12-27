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
    Route::post('/plays', 'getPlays')->name('get.plays');
    Route::post('/edit/plays', 'getPlaysForEdit')->name('get.plays.for.edit');
    Route::post('/play', 'getPlay')->name('get.play');
    Route::post('/play/code', 'getPlayByCode')->name('get.play.by.code');
    Route::post('/update/play', 'updatePlay')->name('play.update');
    Route::post('/ticket', 'getTicket')->name('get.ticket');
    Route::post('/ordercount', 'getOrderCount')->name('get.order.count');
});
