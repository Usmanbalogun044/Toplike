<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Services\ChallengeService;
use App\Services\PaymentGatewayService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function __construct(private readonly ChallengeService $challengeService)
    {
    }

    public function current(): JsonResponse
    {
        $challenge = $this->challengeService->getActiveChallenge();

        if (! $challenge) {
            return response()->json([
                'message' => 'No active challenge available.',
            ], 404);
        }

        return response()->json([
            'data' => $challenge,
        ]);
    }

    public function index(): JsonResponse
    {
        $challenges = $this->challengeService->listChallenges();

        return response()->json([
            'data' => $challenges->items(),
            'meta' => [
                'current_page' => $challenges->currentPage(),
                'last_page' => $challenges->lastPage(),
                'per_page' => $challenges->perPage(),
                'total' => $challenges->total(),
            ],
        ]);
    }

    public function leaderboard(Challenge $challenge): JsonResponse
    {
        $entries = $this->challengeService->getLeaderboard($challenge);

        return response()->json([
            'data' => $entries,
        ]);
    }

    public function join(Request $request, WalletService $walletService): JsonResponse
    {
        $user = $request->user();
        $result = $this->challengeService->joinActiveChallenge($user, $walletService);

        return response()->json($result);
    }

    public function joinCallback(Request $request, PaymentGatewayService $gateway): JsonResponse
    {
        $user = $request->user();
        $reference = $request->query('reference');

        if (! $reference) {
            return response()->json(['message' => 'Missing reference'], 400);
        }

        $result = $gateway->verify($reference);
        if (! ($result['success'] ?? false)) {
            return response()->json(['message' => 'Payment verification failed'], 400);
        }
        $ok = $this->challengeService->confirmJoinFromGateway($user, $reference, $gateway);
        return response()->json(['message' => $ok ? 'Challenge joined successfully' : 'Payment verified, but join failed']);
    }

    // Public webhook (no auth): Paystack server callback
    public function webhook(Request $request, PaymentGatewayService $gateway): JsonResponse
    {
        $reference = $request->input('data.reference') ?? $request->input('reference');
        if (! $reference) {
            return response()->json(['message' => 'Missing reference'], 400);
        }

        $result = $gateway->verify($reference);
        if (! ($result['success'] ?? false)) {
            return response()->json(['message' => 'Verification failed'], 400);
        }

        $metadata = $result['metadata'] ?? [];
        $challengeId = $metadata['challenge_id'] ?? null;
        $userId = $metadata['user_id'] ?? null;

        if (! $challengeId || ! $userId) {
            return response()->json(['message' => 'Missing metadata'], 400);
        }

        // Mark the entry as paid
        \App\Models\ChallengeEntry::updateOrCreate(
            ['challenge_id' => $challengeId, 'user_id' => $userId],
            ['payment_status' => 'paid', 'paid_at' => now()]
        );

        return response()->json(['message' => 'Webhook processed']);
    }
}
