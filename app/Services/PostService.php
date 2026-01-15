<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Post;
use App\Models\User;
use App\Services\ChallengeService;
use App\Services\WalletService;
use App\Traits\FileUploadTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PostService
{
    use FileUploadTrait;

    public function __construct(
        private readonly ChallengeService $challengeService,
        private readonly WalletService $walletService,
    ) {
    }

    public function createForActiveChallenge(User $user, UploadedFile $media, string $caption = null): Post
    {
        $challenge = $this->challengeService->getActiveChallenge();

        if (! $challenge) {
            throw new RuntimeException('No active challenge available.');
        }

        // Must have a paid challenge entry
        $hasPaidEntry = \App\Models\ChallengeEntry::where('user_id', $user->id)
            ->where('challenge_id', $challenge->id)
            ->where('payment_status', 'paid')
            ->exists();

        if (! $hasPaidEntry) {
            throw new RuntimeException('You need to join the challenge before posting.');
        }

        // One post per user per challenge
        $hasPost = Post::where('user_id', $user->id)
            ->where('challenge_id', $challenge->id)
            ->exists();

        if ($hasPost) {
            throw new RuntimeException('You have already submitted a post for this challenge.');
        }

        return DB::transaction(function () use ($user, $challenge, $media, $caption) {
            $mediaUrl = $this->uploadFile($media, 'post');

            if (! $mediaUrl) {
                throw new RuntimeException('Failed to upload media.');
            }

            $post = Post::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'caption' => $caption,
                'media_url' => $mediaUrl,
                'media_type' => $media->getClientMimeType(),
                'status' => 'active',
                'likes_count' => 0,
                'comments_count' => 0,
                'views_count' => 0,
            ]);

            return $post->load(['user.profile', 'challenge']);
        });
    }
}
