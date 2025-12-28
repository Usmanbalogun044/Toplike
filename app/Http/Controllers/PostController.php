<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\PostService;
use App\Services\ChallengeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    protected $postService;
    protected $challengeService;

    public function __construct(PostService $postService, ChallengeService $challengeService)
    {
        $this->postService = $postService;
        $this->challengeService = $challengeService;
    }

    /**
     * Create a new post.
     */
    public function createPost(Request $request)
    {
        try {
            $request->validate([
                'caption' => 'nullable|string',
                'post_type' => 'required|in:image,video,mixed',
                'media' => 'required|array|min:1',
                'media.*' => 'required|file|mimes:jpg,jpeg,png,mp4,mov|max:51200', // 50MB
                'music' => 'nullable|file|mimes:mp3,wav|max:10240',
            ]);

            $post = $this->postService->createPost($request, $request->user());

            return response()->json([
                'message' => 'Post created successfully',
                'post' => $post,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Post creation failed: ' . $e->getMessage());
            
            $status = $e->getCode() ?: 500;
            // Provide a default 500 if the code is 0 or invalid HTTP status
            if ($status < 100 || $status > 599) $status = 500;

            return response()->json([
                'message' => 'Failed to create post.',
                'error' => $e->getMessage(),
            ], $status);
        }
    }

    /**
     * Get all verified user posts for the feed (paginated).
     */
    public function getPosts()
    {
        $posts = Post::join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*')
            ->where('posts.is_visible', true)
            ->with(['media', 'user'])
            ->orderBy('users.is_verified', 'desc')
            ->orderBy('posts.created_at', 'desc')
            ->paginate(10);

        return response()->json($posts);
    }

    /**
     * Get a single post by ID.
     */
    public function getPost($id)
    {
        $post = Post::with(['media', 'user'])->findOrFail($id);
        return response()->json($post);
    }

    /**
     * Get posts for a specific user.
     */
    public function getUserPosts($id)
    {
        $posts = Post::with(['media', 'user'])
            ->where('user_id', $id)
            ->where('is_visible', true)
            ->latest()
            ->paginate(10);

        return response()->json($posts);
    }

    /**
     * Check if user has posted for the current challenge.
     */
    public function checkifuserhasposted(Request $request)
    {
        $eligibility = $this->challengeService->checkPostEligibility($request->user());

        if (!$eligibility['can_post']) {
            return response()->json([
                'message' => $eligibility['message'],
            ])->setStatusCode($eligibility['status'], $eligibility['message']);
        }

        return response()->json([
            'message' => 'continue to post',
        ])->setStatusCode(200, 'continue to post');
    }
}
