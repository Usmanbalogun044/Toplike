<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __construct(private readonly LikeService $likeService)
    {
    }

    public function like(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        $this->likeService->likePost($user, $post);

        return response()->json([
            'message' => 'Post liked successfully.',
        ]);
    }

    public function unlike(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        $this->likeService->unlikePost($user, $post);

        return response()->json([
            'message' => 'Post unliked successfully.',
        ]);
    }
}
