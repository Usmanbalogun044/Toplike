<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class bankaccountController extends Controller
{
    public function updateOrCreateBankAccount(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_number' => 'required|string|max:10',
            'bank_name' => 'required|string|max:255',
            // 'account_type' => 'required|string|in:savings,current',
        ]);

        try {
            $paystack = new \Yabacon\Paystack(env('PAYSTACK_SECRET_KEY'));

            // Get list of banks from Paystack
            $response = $paystack->bank->list();
            $banks = $response->data;
            // dd($banks);
            // Find matching bank code by name
            $bankCode = collect($banks)->firstWhere('name', $validated['bank_name'])->code ?? null;
            // dd($bankCode);

            if (!$bankCode) {
                return response()->json(['message' => 'Invalid bank name. Please select a valid bank.'], 404);
            }

            // Resolve account name
            $resolve = $paystack->bank->resolve([
                'account_number' => $validated['account_number'],
                'bank_code' => $bankCode,
            ]);
            // dd($resolve);

            if (!$resolve->status) {
                return response()->json(['message' => 'Account verification failed.'], 400);
            }

            $accountName = $resolve->data->account_name;

            // Save or update bank record
            $bankDetails = $user->bankaccount()->updateOrCreate(
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

        } catch (\Throwable $e) {
            Log::error('Bank account saving failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to verify or save bank account.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getBankAccount(Request $request){
        try {
            $user = $request->user();
            $bankAccount = $user->bankaccount()->first();
            if (!$bankAccount) {
                return response()->json(['message' => 'No bank account found'], 404);
            }
            return response()->json([
                'message' => 'Bank account details retrieved successfully',
                'bank_account' => $bankAccount,
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('Bank account retrieval failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while retrieving bank account details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function Listnigerianigerianbanks(Request $request){
        $user = $request->user();
        //paystack api to list bank
        $paystack = new \Yabacon\Paystack(env('PAYSTACK_SECRET_KEY'));
        $response = $paystack->bank->list();
        // dd($response);
        return response()->json([
            'message' => 'Banks retrieved successfully',
            'banks' => $response,
        ])->setStatusCode(200, 'Banks retrieved successfully');
    }
}
