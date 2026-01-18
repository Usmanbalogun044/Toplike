<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle Paystack webhook events
     * Validate signature and process incoming payment events.
     *
     * @tags Webhooks
     * @unauthenticated
     */
    public function handle(Request $request, WalletService $walletService): JsonResponse
    {
        // Verify signature
        $signature = $request->header('X-Paystack-Signature');
        $secret = config('services.paystack.secret_key') ?? env('PAYSTACK_SECRET_KEY');
        $computed = hash_hmac('sha512', $request->getContent(), $secret);
        if (! hash_equals($computed, (string) $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $data = $request->input('data', []);

        if ($event === 'charge.success') {
            // Identify wallet via customer code
            $customer = $data['customer'] ?? [];
            $customerCode = $customer['customer_code'] ?? null;
            $amountKobo = (int) ($data['amount'] ?? 0);

            if ($customerCode && $amountKobo > 0) {
                $wallet = UserWallet::where('paystack_customer_code', $customerCode)->first();
                if ($wallet) {
                    try {
                        $user = $wallet->user;
                        $amount = $amountKobo / 100.0;
                        $walletService->credit(
                            $user,
                            $amount,
                            \App\Enums\TransactionType::DEPOSIT,
                            reference: $data['reference'] ?? null,
                            description: 'Paystack deposit via virtual account',
                            meta: ['paystack_event' => $event]
                        );
                    } catch (\Throwable $e) {
                        Log::error('Webhook credit failed: ' . $e->getMessage());
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
