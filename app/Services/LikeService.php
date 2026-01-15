<?php

namespace App\Services;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LikeService
{
    public function likePost(User $user, Post $post): void
    {
        if ($post->user_id === $user->id) {
            throw new RuntimeException('You cannot like your own post.');
        }

        $existing = Like::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($existing) {
            // Already liked; no-op
            return;
        }

        DB::transaction(function () use ($user, $post) {
            Like::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $post->increment('likes_count');
        });
    }

    public function unlikePost(User $user, Post $post): void
    {
        DB::transaction(function () use ($user, $post) {
            $deleted = Like::where('user_id', $user->id)
                ->where('post_id', $post->id)
                ->delete();

            if ($deleted) {
                $post->decrement('likes_count');
            }
        });
    }
}
