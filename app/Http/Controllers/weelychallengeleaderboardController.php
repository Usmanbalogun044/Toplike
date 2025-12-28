<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use Illuminate\Http\Request;

class weelychallengeleaderboardController extends Controller
{
    public function leaderboard(Request $request){
        $currentweek= now()->weekOfYear;
        $currentyear= now()->year;
        $challenge= Challenge::where('week_number', $currentweek)
            ->where('year', $currentyear)
            ->first();
        if (!$challenge) {
            return response()->json([
                'message' => 'No active challenge this week.',
            ], 404);
        }
        //check if challenge has being completed
        if ($challenge->is_completed) {
            return response()->json([
                'message' => 'This week\'s challenge has already been completed.',
            ], 403);
        }
        // Top 100 leaderboard TopLike posts within the challenge period
        $topPosts = \App\Models\Post::whereBetween('created_at', [$challenge->starts_at, $challenge->ends_at])
            ->where('is_visible', true)
            ->withCount('likes')
            ->orderBy('likes_count', 'desc')
            ->take(100)
            ->with(['user:id,username,profile_picture,is_verified'])
            ->get();
            // the totalpool of the challenge
        $totalPool = $challenge->total_pool;
      $rewards = [
            0 => 0.40, // Top 1
            1 => 0.10, // Top 2
            2 => 0.05, // Top 3
        ];
        // Calculate the reward for each post
        foreach ($topPosts as $index => $post) {
            $percentage = $rewards[$index] ?? 0;
            $rewardAmount = floor($totalPool * $percentage);
            $post->reward = $rewardAmount;
        }
            return response()->json([
                'message' => 'Leaderboard retrieved successfully',
                'total_pool' => $totalPool,
                'leaderboard' => $topPosts,
            ])->setStatusCode(200,'Leaderboard retrieved successfully');
    }
}
