<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use Str;

class Authcontroller extends Controller
{
    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $token = Str::random(40);
        // dd($token);
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verification_token'=>$token
        ]);
// dd($user);
        // $user = $notifiable;
   
        // $user->update(['email_verification_token' => $token]);
        $apiUrl = route('api.verification.verify', ['id' => $user->id, 'token' => $token]);
        $token = $user->createToken('TopLikeApp')->plainTextToken;

        $appname = env('APP_NAME');
        $mailfrom=env('MAIL_FROM_ADDRESS');
    
            $mail = Mail::send('emails.verify', [
                'name' => $user->name,
                'title_site' => $appname,
                'verification_link' => $apiUrl,
            ], function ($message) use ($user, $appname, $mailfrom) {
                $message->from($mailfrom, $appname);
                $message->to($user->email, $user->name);
                $message->subject('Verify Your Email Address');
            });

        return response()->json([
            'message' => 'User registered successfully! Please verify your email.',
            'token'=>$token
], 201);
    }
    public function login(Request $request)
    {
        // Validate incoming request
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Attempt to log the user in
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // Check if email is verified
            if (!$user->hasVerifiedEmail()) {
                return response()->json(['message' => 'Please verify your email before logging in.'], 403);
            }

            // Generate an API token using Laravel Sanctum (or Passport)
            $token = $user->createToken('TopLikeApp')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user,
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }
    public function logout(Request $request)
    {
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['message' => 'Logged out successfully']);
    }
}
