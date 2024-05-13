<?php

use App\WebSockets\Handler\UserLocationSocketHandler;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use Illuminate\Support\Facades\Route;
use Modules\AuthManagement\Http\Controllers\Api\AuthController;
use Modules\BusinessManagement\Http\Controllers\Api\Customer\ConfigController;
use Modules\BusinessManagement\Http\Controllers\Api\Driver\ConfigController as DriverConfigController;
use Modules\UserManagement\Http\Controllers\Api\AppNotificationController;
use Modules\UserManagement\Http\Controllers\Api\Customer\AddressController;
use Modules\UserManagement\Http\Controllers\Api\Customer\CustomerController;
use Modules\UserManagement\Http\Controllers\Api\Customer\LoyaltyPointController;
use Modules\UserManagement\Http\Controllers\Api\Driver\ActivityController;
use Modules\UserManagement\Http\Controllers\Api\Driver\LoyaltyPointController as DriverPointsController;
use Modules\UserManagement\Http\Controllers\Api\Driver\DriverController;
use Modules\UserManagement\Http\Controllers\Api\Driver\TimeTrackController;
use Modules\UserManagement\Http\Controllers\Api\Driver\WithdrawController;
use Modules\UserManagement\Http\Controllers\Api\UserController;


Route::group(['prefix' => 'customer'], function () {

    Route::group(['prefix' => 'config'], function () {
        Route::get('get-zone-id', [ConfigController::class, 'getZone']);
        Route::get('place-api-autocomplete', [ConfigController::class, 'placeApiAutocomplete']);
        Route::get('distance-api', [ConfigController::class, 'distanceApi']);
        Route::get('place-api-details', [ConfigController::class, 'placeApiDetails']);
        Route::get('geocode-api', [ConfigController::class, 'geocodeApi']);
        Route::post('get-routes', [ConfigController::class, 'getRoutes']);
    });

    Route::group(['middleware' => ['auth:api', 'maintenance_mode']], function () {
        Route::group(['prefix' => 'loyalty-points'], function () {
            Route::get('list', [LoyaltyPointController::class, 'index']);
            Route::post('convert', [LoyaltyPointController::class, 'convert']);
        });
        Route::get('info', [CustomerController::class, 'profileInfo']);
        Route::group(['prefix' => 'update'], function () {
            Route::put('fcm-token', [AuthController::class, 'updateFcmToken']); //for customer and driver use AuthController
            Route::put('profile', [CustomerController::class, 'updateProfile']);
        });
        Route::get('notification-list', [AppNotificationController::class, 'index']);

        Route::group(['prefix' => 'address'], function () {
            Route::get('all-address', [AddressController::class, 'getAddresses']);
            Route::post('add', [AddressController::class, 'store']);
            Route::get('edit/{id}', [AddressController::class, 'edit']);
            Route::put('update', [AddressController::class, 'update']);
            Route::delete('delete', [AddressController::class, 'destroy']);

        });
    });

});

Route::group(['prefix' => 'driver'], function () {
    Route::group(['prefix' => 'config'], function () {
        // These config will found in Customer Config
        Route::get('get-zone-id', [ConfigController::class, 'getZone']);
        Route::get('place-api-autocomplete', [ConfigController::class, 'placeApiAutocomplete']);
        Route::get('distance-api', [ConfigController::class, 'distanceApi']);
        Route::get('place-api-details', [ConfigController::class, 'placeApiDetails']);
        Route::get('geocode-api', [ConfigController::class, 'geocodeApi']);
    });

    Route::group(['middleware' => ['auth:api', 'maintenance_mode']], function () {
        Route::post('get-routes', [DriverConfigController::class, 'getRoutes']);

        Route::get('time-tracking', [TimeTrackController::class, 'store']);
        Route::post('update-online-status', [TimeTrackController::class, 'onlineStatus']);

        Route::get('info', [DriverController::class, 'profileInfo']);
        Route::group(['prefix' => 'update'], function () {
            Route::put('profile', [DriverController::class, 'updateProfile']);
            Route::put('fcm-token', [AuthController::class, 'updateFcmToken']); //for customer and driver use AuthController
        });

//        Route::post('update-online-status',[DriverController::class, 'onlineStatus']);
        Route::get('my-activity', [DriverController::class, 'myActivity']);
        Route::get('notification-list', [AppNotificationController::class, 'index']);

        Route::group(['prefix' => 'activity'], function () {
            Route::get('leaderboard', [ActivityController::class, 'leaderboard']);
            Route::get('daily-income', [ActivityController::class, 'dailyIncome']);

        });
        Route::group(['prefix' => 'loyalty-points'], function () {
            Route::get('list', [DriverPointsController::class, 'index']);
            Route::post('convert', [DriverPointsController::class, 'convert']);
        });

        Route::group(['prefix' => 'withdraw'], function () {
            Route::get('methods', [WithdrawController::class, 'methods']);
            Route::post('request', [WithdrawController::class, 'create']);
        });
    });

});

#new route
//Route::group(['prefix' => 'customer'], function () {
//
//    Route::group(['middleware' => ['auth:api', 'maintenance_mode']], function () {
//        Route::group(['prefix' => 'loyalty-points'], function () {
//            Route::get('list', [LoyaltyPointController::class, 'index']);
//            Route::post('convert', [LoyaltyPointController::class, 'convert']);
//        });
//        Route::controller(\Modules\UserManagement\Http\Controllers\Api\New\Customer\CustomerController::class)->group(function () {
//            Route::get('info', 'profileInfo');
//            Route::put('update/profile', 'updateProfile');
//
//        });
//        Route::group(['prefix' => 'update'], function () {
//            Route::put('fcm-token', [AuthController::class, 'updateFcmToken']); //for customer and driver use AuthController
//        });
//        Route::controller(\Modules\UserManagement\Http\Controllers\Api\New\AppNotificationController::class)->group(function () {
//            Route::get('notification-list', 'index');
//        });
//
//        Route::group(['prefix' => 'address'], function () {
//            Route::controller(\Modules\UserManagement\Http\Controllers\Api\New\Customer\AddressController::class)->group(function (){
//                Route::get('all-address', 'getAddresses');
//                Route::post('add', 'store');
//                Route::get('edit/{id}', 'edit');
//                Route::put('update', 'update');
//                Route::delete('delete', 'destroy');
//            });
//        });
//    });
//
//});

//Route::group(['prefix' => 'driver'], function () {
//    Route::group(['middleware' => ['auth:api', 'maintenance_mode']], function () {
//        Route::controller(\Modules\UserManagement\Http\Controllers\Api\New\Driver\DriverController::class)->group(function (){
//            Route::put('update/profile', 'updateProfile');
//            Route::get('info', 'profileInfo');
//            Route::get('my-activity', 'myActivity');
//        });
//        Route::controller(\Modules\UserManagement\Http\Controllers\Api\New\Driver\TimeTrackController::class)->group(function (){
//            Route::get('time-tracking', 'store');
//            Route::post('update-online-status', 'onlineStatus');
//        });
//
//        Route::group(['prefix' => 'update'], function () {
//            Route::put('fcm-token', [AuthController::class, 'updateFcmToken']); //for customer and driver use AuthController
//        });
//
////        Route::post('update-online-status',[DriverController::class, 'onlineStatus']);
//        Route::controller(\Modules\UserManagement\Http\Controllers\Api\New\AppNotificationController::class)->group(function () {
//            Route::get('notification-list', 'index');
//        });
//        Route::group(['prefix' => 'activity'], function () {
//            Route::controller(\Modules\UserManagement\Http\Controllers\Api\New\Driver\ActivityController::class)->group(function (){
//                Route::get('leaderboard', 'leaderboard');
//                Route::get('daily-income', 'dailyIncome');
//            });
//        });
//        Route::group(['prefix' => 'loyalty-points'], function () {
//            Route::controller(\Modules\UserManagement\Http\Controllers\Api\New\Driver\LoyaltyPointController::class)->group(function (){
//                Route::get('list', 'index');
//                Route::post('convert', 'convert');
//            });
//        });
//
//        Route::group(['prefix' => 'withdraw'], function () {
//            Route::controller(\Modules\UserManagement\Http\Controllers\Api\New\Driver\WithdrawController::class)->group(function (){
//                Route::get('methods', 'methods');
//                Route::post('request', 'create');
//            });
//        });
//    });
//
//});

Route::post('/user/store-live-location', [UserController::class, 'storeLastLocation']);
Route::post('/user/get-live-location', [UserController::class, 'getLastLocation']);
WebSocketsRouter::webSocket('/user/live-location', UserLocationSocketHandler::class);

