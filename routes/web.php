<?php

use App\Models\User;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// require __DIR__.'/auth.php';

Route::get('/verify/{id}/{token}', function (Request $request, $id, $token) {
    $user = User::findOrFail($id);
    // Verify the token
    if ($user->email_verification_token == $token) {
        $user->update(['email_verified_at' => now(), 'email_verification_token' => null]);
        // dd($user);
        $frontendurl='https://toplikefe.up.railway.app';
        return redirect($frontendurl.'/login');
        // return response()->json(['message' => 'Email verified successfully'], 200);
    } else {
        return response()->json(['message' => 'Invalid verification token'], 400);
    }
})->name('api.verification.verify');