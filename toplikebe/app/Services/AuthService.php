<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Register a new user and send OTP.
     */
    public function register(array $data)
    {
        Log::info('Registering new user', ['email' => $data['email'], 'username' => $data['username']]);
        
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->createWallet();

        // Generate and Send OTP
        $this->sendVerificationOtp($user);

        return $user;
    }

    /**
     * Generate 6-digit OTP and store in Cache for 15 minutes.
     */
    public function sendVerificationOtp(User $user)
    {
        $otp = rand(100000, 999999);
        $key = 'email_verification_otp_' . $user->id;
        
        Log::info('Generating OTP for user', ['user_id' => $user->id, 'email' => $user->email, 'otp' => $otp]);

        // Store in cache for 15 mins
        Cache::put($key, $otp, now()->addMinutes(15));
        
        Log::info('Sending OTP email', ['email' => $user->email]);

        try {
            // Send Email
            Mail::send('emails.verify_otp', ['user' => $user, 'otp' => $otp], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Your TopLike Verification Code');
            });
            Log::info('OTP email sent successfully', ['email' => $user->email]);
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email', ['email' => $user->email, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Verify the OTP provided by the user.
     */
    public function verifyOtp(User $user, $otp)
    {
        $key = 'email_verification_otp_' . $user->id;
        $cachedOtp = Cache::get($key);

        Log::info('Verifying OTP', ['user_id' => $user->id, 'email' => $user->email, 'provided_otp' => $otp, 'cached_otp' => $cachedOtp]);

        if (!$cachedOtp || $cachedOtp != $otp) {
            Log::warning('OTP verification failed', ['user_id' => $user->id, 'reason' => 'Mismatch or expired']);
            return false;
        }

        // Verify user
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            Log::info('User email verified successfully', ['user_id' => $user->id]);
        }

        Cache::forget($key);
        
        return true;
    }


    /**
     * Handle Login logic.
     */
    public function login(array $credentials)
    {
        Log::info('Attempting login', ['email' => $credentials['email']]);

        if (!Auth::guard('web')->attempt($credentials)) {
            Log::warning('Login failed: Invalid credentials', ['email' => $credentials['email']]);
            return null;
        }

        $user = Auth::user();
        Log::info('Login successful', ['user_id' => $user->id, 'email' => $user->email]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }
}
