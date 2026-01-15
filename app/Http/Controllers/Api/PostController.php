<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(private readonly PostService $postService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'media' => ['required', 'file', 'mimes:jpg,jpeg,png,mp4,mov,avi', 'max:10240'],
            'caption' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $post = $this->postService->createForActiveChallenge(
            $user,
            $request->file('media'),
            $request->input('caption')
        );

        return response()->json([
            'message' => 'Post created successfully for current challenge.',
            'data' => $post,
        ], 201);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json([
            'data' => $post->load(['user.profile', 'challenge']),
        ]);
    }
}
