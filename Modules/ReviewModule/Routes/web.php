<?php

use Illuminate\Support\Facades\Route;
use Modules\ReviewModule\Http\Controllers\Web\Admin\ReviewController;

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

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => 'admin'], function () {

    Route::group(['prefix' => 'driver', 'as' => 'driver.'], function () {
        Route::group(['prefix' => 'review', 'as' => 'review.'], function () {
            Route::controller(\Modules\ReviewModule\Http\Controllers\Web\New\Admin\ReviewController::class)->group(function () {
                Route::get('review-export/{id}/{reviewed}', 'driverReviewExport')->name('export');
            });
        });
    });

    Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {
        Route::group(['prefix' => 'review', 'as' => 'review.'], function () {
            Route::controller(\Modules\ReviewModule\Http\Controllers\Web\New\Admin\ReviewController::class)->group(function () {
                Route::get('review-export/{id}/{reviewed}', 'customerReviewExport')->name('export');
            });
        });
    });

});
