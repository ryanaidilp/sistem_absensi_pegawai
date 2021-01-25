<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AttendeController;
use App\Http\Controllers\Api\OutstationController;
use App\Http\Controllers\Api\AbsentPermissionController;
use App\Http\Controllers\Api\PaidLeaveController;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('login', [UserController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('logout', [UserController::class, 'logout']);
    Route::get('my', [UserController::class, 'show']);
    Route::post('change_password', [UserController::class, 'update_password']);
    Route::get('user', [UserController::class, 'index']);
    Route::post('presence', [AttendeController::class, 'presence']);
    Route::post('presence/cancel', [AttendeController::class, 'cancel']);
    Route::get('presence', [AttendeController::class, 'index']);

    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/', [UserController::class, 'notifications']);
        Route::post('/', [UserController::class, 'readNotification']);
        Route::get('/read', [UserController::class, 'readAllNotifications']);
        Route::get('/delete', [UserController::class, 'deleteAllNotifications']);
        Route::post('/send', [UserController::class, 'send']);
    });

    Route::get('statistics', [UserController::class, 'myStatistic']);

    Route::group(['prefix' => 'permission'], function () {
        Route::post('/', [AbsentPermissionController::class, 'store']);
        Route::get('/', [AbsentPermissionController::class, 'index']);
        Route::get('/all', [AbsentPermissionController::class, 'all']);
        Route::post('/approve',  [AbsentPermissionController::class, 'approve']);
        Route::post('/picture',  [AbsentPermissionController::class, 'updatePicture']);
    });


    Route::group(['prefix' => 'outstation'], function () {
        Route::post('/', [OutstationController::class, 'store']);
        Route::get('/', [OutstationController::class, 'index']);
        Route::get('/all', [OutstationController::class, 'all']);
        Route::post('/approve', [OutstationController::class, 'approve']);
        Route::post('/picture',  [OutstationController::class, 'updatePicture']);
    });

    Route::group(['prefix' => 'paid-leave'], function () {
        Route::post('/', [PaidLeaveController::class, 'store']);
        Route::get('/', [PaidLeaveController::class, 'index']);
        Route::get('/all', [PaidLeaveController::class, 'all']);
        Route::post('/approve', [PaidLeaveController::class, 'approve']);
        Route::post('/picture',  [PaidLeaveController::class, 'updatePicture']);
    });
});
