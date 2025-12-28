<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\ChallengeService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class JoinChallengeController extends Controller
{
    protected $challengeService;
    protected $paymentService;

    public function __construct(ChallengeService $challengeService, PaymentService $paymentService)
    {
        $this->challengeService = $challengeService;
        $this->paymentService = $paymentService;
    }

    /**
     * Join challenge via auth user (Wallet or Redirect to Paystack).
     */
    public function joinChallenge(Request $request)
    {
        $user = $request->user();
        
        // Ensure active challenge exists
        $currentWeek = now()->weekOfYear;
        $year = now()->year;
        $challenge = Challenge::firstOrCreate(
            ['week_number' => $currentWeek, 'year' => $year],
            ['starts_at' => now()->startOfWeek(), 'ends_at' => now()->endOfWeek(), 'entry_fee' => 500] // Default fee if not set
        );

        // Try Wallet Join
        $result = $this->challengeService->joinChallengeViaWallet($user, $challenge);

        if ($result['success']) {
            return response()->json(['message' => $result['message']]);
        }

        // If failed due to insufficient funds (status 402), initiate Paystack
        if ($result['status'] === 402) {
            $reference = 'CHAL_' . uniqid();
            try {
                $paymentRequest = $this->paymentService->initializeTransaction(
                    $challenge->entry_fee * 100,
                    $user->email,
                    $reference,
                    ['user_id' => $user->id, 'challenge_id' => $challenge->id],
                    route('payment.callback')
                );

                return response()->json([
                    'message' => 'Insufficient wallet balance. Redirecting to Paystack.',
                    'redirect_url' => $paymentRequest->data->authorization_url,
                    'reference' => $reference,
                ]);

            } catch (\Exception $e) {
                return response()->json(['message' => 'Unable to initialize payment.', 'error' => $e->getMessage()], 500);
            }
        }

        // Other errors (e.g. already joined)
        return response()->json(['message' => $result['message']], $result['status']);
    }

    /**
     * Payment Callback
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');
        $response = $this->paymentService->verifyTransaction($reference);

        if ($response && $response->status) {
            $userId = $response->data->metadata->user_id;
            $challengeId = $response->data->metadata->challenge_id;

            $user = User::find($userId);
            $challenge = Challenge::find($challengeId);

            if ($user && $challenge) {
                // Record transaction (Credit then Debit? Or just create record? Original code just created record)
                // We should credit wallet then join, or just join.
                // Original logic: Create wallet transaction (Debit) - wait, this assumes money was added? 
                // Ah, user pays Paystack -> Platform. Platform should credit User Wallet then Debit it? 
                // Or just mark entry as paid.
                // Original code: Created "Debit" transaction and joined. It didn't credit wallet first.
                // This implies "Paystack" payment is treated as "Direct Pay for Item" not "Topup".
                
                WalletTransaction::create([
                    'user_id' => $userId,
                    'amount' => $challenge->entry_fee,
                    'type' => 'debit',
                    'description' => 'Challenge Entry Fee (Paystack)',
                ]);

                $this->challengeService->createEntry($user, $challenge);

                return response()->json(['message' => 'Payment successful. Joined challenge successfully.']);
            }
        }

        return response()->json(['message' => 'Payment verification failed.'], 400);
    }
}
