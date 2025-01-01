<?php

use App\Http\Controllers\APIController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('user')->controller(APIController::class)->group(function () {
    Route::post('/auth', 'getAuthUser')->name('get.auth.user');
    Route::post('/all', 'getAllUsers')->name('get.all.users');
    Route::post('/save', 'saveUser')->name('user.save');
    Route::post('/delete', 'deleteUser')->name('user.delete');
    Route::post('/nextplay', 'getNextPlay')->name('get.next.play');
    Route::post('/current/users', 'getCurrentUsers')->name('get.current.users');
    Route::post('/plays', 'getPlays')->name('get.plays');
    Route::post('/edit/plays', 'getPlaysForEdit')->name('get.plays.for.edit');
    Route::post('/play', 'getPlay')->name('get.play');
    Route::post('/play/code', 'getPlayByCode')->name('get.play.by.code');
    Route::post('/update/play', 'updatePlay')->name('play.update');
    Route::post('/ticket', 'getTicket')->name('get.ticket');
    Route::post('/ordercount', 'getOrderCount')->name('get.order.count');
    Route::post('/blockednumbercount', 'getBlockedNumberCount')->name('get.blocked.number.count');
    Route::post('/save/order', 'saveOrder')->name('order.save');
    Route::post('/save/blockednumber', 'saveBlockedNumber')->name('blocked.number.save');
    Route::post('/get/blockednumbers', 'getBlockedNumber')->name('blocked.number.get');
    Route::post('/delete/blockednumber', 'deleteBlockedNumber')->name('blocked.number.delete');
    Route::post('/save/result', 'saveResult')->name('result.save');
    Route::post('/ticket/all', 'getAllTickets')->name('ticket.all');
    Route::post('/ticket/edit', 'getTicketForEdit')->name('ticket.edit');
    Route::post('/ticket/update', 'updateTicket')->name('ticket.update');
    Route::post('/scheme/all', 'getAllSchemes')->name('scheme.all');
    Route::post('/scheme/edit', 'getSchemeForEdit')->name('scheme.edit');
    Route::post('/scheme/update', 'updateScheme')->name('scheme.update');
});

Route::prefix('report')->controller(APIController::class)->group(function () {
    Route::post('/get/play', 'getPlaysForReport')->name('get.play.for.report');
    Route::post('/get/ticket', 'getTicketsForReport')->name('get.ticket.for.report');
    Route::post('/get/user', 'getUsersForReport')->name('get.user.for.report');


    Route::post('/result', 'getResult')->name('get.result');
    Route::post('/numberwise', 'getNumberWiseReport')->name('get.number.wise');
    Route::post('/sales', 'getSalesReport')->name('get.sales.report');
    Route::post('/sales/user', 'getSalesReportByUser')->name('get.sales.report.user');
    Route::post('/sales/bill', 'getSalesReportByBill')->name('get.sales.report.bill');
    Route::post('/sales/bill/all', 'getSalesReportByBillAll')->name('get.sales.report.bill.all');
});
