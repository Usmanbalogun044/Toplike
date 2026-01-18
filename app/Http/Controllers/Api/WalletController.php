<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    /**
     * Get wallet details
     * Retrieve current balances and virtual account info.
     *
     * @tags Wallet
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $this->walletService->getWallet($user);

        return response()->json([
            'data' => [
                'balance' => $wallet->balance,
                'currency' => $wallet->currency,
                'is_frozen' => $wallet->is_frozen,
                'virtual_account_number' => $wallet->virtual_account_number,
                'virtual_account_bank_name' => $wallet->virtual_account_bank_name,
                'virtual_account_bank_code' => $wallet->virtual_account_bank_code,
                'paystack_customer_code' => $wallet->paystack_customer_code,
                'funding_available' => (bool) $wallet->virtual_account_number,
            ],
        ]);
    }

    /**
     * List wallet transactions
     * Paginated list of recent wallet activity.
     *
     * @tags Wallet
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $this->walletService->getWallet($user);

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Provision a dedicated virtual account (Paystack)
     * Create a DVA via Paystack for bank transfer funding.
     *
     * @tags Wallet
     */
    public function provisionVirtualAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $this->walletService->getWallet($user);

        if (! config('services.paystack.enable_dva')) {
            return response()->json([
                'message' => 'Dedicated virtual accounts are disabled. Enable PAYSTACK_ENABLE_DVA to proceed.',
            ], 400);
        }

        if (! $wallet->paystack_customer_code) {
            try {
                $gateway = app(PaymentGatewayService::class);
                $customer = $gateway->createCustomer($user);
                if (! ($customer['customer_code'] ?? null)) {
                    return response()->json(['message' => 'Failed to create Paystack customer'], 400);
                }
                $wallet->paystack_customer_code = $customer['customer_code'];
                $wallet->save();
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Paystack customer setup failed: ' . $e->getMessage()], 400);
            }
        }

        try {
            $gateway = app(PaymentGatewayService::class);
            $dva = $gateway->createDedicatedAccount($wallet->paystack_customer_code);
            $wallet->paystack_dedicated_account_id = $dva['dedicated_account_id'] ?? null;
            $wallet->virtual_account_number = $dva['account_number'] ?? null;
            $wallet->virtual_account_bank_name = $dva['bank_name'] ?? null;
            $wallet->virtual_account_bank_code = $dva['bank_code'] ?? null;
            $wallet->save();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Paystack DVA setup failed: ' . $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Virtual account provisioned',
            'data' => [
                'virtual_account_number' => $wallet->virtual_account_number,
                'virtual_account_bank_name' => $wallet->virtual_account_bank_name,
                'virtual_account_bank_code' => $wallet->virtual_account_bank_code,
                'paystack_customer_code' => $wallet->paystack_customer_code,
            ],
        ]);
    }
}
