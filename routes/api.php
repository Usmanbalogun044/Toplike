<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\BankController;
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

Route::middleware(['auth:api', 'user.active'])->group(function () {
    Route::get('wallet', [WalletController::class, 'show']);
    Route::get('wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('wallet/virtual-account/provision', [WalletController::class, 'provisionVirtualAccount']);
    Route::get('wallet/withdrawals', [\App\Http\Controllers\Api\WithdrawalController::class, 'index']);
    Route::post('wallet/withdrawals', [\App\Http\Controllers\Api\WithdrawalController::class, 'store']);
    // Save/verify bank account
    Route::post('banks/save', [BankController::class, 'save']);
    // Bank account management routes removed (using user's controller elsewhere)
    
    // Posts
    Route::post('posts', [PostController::class, 'store']);
    Route::get('posts/{post}', [PostController::class, 'show']);
    
    // Likes (with rate limiting middleware applied at route level)
    Route::post('posts/{post}/like', [LikeController::class, 'like'])
        ->middleware('throttle:30,1');
    Route::delete('posts/{post}/like', [LikeController::class, 'unlike'])
        ->middleware('throttle:60,1');

    // Join active challenge (wallet first, fallback to Paystack)
    Route::post('challenge/join', [ChallengeController::class, 'join']);
    // Callback after payment (requires auth to attach to correct user)
    Route::get('challenge/join/callback', [ChallengeController::class, 'joinCallback']);
});

// Public challenge and leaderboard endpoints
Route::get('challenge/current', [ChallengeController::class, 'current']);
Route::get('challenges', [ChallengeController::class, 'index']);
Route::get('challenges/{challenge}/leaderboard', [ChallengeController::class, 'leaderboard']);
// Public webhook for Paystack server callbacks
Route::post('challenge/join/webhook', [ChallengeController::class, 'webhook']);
Route::post('paystack/webhook', [PaymentWebhookController::class, 'handle']);

// Banks and account resolution
Route::get('banks', [BankController::class, 'list']);
Route::post('banks/resolve', [BankController::class, 'resolve']);
