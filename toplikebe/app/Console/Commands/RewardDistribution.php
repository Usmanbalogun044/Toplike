<?php

namespace App\Console\Commands;

use App\Models\Challenge;
use App\Models\Post;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;

class RewardDistribution extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'challenge:reward-distribution';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Distribute rewards for the weekly challenge.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Distributing weekly challenge rewards...');
        $now = now();
    $challenge = Challenge::where('ends_at', '<=', $now)
                              ->where('is_completed', false)
                              ->orderBy('ends_at', 'desc')
                              ->first();

        if (!$challenge) {
            $this->info('No challenge to complete at the moment.');
            return; // Exit if no challenge to complete
        }

        // Get all posts in this challenge, sorted by likes
        $topPosts = Post::where('challenge_id', $challenge->id)
                        ->orderByDesc('likes_count')
                        ->take(3)
                        ->get();

        $rewards = [
            0 => 0.40, // Top 1
            1 => 0.10, // Top 2
            2 => 0.05  // Top 3
        ];

        foreach ($topPosts as $index => $post) {
            $user = $post->user;
            $percentage = $rewards[$index] ?? 0;
            $rewardAmount = floor($challenge->total_pool * $percentage);

            // Update or create wallet
            $wallet = UserWallet::firstOrCreate(['user_id' => $user->id]);
            $wallet->balance += $rewardAmount;
            $wallet->last_transaction_at = now();
            $wallet->save();

            WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $rewardAmount,
                'type' => 'credit',
                'description' => 'Challenge Reward for position ' . ($index + 1),
            ]);
        }

        $challenge->is_completed = true;
        $challenge->save();

        $this->info("Rewards distributed for week {$challenge->week_number} of {$challenge->year}");
    }
}
