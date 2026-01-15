<?php

namespace App\Services;

use App\Models\User;
use Yabacon\Paystack;
use RuntimeException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    protected Paystack $paystack;

    public function __construct()
    {
        $cfg = config('services.paystack') ?? [];
        $secret = $cfg['secret_key'] ?? env('PAYSTACK_SECRET_KEY');
        if (! $secret) {
            throw new RuntimeException('Paystack secret key not configured');
        }
        $this->paystack = new Paystack($secret);
    }

    /**
     * Initialize a Paystack transaction and return authorization URL + reference.
     * Amount should be provided in kobo.
     */
    public function initialize(User $user, int $amountKobo, string $reference, string $callbackUrl, array $metadata = []): array
    {
        $init = $this->paystack->transaction->initialize([
            'amount' => $amountKobo,
            'email' => $user->email,
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ]);

        return [
            'authorization_url' => $init->data->authorization_url ?? null,
            'reference' => $init->data->reference ?? $reference,
        ];
    }

    /**
     * Verify a Paystack transaction by reference.
     */
    public function verify(string $reference): array
    {
        $verify = $this->paystack->transaction->verify([
            'reference' => $reference,
        ]);

        $ok = isset($verify->data->status) && $verify->data->status === 'success';
        $metadata = isset($verify->data->metadata) ? (array) $verify->data->metadata : [];

        return [
            'success' => $ok,
            'metadata' => $metadata,
            'amount' => isset($verify->data->amount) ? (int) $verify->data->amount : null,
            'currency' => $verify->data->currency ?? null,
        ];
    }

    /**
     * Create Paystack customer tied to the user's email.
     */
    public function createCustomer(User $user): array
    {
        $res = $this->paystack->customer->create([
            'email' => $user->email,
            'first_name' => optional($user->profile)->first_name,
            'last_name' => optional($user->profile)->last_name,
        ]);

        return [
            'customer_code' => $res->data->customer_code ?? null,
            'id' => $res->data->id ?? null,
        ];
    }

    /**
     * Create Dedicated Virtual Account for the customer.
     */
    public function createDedicatedAccount(string $customerCode): array
    {
        $res = $this->paystack->dedicated_account->create([
            'customer' => $customerCode,
            'preferred_bank' => 'wema-bank',
        ]);

        return [
            'dedicated_account_id' => $res->data->id ?? null,
            'account_number' => $res->data->account_number ?? null,
            'bank_name' => $res->data->bank->name ?? null,
            'bank_code' => $res->data->bank->code ?? null,
        ];
    }

    /**
     * Create transfer recipient for bank account withdrawals.
     */
    public function createTransferRecipient(string $name, string $accountNumber, string $bankCode): array
    {
        $res = $this->paystack->transferrecipient->create([
            'type' => 'nuban',
            'name' => $name,
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
        ]);

        return [
            'recipient_code' => $res->data->recipient_code ?? null,
            'details' => isset($res->data) ? (array) $res->data : [],
        ];
    }

    /**
     * Initiate transfer to a recipient. Amount in kobo.
     */
    public function initiateTransfer(int $amountKobo, string $recipientCode, string $reason, ?string $reference = null): array
    {
        $payload = [
            'amount' => $amountKobo,
            'recipient' => $recipientCode,
            'reason' => $reason,
        ];
        if ($reference) {
            $payload['reference'] = $reference;
        }

        $res = $this->paystack->transfer->initiate($payload);

        return [
            'status' => $res->status ?? null,
            'message' => $res->message ?? null,
            'data' => isset($res->data) ? (array) $res->data : [],
        ];
    }

    /**
     * List Nigerian banks from Paystack.
     */
    public function listBanks(): array
    {
        $res = $this->paystack->bank->list();
        $data = isset($res->data) ? (array) $res->data : [];
        return array_map(function ($b) {
            return [
                'name' => $b->name ?? null,
                'code' => $b->code ?? null,
                'slug' => $b->slug ?? null,
            ];
        }, $data);
    }

    /**
     * Resolve account name from account number and bank code.
     */
    public function resolveAccountName(string $accountNumber, string $bankCode): ?string
    {

        $paystack = new \Yabacon\Paystack(config('services.paystack.secret_key'));

        try {
            $response = $paystack->bank->resolve([
                'account_number' => $accountNumber,
                'bank_code'      => $bankCode,
            ]);

            return $response->data->account_name ?? null;

        } catch (\Yabacon\Paystack\Exception\ApiException $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'Integration has been disabled') !== false) {
                Log::info('Paystack resolve skipped: integration disabled');
                return null;
            }
            Log::warning('Paystack account resolve failed', [
                'message' => $msg,
            ]);
            return null;

        } catch (\Throwable $e) {
            Log::error('Paystack resolveAccountName exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
