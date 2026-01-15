<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\ChallengeEntry;
use App\Models\Post;
use App\Models\User;
use App\Enums\TransactionType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChallengeService
{
    public function getActiveChallenge(): ?Challenge
    {
        $now = Carbon::now();

        return Challenge::where('status', 'active')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->orderByDesc('starts_at')
            ->first();
    }

    public function listChallenges(int $perPage = 15)
    {
        return Challenge::orderByDesc('starts_at')->paginate($perPage);
    }

    public function getLeaderboard(Challenge $challenge, int $limit = 100)
    {
        return Post::where('challenge_id', $challenge->id)
            ->with(['user.profile'])
            ->orderByDesc('likes_count')
            ->limit($limit)
            ->get();
    }

    /**
     * User joins the active challenge: debit wallet if sufficient; otherwise initialize gateway payment.
     * Returns array with either 'joined' => true or 'authorization_url' for Paystack.
     */
    public function joinActiveChallenge(User $user, WalletService $walletService): array
    {
        $challenge = $this->getActiveChallenge();
        if (! $challenge) {
            throw new RuntimeException('No active challenge.');
        }

        // Already joined?
        $existing = ChallengeEntry::where('challenge_id', $challenge->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->payment_status === 'paid') {
            return ['joined' => true];
        }

        $fee = (float) $challenge->entry_fee;
        if ($fee <= 0) {
            // Create/ensure entry as paid without fee
            ChallengeEntry::updateOrCreate(
                ['challenge_id' => $challenge->id, 'user_id' => $user->id],
                ['payment_status' => 'paid', 'paid_at' => Carbon::now()]
            );
            return ['joined' => true];
        }

        // Try wallet debit
        try {
            $walletService->debit(
                $user,
                $fee,
                TransactionType::ENTRY_FEE,
                reference: 'challenge_' . $challenge->id,
                description: 'Challenge entry fee',
                meta: ['challenge_id' => $challenge->id]
            );

            ChallengeEntry::updateOrCreate(
                ['challenge_id' => $challenge->id, 'user_id' => $user->id],
                ['payment_status' => 'paid', 'paid_at' => Carbon::now()]
            );

            return ['joined' => true];
        } catch (\Throwable $e) {
            // Insufficient funds: do not lead to gateway, return funding instructions
            $wallet = $user->wallet;
            ChallengeEntry::updateOrCreate(
                ['challenge_id' => $challenge->id, 'user_id' => $user->id],
                ['payment_status' => 'pending']
            );

            return [
                'joined' => false,
                'reason' => 'insufficient_funds',
                'message' => 'Insufficient funds. Fund your wallet using your virtual account.',
                'account' => [
                    'number' => $wallet?->virtual_account_number,
                    'bank_name' => $wallet?->virtual_account_bank_name,
                    'bank_code' => $wallet?->virtual_account_bank_code,
                ],
                'balance' => (string) ($wallet?->balance ?? '0.00'),
                'entry_fee' => $fee,
            ];
        }
    }

    /**
     * Verify Paystack callback and mark entry as paid.
     */
    public function confirmJoinFromGateway(User $user, string $reference, PaymentGatewayService $gateway): bool
    {
        $challenge = $this->getActiveChallenge();
        if (! $challenge) {
            throw new RuntimeException('No active challenge.');
        }

        $ok = $gateway->verify($reference);
        if (! $ok) {
            return false;
        }

        ChallengeEntry::updateOrCreate(
            ['challenge_id' => $challenge->id, 'user_id' => $user->id],
            ['payment_status' => 'paid', 'paid_at' => Carbon::now()]
        );

        return true;
    }

    /**
     * Close and settle ended challenges, distribute prizes, and create next week's challenge if needed.
     */
    public function settleAndRotate(WalletService $walletService): void
    {
        DB::transaction(function () use ($walletService) {
            $now = Carbon::now();

            // Close and settle any challenge that ended
            $ended = Challenge::where('status', 'active')
                ->where('ends_at', '<', $now)
                ->get();

            foreach ($ended as $challenge) {
                // Compute prize pool from paid entries
                $paidCount = ChallengeEntry::where('challenge_id', $challenge->id)
                    ->where('payment_status', 'paid')
                    ->count();
                $pool = (float) $challenge->entry_fee * $paidCount;

                // Rank posts by likes
                $leaders = Post::where('challenge_id', $challenge->id)
                    ->orderByDesc('likes_count')
                    ->limit(3)
                    ->get();

                $payouts = [0.40, 0.10, 0.05];
                foreach ($leaders as $idx => $post) {
                    $amount = $pool * $payouts[$idx];
                    if ($amount > 0) {
                        $walletService->credit(
                            $post->user,
                            $amount,
                            TransactionType::PRIZE_CREDIT,
                            reference: 'challenge_payout_' . $challenge->id,
                            description: 'Challenge prize payout',
                            meta: ['challenge_id' => $challenge->id, 'position' => $idx + 1]
                        );
                    }
                }

                // Platform share (45%) - optional: credit to platform user/wallet if configured
                $platformShare = $pool * 0.45;
                $platformUserId = (int) (env('PLATFORM_USER_ID', 0));
                if ($platformShare > 0 && $platformUserId > 0) {
                    $platformUser = User::find($platformUserId);
                    if ($platformUser) {
                        $walletService->credit(
                            $platformUser,
                            $platformShare,
                            TransactionType::PRIZE_CREDIT,
                            reference: 'challenge_platform_' . $challenge->id,
                            description: 'Platform share',
                            meta: ['challenge_id' => $challenge->id]
                        );
                    }
                }

                $challenge->update(['status' => 'settled', 'prize_pool' => $pool]);
            }

            // If no active challenge, create new for next week
            if (! $this->getActiveChallenge()) {
                $this->createWeeklyChallenge();
            }
        });
    }

    /**
     * Create next weekly challenge starting Monday 00:00 and ending Sunday 23:59.
     */
    public function createWeeklyChallenge(?float $entryFee = 500.0): Challenge
    {
        $now = Carbon::now();
        // Start immediately (current day start) and end on Sunday 23:59:59
        $start = (clone $now)->startOfDay();
        $end = (clone $start)->endOfWeek(Carbon::SUNDAY)->setTime(23, 59, 59);

        $weekNumber = (int) $start->weekOfYear;
        $year = (int) $start->year;

        return Challenge::create([
            'title' => 'Week ' . $weekNumber . ' Challenge',
            'slug' => 'week-' . $weekNumber . '-' . $year,
            'week_number' => $weekNumber,
            'year' => $year,
            'status' => 'active',
            'starts_at' => $start,
            'ends_at' => $end,
            'entry_fee' => $entryFee,
            'prize_pool' => 0,
            'rules' => 'One post per user. Likes determine ranking. Entry fee required.'
        ]);
    }
}
