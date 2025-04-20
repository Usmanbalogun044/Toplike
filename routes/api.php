<?php

use App\Http\Controllers\Authcontroller;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\postController;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\challengeController;
use App\Http\Controllers\paymentController;
use App\Http\Controllers\WalletController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/tunnel', function (Request $request) {
    return response()->json(['message' => 'Tunnel is working']);
});
Route::controller(Authcontroller::class)->group(function(){
    Route::post('/signup','register');
    Route::post('/signin','login');
    Route::post('/logout','logout')->middleware('auth:sanctum');
});
Route::post('/verify/{id}/{token}', function (Request $request, $id, $token) {
    $user = User::findOrFail($id);
    // Verify the token
    if ($user->email_verification_token == $token) {
        $user->update(['email_verified_at' => now(), 'email_verification_token' => null]);
        // dd($user);
        return response()->json(['message' => 'Email verified successfully'], 200);
    } else {
        return response()->json(['message' => 'Invalid verification token'], 400);
    }
})->name('api.verification.verify');

// Resend verification email
Route::post('/email/resend', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified.']);
    }
    $request->user()->sendEmailVerificationNotification();
    return response()->json(['message' => 'Verification link sent.']);
})->middleware(['auth:sanctum'])->name('verification.send');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/user/profile', function (Request $request) {
        return $request->user();
    });
});

Route::middleware(['api','auth.user','verified'])->group(function () {
    Route::post('/user/update', 'App\Http\Controllers\userController@updateProfile');
    Route::get('/myprofile', 'App\Http\Controllers\userController@me');
    Route::get('/user/profile/{id}', 'App\Http\Controllers\userController@otheruserprofile');

    Route::controller(postController::class)->group(function(){
        Route::get('/post/all', 'getPosts');
        Route::post('/post/create', 'createPost');
        Route::get('/post/{id}', 'getPost');
        Route::get('/has-user-post','checkifuserhasposted');
        Route::get('/post/user/{id}', 'getUserPosts');
    });
    Route::controller(LikeController::class)->group(function(){
        Route::post('/like-post/{postId}','likePost');
        Route::get('/like/list-user/{postId}', 'userthatlikepost');
        // Route::post('/unlike-post/{postId}','unlikePost');
        // Route::get('/likes/user/{userId}', 'getUserLikes');
        // Route::get('/likes/all', 'getAllLikes');
    });

    Route::controller(challengeController::class)->group(function(){
        Route::post('/join/challenge', 'joinChallenge');
        Route::get('/paystack/callback', 'callback')->name('payment.callback');
    });
  Route::controller(WalletController::class)->group(function(){
        Route::get('/wallet', 'wallet');
        Route::get('/wallet/transactions', 'walletTransactions');
        // Route::post('/wallet/withdraw', 'withdrawFunds');
    });

    // Route::controller('App\Http\Controllers\NotificationController')->group(function(){
    //     Route::get('/notifications', 'getNotifications');
    //     Route::post('/notifications/mark-as-read', 'markAsRead');
    // });
   
});