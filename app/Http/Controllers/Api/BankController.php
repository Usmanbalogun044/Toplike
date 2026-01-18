<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Facades\Log;
use Yabacon\Paystack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function __construct(private readonly PaymentGatewayService $gateway)
    {
    }

    /**
     * List supported banks
     * Retrieve a list of banks from the provider.
     *
     * @tags Banks
     * @unauthenticated
     */
    public function list(): JsonResponse
    {
        try {
            $banks = $this->gateway->listBanks();
            return response()->json(['data' => $banks]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Resolve account name
     * Resolve account holder name by account number and bank code.
     *
     * @tags Banks
     * @unauthenticated
     */
    public function resolve(Request $request): JsonResponse
    {
        $request->validate([
            'bank_code' => ['required', 'string'],
            'account_number' => ['required', 'string', 'min:10', 'max:10'],
        ]);
        try {
            $name = $this->gateway->resolveAccountName($request->input('account_number'), $request->input('bank_code'));
            return response()->json(['data' => ['account_name' => $name]]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Verify and save user's bank account using Paystack SDK directly.
     */
    /**
     * Verify and save user's bank account
     * Validate account with Paystack and persist bank details.
     *
     * @tags Banks
     */
    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_number' => 'required|string|max:10',
            'bank_name' => 'required|string|max:255',
        ]);

        try {
            $secret = config('services.paystack.secret_key') ?? env('PAYSTACK_SECRET_KEY');
            $paystack = new Paystack($secret);

            // Get list of banks from Paystack
            $response = $paystack->bank->list();
            $banks = isset($response->data) ? (array) $response->data : [];

            // Find matching bank code by name
            $match = collect($banks)->firstWhere('name', $validated['bank_name']);
            $bankCode = $match->code ?? null;

            if (! $bankCode) {
                return response()->json(['message' => 'Invalid bank name. Please select a valid bank.'], 404);
            }

            // Resolve account name
            $resolve = $paystack->bank->resolve([
                'account_number' => $validated['account_number'],
                'bank_code'      => $bankCode,
            ]);

            if (! ($resolve->status ?? false)) {
                return response()->json(['message' => 'Account verification failed.'], 400);
            }

            $accountName = $resolve->data->account_name ?? null;

            // Save or update bank record
            $user = $request->user();
            $bankDetails = $user->bank()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'account_number' => $validated['account_number'],
                    'bank_name' => $validated['bank_name'],
                    'bank_code' => $bankCode,
                    'account_name' => $accountName,
                    'is_verified' => true,
                ]
            );

            return response()->json([
                'message' => 'Bank account details saved successfully.',
                'bank_account' => $bankDetails,
            ], 200);

        } catch (\Yabacon\Paystack\Exception\ApiException $e) {
            Log::warning('Paystack account resolve failed', [
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to verify account with Paystack.',
                'error' => $e->getMessage(),
            ], 400);

        } catch (\Throwable $e) {
            Log::error('Bank account saving failed', [
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to verify or save bank account.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
