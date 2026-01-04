<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserWallet;
use App\Models\Verification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // 1. Create User
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => UserRole::USER,
                'status' => UserStatus::ACTIVE,
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

            // 4. Generate OTP
            $this->generateOtp($user);

            return $user;
        });
    }

    private function generateOtp(User $user): void
    {
        $code = (string) rand(100000, 999999);
        
        Verification::where('identifier', $user->email)
            ->where('type', 'registration')
            ->delete();

        Verification::create([
            'identifier' => $user->email,
            'code' => Hash::make($code),
            'type' => 'registration',
            'expires_at' => now()->addMinutes(15),
        ]);
    }
}
