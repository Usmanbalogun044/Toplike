<?php

use App\Http\Controllers\Authcontroller;
use App\Http\Controllers\bankaccountController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\postController;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\challengeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\paymentController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\weelychallengeleaderboardController;
use App\Http\Controllers\withdrawController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Http\Controllers\userController;
use App\Http\Controllers\BankController;


Route::controller(Authcontroller::class)->group(function(){
    Route::post('/signup','register');
    Route::post('/signin','login');
    Route::post('/logout','logout')->middleware('auth:sanctum');
});


//resend verification

Route::post('/resend-verification', function (Request $request) {
    $user = User::where('email', $request->email)->first();
    if ($user) {
        $token = Str::random(40);
        $user->update(['email_verification_token' => $token]);
        // Send verification email
        $apiUrl = route('api.verification.verify', ['id' => $user->id, 'token' => $token]);
        // Send email logic here
        return response()->json(['message' => 'Verification email resent successfully'], 200);
    } else {
        return response()->json(['message' => 'User not found'], 404);
    }
})->name('api.resend-verification');


Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/user/profile', function (Request $request) {
        return $request->user();
    });
});
   Route::get('/bankaccount/list', function (Request $request) {
    //paystack api to list bank
    $paystack = new \Yabacon\Paystack(env('PAYSTACK_SECRET_KEY'));
    $response = $paystack->bank->list();
    return response()->json([
        'message' => 'Banks retrieved successfully',
        'banks' => $response,
    ])->setStatusCode(200, 'Banks retrieved successfully');
});
Route::middleware(['api','auth.user','verified'])->group(function () {
    Route::post('/user',[userController::class, 'updateProfile']);
    Route::get('/myprofile',[userController::class, 'me']);
    Route::get('/user/profile/{id}', [userController::class,'otheruserprofile']);

    Route::controller(postController::class)->group(function(){
        Route::get('/post/all', 'getPosts');
        Route::post('/post/create', 'createPost');
        Route::get('/post/{id}', 'getPost');
        Route::get('/has-user-post','checkifuserhasposted');
        Route::get('/post/user/{id}', 'getUserPosts');
        // RESTful aliases
        Route::get('/posts', 'getPosts');
        Route::post('/posts', 'createPost');
        Route::get('/posts/{id}', 'getPost');
        Route::get('/users/{id}/posts', 'getUserPosts');
    });
    Route::controller(LikeController::class)->group(function(){
        Route::post('/like-post/{postId}','likePost');
        Route::get('/like/list-user/{postId}', 'userthatlikepost');
        // RESTful aliases
        Route::post('/posts/{postId}/likes','likePost');
        Route::get('/posts/{postId}/likes', 'userthatlikepost');
    });

    Route::controller(challengeController::class)->group(function(){
        Route::post('/join/challenge', 'joinChallenge');
        Route::get('/paystack/callback', 'callback')->name('payment.callback');
    });
  Route::controller(WalletController::class)->group(function(){
        Route::get('/wallet', 'wallet');
        Route::get('/wallet/transactions', 'walletTransactions');
    });
    Route::controller(weelychallengeleaderboardController::class)->group(function(){
        Route::get('/weekly/challenge/leaderboard', 'leaderboard');
    });
    Route::controller(bankaccountController::class)->group(function(){
        Route::post('/bankaccount/create', 'updateOrCreateBankAccount');
        Route::put('/bankaccount', 'updateOrCreateBankAccount');
        Route::get('/bankaccount', 'getBankAccount');
        Route::get('/banks/list', 'Listnigerianigerianbanks');
    });
    Route::get('/allbanks',[BankController::class, 'allbanks']);
    Route::get('/bankdetails',[BankController::class, 'getbankdetails']);
    Route::controller(withdrawController::class)->group(function(){
        Route::post('/withdraw', 'withdraw');
        Route::get('/withdraw/history', 'withdrawHistory');
    });
    Route::controller(NotificationController::class)->group(function(){
        Route::get('/notifications', 'getUserNotifications');
        Route::get('/notifications/mark-as-read', 'markAsRead');
        Route::get('/notifications/mark-as-read/{id}', 'markAsReadById');
        Route::delete('/notifications/delete/{id}', 'deleteNotification');
        // RESTful aliases for state changes
        Route::post('/notifications/mark-as-read', 'markAsRead');
        Route::post('/notifications/{id}/mark-as-read', 'markAsReadById');
        Route::delete('/notifications/{id}', 'deleteNotification');

    });

});

