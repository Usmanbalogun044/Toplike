<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Yabacon\Paystack;
use Illuminate\Support\Facades\Auth;

class withdrawController extends Controller
{
    private function createTransferRecipient($username, $accountNumber, $bankCode){
        $url = "https://api.paystack.co/transferrecipient";

        $data = [
            'type' => 'nuban',
            'name' => $username,
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
            'currency' => 'NGN',
        ];
    
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->post($url, $data);
    
        if ($response->successful()) {
            $recipientCode = $response->json('data.recipient_code');
    
            // Save recipient code to user bank account
            $accountDetails =  Auth::user()->bankaccount;
            $accountDetails->recipient_code = $recipientCode;
            $accountDetails->save();
    
            return $recipientCode;
        } else {
            Log::error('Paystack recipient creation failed', [
                'response' => $response->body(),
            ]);
            return null;
        }
    }
    //withdraw with paystack
    public function withdraw(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;
        $account = $user->bankaccount;
    
        $request->validate([
            'amount' => 'required|numeric|min:1000',
        ]);
    
        $amount = $request->amount;
    
        if (!$account) {
            return response()->json(['message' => 'No bank account found'], 404);
        }
    
        if ($wallet->balance < $amount) {
            return response()->json(['message' => 'Insufficient wallet balance'], 400);
        }
    
        // If no recipient code, create one
        if (!$account->recipient_code) {
            $recipientCode = $this->createTransferRecipient($user->name, $account->account_number, $account->bank_code);
    
            if (!$recipientCode) {
                return response()->json(['message' => 'Failed to create recipient code'], 500);
            }
        } else {
            $recipientCode = $account->recipient_code;
        }
    
        $paystack = new \Yabacon\Paystack(env('PAYSTACK_SECRET_KEY'));
    
        $reference = 'WITHDRAW_' . uniqid();
    
        try {
            $response = $paystack->transfer->initiate([
                'source' => 'balance',
                'reason' => 'TopLike Weekly Challenge Payout',
                'amount' => $amount * 100, // in kobo
                'recipient' => $recipientCode,
                'reference' => $reference,
            ]);
         
    
            if ($response->status) {
                // Deduct from wallet
                $wallet->balance -= $amount;
                $wallet->save();
    
                // Save withdrawal record
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'amount' => -$amount,
                    'type' => 'withdrawal',
                    'description' => 'Withdrawal to bank account',
                    'reference' => $reference,
                    'status' => 'pending', // Mark as pending until webhook confirms
                ]);
    
                return response()->json(['message' => 'Withdrawal initiated', 'data' => $response->data]);
            }
            // dd($response);
            return response()->json(['message' => 'Withdrawal failed', 'data' => $response], 500);
    
        } catch (\Exception $e) {
            Log::error('Withdrawal error: ' . $e->getMessage());
            return response()->json(['message' => 'Withdrawal request failed', 'error'=>$e->getMessage()], 500);
        }
    }
    
}
