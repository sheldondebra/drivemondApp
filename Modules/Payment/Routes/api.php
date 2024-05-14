<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

Route::middleware(['auth.basic'])->group(function () {

    Route::post('payment', [PaymentController::class, 'create'])->name('create');
    Route::get('success', [PaymentController::class, 'success'])->name('success');
    Route::get('cancel', [PaymentController::class, 'cancel'])->name('cancel');
    Route::get('total/{user_id}', [PaymentController::class, "total_amount"])->name('total');
});


