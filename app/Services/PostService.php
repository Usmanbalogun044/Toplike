<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostMedia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostService
{
    protected $challengeService;
    protected $mediaService;

    public function __construct(ChallengeService $challengeService, MediaService $mediaService)
    {
        $this->challengeService = $challengeService;
        $this->mediaService = $mediaService;
    }

    /**
     * Create a new post for the weekly challenge.
     */
    public function createPost(Request $request, User $user)
    {
        // 1. Check Eligibility
        $eligibility = $this->challengeService->checkPostEligibility($user);
        if (!$eligibility['can_post']) {
            throw new \Exception($eligibility['message'], $eligibility['status']);
        }

        $entry = $eligibility['entry'];

        return DB::transaction(function () use ($request, $user, $entry) {
            // 2. Create Post
            $post = new Post();
            $post->user_id = $user->id;
            $post->caption = $request->input('caption');
            $post->type = $request->input('post_type'); // 'image', 'video', 'mixed'
            $post->music = $request->file('music') ? $request->file('music')->store('music', 'public') : null;
            $post->is_visible = true; // Default to visible
            $post->save();

            // 3. Handle Media Uploads
            $mediaItems = [];
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {
                    $mediaItems[] = new PostMedia([
                        'type' => $this->mediaService->getResourceType($file),
                        'file_path' => $this->mediaService->upload($file),
                    ]);
                }
                $post->media()->saveMany($mediaItems);
            }

            // 4. Mark Entry as Posted
            $entry->has_posted = true;
            $entry->save();

            return $post->load('media');
        });
    }

    /**
     * Delete a post.
     */
    public function deletePost(Post $post)
    {
        // Logic to delete from Cloudinary can be added here
        $post->media()->delete();
        $post->delete();
    }
}
