<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class WithdrawalService
{
    public function request(User $user, float $amount, string $bankName, string $accountNumber, ?string $accountName = null): Withdrawal
    {
        /** @var UserWallet $wallet */
        $wallet = $user->wallet;
        if (! $wallet) {
            throw new RuntimeException('Wallet not found');
        }
        if ($wallet->is_frozen) {
            throw new RuntimeException('Wallet is frozen');
        }
        if ($wallet->balance < $amount) {
            throw new RuntimeException('Insufficient wallet balance');
        }

        return DB::transaction(function () use ($user, $wallet, $amount, $bankName, $accountNumber, $accountName) {
            // Determine bank code from bank name if lookup is enabled
            $bankCode = null;
            if (config('services.paystack.enable_bank_lookup')) {
                try {
                    $banks = app(PaymentGatewayService::class)->listBanks();
                    foreach ($banks as $b) {
                        if (isset($b['name']) && mb_strtolower(trim($b['name'])) === mb_strtolower(trim($bankName))) {
                            $bankCode = $b['code'] ?? null;
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Bank list fetch failed: ' . $e->getMessage());
                }
            }

            // Attempt to auto-resolve account name if enabled and not provided
            $resolvedName = null;
            if (! $accountName && config('services.paystack.enable_bank_lookup') && $bankCode) {
                try {
                    $resolvedName = app(PaymentGatewayService::class)->resolveAccountName($accountNumber, $bankCode);
                } catch (\Throwable $e) {
                    Log::warning('Account name resolve failed: ' . $e->getMessage());
                }
            }

            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'account_name' => $resolvedName ?? $accountName ?? $user->profile->first_name . ' ' . $user->profile->last_name,
                'status' => 'processing',
            ]);

            // If transfers are disabled, approve locally and debit wallet
            if (! config('services.paystack.enable_transfers')) {
                $reference = (string) Str::uuid();
                app(WalletService::class)->debit($user, $amount, TransactionType::WITHDRAWAL, $reference, 'Withdrawal to bank (test mode)', [
                    'bank_code' => $bankCode,
                ]);
                $withdrawal->status = 'approved';
                $withdrawal->processed_at = now();
                $withdrawal->admin_note = 'Test mode approval: ' . $reference;
                $withdrawal->save();
                return $withdrawal;
            }

            if (! $bankCode) {
                $withdrawal->status = 'rejected';
                $withdrawal->admin_note = 'Bank code not found for ' . $bankName;
                $withdrawal->save();
                throw new RuntimeException('Bank code not found for the provided bank name.');
            }

            $gateway = app(PaymentGatewayService::class);
            $recipient = $gateway->createTransferRecipient($withdrawal->account_name, $withdrawal->account_number, $bankCode);
            if (! ($recipient['recipient_code'] ?? null)) {
                $withdrawal->status = 'rejected';
                $withdrawal->admin_note = 'Failed to create recipient';
                $withdrawal->save();
                throw new RuntimeException('Failed to create transfer recipient');
            }

            // Initiate transfer: Paystack expects amount in kobo
            $reference = (string) Str::uuid();
            $init = $gateway->initiateTransfer((int) round($amount * 100), $recipient['recipient_code'], 'Wallet withdrawal', $reference);
            $ok = ($init['status'] ?? null) === true || (($init['data']['status'] ?? null) === 'success');

            if ($ok) {
                // Debit wallet and mark approved
                app(WalletService::class)->debit($user, $amount, TransactionType::WITHDRAWAL, $reference, 'Withdrawal to bank', [
                    'bank_code' => $bankCode,
                    'recipient_code' => $recipient['recipient_code'],
                ]);
                $withdrawal->status = 'approved';
                $withdrawal->processed_at = now();
                $withdrawal->admin_note = $reference;
                $withdrawal->save();
                return $withdrawal;
            } else {
                $withdrawal->status = 'rejected';
                $withdrawal->admin_note = $init['message'] ?? 'Transfer initiation failed';
                $withdrawal->save();
                throw new RuntimeException('Transfer initiation failed: ' . ($init['message'] ?? 'Unknown')); 
            }
        });
    }
}
