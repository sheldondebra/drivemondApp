<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\PromotionManagement\Http\Controllers\Api\New\Customer\BannerSetupController;
use Modules\PromotionManagement\Http\Controllers\Api\New\Customer\CouponSetupController ;



// Route::group(['prefix' => 'customer'], function (){
//     Route::group(['prefix' => 'banner'], function(){
//         Route::get('list', [BannerSetupController::class, 'list']);
//         Route::post('update-redirection-count', [BannerSetupController::class, 'RedirectionCount']);
//     });
//     Route::group(['prefix' => 'coupon', 'middleware' => ['auth:api', 'maintenance_mode']], function(){
//         Route::get('list', [CouponSetupController::class, 'list']);
//         Route::post('apply', [CouponSetupController::class, 'apply']);
//     });
// });





Route::group(['prefix' => 'customer'], function (){

    Route::group(['prefix' => 'banner'], function(){
        Route::controller(BannerSetupController::class)->group(function () {
            Route::get('list', 'list');
        Route::post('update-redirection-count', 'RedirectionCount');
        });

    });
    Route::group(['prefix' => 'coupon', 'middleware' => ['auth:api', 'maintenance_mode']], function(){
        Route::controller(CouponSetupController::class)->group(function () {
            Route::get('list', 'list');
             Route::post('apply', 'apply');
        });

    });
});
