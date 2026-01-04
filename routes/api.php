<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {
    // Rate limit: 3 requests per 1 hour to prevent bot spam
    Route::post('register', [AuthController::class, 'register'])
        ->middleware(['throttle:3,60', 'check.bot']);
    
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:5,1');
    
    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        // Routes requiring active account status
        Route::group(['middleware' => 'user.active'], function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('profile/update', [UserProfileController::class, 'update']);
        });
    });
});
