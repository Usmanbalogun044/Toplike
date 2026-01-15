<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BankAccountController extends Controller
{
    public function storeOrUpdate(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'account_number' => ['required', 'string', 'min:10', 'max:10'],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $bankName = $request->input('bank_name');
        $accountNumber = $request->input('account_number');
        $providedAccountName = $request->input('account_name');

        try {
            $bankCode = null;
            $resolvedName = null;

            if (config('services.paystack.enable_bank_lookup')) {
                $gateway = app(PaymentGatewayService::class);
                $banks = $gateway->listBanks();
                foreach ($banks as $b) {
                    if (isset($b['name']) && mb_strtolower(trim($b['name'])) === mb_strtolower(trim($bankName))) {
                        $bankCode = $b['code'] ?? null;
                        break;
                    }
                }
                if (! $bankCode) {
                    return response()->json(['message' => 'Invalid bank name. Select a valid bank.'], 404);
                }
                // Resolve account name
                $resolvedName = $gateway->resolveAccountName($accountNumber, $bankCode);
                if (! $resolvedName) {
                    return response()->json(['message' => 'Account verification failed.'], 400);
                }
            }

            $bank = $user->bank()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'account_number' => $accountNumber,
                    'bank_name' => $bankName,
                    'bank_code' => $bankCode,
                    'account_name' => $resolvedName ?? $providedAccountName,
                    'is_verified' => (bool) $resolvedName,
                ]
            );

            return response()->json([
                'message' => 'Bank account details saved successfully.',
                'bank_account' => $bank,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Bank account saving failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to verify or save bank account.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $bankAccount = $user->bank()->first();
            if (! $bankAccount) {
                return response()->json(['message' => 'No bank account found'], 404);
            }
            return response()->json([
                'message' => 'Bank account details retrieved successfully',
                'bank_account' => $bankAccount,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Bank account retrieval failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while retrieving bank account details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
