<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WalletService
{
    public function getWallet(User $user): UserWallet
    {
        $wallet = $user->wallet;

        if (! $wallet) {
            throw new RuntimeException('Wallet not found for user.');
        }

        return $wallet;
    }

    public function getBalance(User $user): string
    {
        return (string) $this->getWallet($user)->balance;
    }

    public function credit(User $user, float $amount, TransactionType $type, ?string $reference = null, ?string $description = null, array $meta = []): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $reference, $description, $meta) {
            /** @var UserWallet $wallet */
            $wallet = $this->getWalletForUpdate($user);

            if ($wallet->is_frozen) {
                throw new RuntimeException('Wallet is frozen.');
            }

            $wallet->balance = $wallet->balance + $amount;
            $wallet->save();

            $txReference = $reference ?: (string) Str::uuid();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $type->value,
                'direction' => 'credit',
                'amount' => $amount,
                'reference' => $txReference,
                'description' => $description,
                'status' => 'successful',
                'meta_data' => $meta,
            ]);
        });
    }

    public function debit(User $user, float $amount, TransactionType $type, ?string $reference = null, ?string $description = null, array $meta = []): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $reference, $description, $meta) {
            /** @var UserWallet $wallet */
            $wallet = $this->getWalletForUpdate($user);

            if ($wallet->is_frozen) {
                throw new RuntimeException('Wallet is frozen.');
            }

            if ($wallet->balance < $amount) {
                throw new RuntimeException('Insufficient wallet balance.');
            }

            $wallet->balance = $wallet->balance - $amount;
            $wallet->save();

            $txReference = $reference ?: (string) Str::uuid();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $type->value,
                'direction' => 'debit',
                'amount' => $amount,
                'reference' => $txReference,
                'description' => $description,
                'status' => 'successful',
                'meta_data' => $meta,
            ]);
        });
    }

    protected function getWalletForUpdate(User $user): UserWallet
    {
        /** @var UserWallet|null $wallet */
        $wallet = UserWallet::where('user_id', $user->id)->lockForUpdate()->first();

        if (! $wallet) {
            throw new RuntimeException('Wallet not found for user.');
        }

        return $wallet;
    }
}
