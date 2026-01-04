<?php

namespace App\Services;

use App\Mail\otpmail;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserWallet;
use App\Models\Verification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // 1. Create User
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'user',
                'status' => 'active',
                'is_online' => false,
            ]);

            // 2. Create Profile
            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone_number' => $data['phone_number'] ?? null,
                'country' => $data['country'],
                'is_verified' => false,
            ]);

            // 3. Create Wallet
            UserWallet::create([
                'user_id' => $user->id,
                'balance' => 0.0000,
                'currency' => 'NGN',
                'is_frozen' => false,
            ]);

            // 4. Generate & Store OTP
            $this->generateAndSendOtp($user, 'registration');

            return $user;
        });
    }

    public function login(array $credentials): array
    {
        if (! $token = JWTAuth::attempt($credentials)) {
            throw new \Exception('Invalid credentials', 401);
        }

        $user = auth()->user();

        // Check if user is active
        // if ($user->status !== 'active') {
        //     auth()->logout();
        //     throw new \Exception('Account is ' . $user->status, 403);
        // }

        // Update last login
        $user->update(['last_active_at' => now(), 'is_online' => true]);

        return [
            'user' => $user->load('profile', 'wallet'),
            'token' => $token,
        ];
    }

    public function verifyOtp(string $email, string $code, string $type): bool
    {
        $verification = Verification::where('identifier', $email)
            ->where('type', $type)
            ->where('expires_at', '>', now())
            ->first();

        if (! $verification || ! Hash::check($code, $verification->code)) {
            throw new \Exception('Invalid or expired OTP', 400);
        }

        // Mark as verified
        $verification->update(['verified_at' => now()]);

        // If registration, mark profile as verified (or email_verified_at on user)
        if ($type === 'registration') {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update(['email_verified_at' => now()]);
                // Optionally verify profile too if that's the logic
                // $user->profile()->update(['is_verified' => true]);
            }
        }

        // Delete used OTP
        $verification->delete();

        return true;
    }

    public function generateAndSendOtp(User $user, string $type): void
    {
        $code = (string) rand(100000, 999999);
        
        // Invalidate old OTPs of same type
        Verification::where('identifier', $user->email)
            ->where('type', $type)
            ->delete();

        Verification::create([
            'identifier' => $user->email,
            'code' => Hash::make($code),
            'type' => $type,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Mail::to($user->email)->send(new otpmail($code));
        Log::info("OTP for {$user->email} ($type): $code");
    }

    public function logout()
    {
        $user = auth()->user();
        if ($user) {
            $user->update(['is_online' => false]);
        }
        auth()->logout();
    }
}
