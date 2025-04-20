<?php

namespace App\Http\Controllers;

use App\Models\challenge;
use App\Models\challengeEntry;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Yabacon\Paystack;

class challengeController extends Controller
{
    public function joinChallenge(Request $request)
{
    $user = $request->user();
    $currentWeek = now()->weekOfYear;
    $year = now()->year;

    $challenge = challenge::firstOrCreate(
        ['week_number' => $currentWeek, 'year' => $year],
        ['starts_at' => now()->startOfWeek(), 'ends_at' => now()->endOfWeek()]
    );

    // Check if already joined
    if (challengeEntry::where('user_id', $user->id)->where('challenge_id', $challenge->id)->exists()) {
        return response()->json(['message' => 'Already joined this week\'s challenge.']);
    }

    $wallet = $user->wallet;

    if ($wallet && $wallet->balance >= $challenge->entry_fee) {
        // Deduct from wallet
        $wallet->balance -= $user->challenge->entry_fee;
        $wallet->last_transaction_at = now();
        $wallet->save();

        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => $challenge->entry_fee,
            'type' => 'debit',
            'description' => 'Challenge Entry Fee',
        ]);

        // Join challenge
        ChallengeEntry::create([
            'challenge_id' => $challenge->id,
            'user_id' => $user->id,
            'has_paid' => true,
        ]);

        // Increase pool
        $challenge->total_pool += $challenge->entry_fee;
        $challenge->save();

        return response()->json(['message' => 'Joined challenge successfully using wallet.']);
    }

    // Redirect to Paystack if insufficient wallet balance
    $reference = 'CHAL_' . uniqid();

    $paystack=new Paystack(config('services.paystack.secret'));
    try {
        $paymentRequest = $paystack->transaction->initialize([
            'amount' => $challenge->entry_fee * 100, // Amount in kobo
            'email' => $user->email,
            'callback_url' => route('payment.callback'),
            'reference' => $reference,
            'metadata' => json_encode([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
            ]),
        ]);

        return response()->json([
            'message' => 'Insufficient wallet balance. Redirecting to Paystack.',
            'redirect_url' => $paymentRequest->data->authorization_url,
            'reference' => $reference,
        ]);

    } catch (\Exception $e) {
        return response()->json(['message' => 'Unable to initialize Paystack payment.', 'error' => $e->getMessage()], 500);
    }
}
    public function callback(Request $request)
    {
        $paystack = new Paystack(config('services.paystack.secret'));
        $response = $paystack->transaction->verify([
            'reference' => $request->query('reference'),
        ]);

        if ($response->status) {
         
            $userId = $response->data->metadata->user_id;
            $challengeId = $response->data->metadata->challenge_id;

            // Update wallet and challenge entry
            $user = User::find($userId);
            $challenge = Challenge::find($challengeId);

            if ($user && $challenge) {
                // Update wallet balance
                $user->wallet->balance -= $challenge->entry_fee;
                $user->wallet->last_transaction_at = now();
                $user->wallet->save();

                // Create wallet transaction
                WalletTransaction::create([
                    'user_id' => $userId,
                    'amount' => $challenge->entry_fee,
                    'type' => 'debit',
                    'description' => 'Challenge Entry Fee',
                ]);

                // Join challenge
                ChallengeEntry::create([
                    'challenge_id' => $challengeId,
                    'user_id' => $userId,
                    'has_paid' => true,
                ]);

                // Increase pool
                $challenge->total_pool += $challenge->entry_fee;
                $challenge->save();

                return response()->json(['message' => 'Payment successful. Joined challenge successfully.']);
            }
        }

        return response()->json(['message' => 'Payment verification failed.'], 400);
    }

}
