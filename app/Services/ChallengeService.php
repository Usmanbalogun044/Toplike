<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\ChallengeEntry;
use App\Models\User;

class ChallengeService
{
    /**
     * Get the active challenge for the current week.
     */
    public function getActiveChallenge()
    {
        $currentWeek = now()->weekOfYear;
        $year = now()->year;

        return Challenge::where('week_number', $currentWeek)
            ->where('year', $year)
            ->first();
    }

    /**
     * Check if a user can post for the active challenge.
     * returns array [can_post => bool, message => string, status => int]
     */
    public function checkPostEligibility(User $user)
    {
        $challenge = $this->getActiveChallenge();

        if (!$challenge) {
            return ['can_post' => false, 'message' => 'No active challenge this week.', 'status' => 404];
        }

        if ($challenge->is_completed) {
            return ['can_post' => false, 'message' => 'This week\'s challenge has already been completed.', 'status' => 403];
        }

        $entry = ChallengeEntry::where('user_id', $user->id)
            ->where('challenge_id', $challenge->id)
            ->first();

        if (!$entry || !$entry->has_paid) {
            return ['can_post' => false, 'message' => 'You must pay for the challenge to create a post.', 'status' => 403];
        }

        if ($entry->has_posted) {
            return ['can_post' => false, 'message' => 'You have already posted for this week\'s challenge.', 'status' => 403];
        }

        return ['can_post' => true, 'entry' => $entry, 'status' => 200];
    }

    /**
     * Attempt to join a challenge via Wallet. Returns status and message.
     */
    public function joinChallengeViaWallet(User $user, Challenge $challenge)
    {
        // Check if already joined
        if (ChallengeEntry::where('user_id', $user->id)->where('challenge_id', $challenge->id)->exists()) {
             return ['success' => false, 'message' => 'Already joined this week\'s challenge.', 'status' => 400];
        }

        $wallet = $user->wallet;
        if (!$wallet || $wallet->balance < $challenge->entry_fee) {
             return ['success' => false, 'message' => 'Insufficient wallet balance.', 'status' => 402];
        }

        // Processing
        $wallet->balance -= $challenge->entry_fee;
        $wallet->last_transaction_at = now();
        $wallet->save();

        \App\Models\WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => $challenge->entry_fee,
            'type' => 'debit',
            'description' => 'Challenge Entry Fee',
        ]);

        $this->createEntry($user, $challenge);

        return ['success' => true, 'message' => 'Joined challenge successfully using wallet.', 'status' => 200];
    }

    /**
     * Create the challenge entry record.
     */
    public function createEntry(User $user, Challenge $challenge)
    {
        ChallengeEntry::create([
            'challenge_id' => $challenge->id,
            'user_id' => $user->id,
            'has_paid' => true,
        ]);

        $challenge->total_pool += $challenge->entry_fee;
        $challenge->save();
    }
}
