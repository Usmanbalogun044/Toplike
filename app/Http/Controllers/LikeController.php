<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function likePost(Request $request, $postId)
    {
        $user = $request->user();

        // Check if the post exists
        $post = Post::findOrFail($postId);

        // Check if the user has already liked the post
        $like = Like::where('user_id', $user->id)->where('post_id', $postId)->first();

        if ($like) {
            $like->is_liked = !$like->is_liked;
            $like->save();

            if ($like->is_liked) {
                // Send notification only if liked
                if ($post->user_id !== $user->id) {
                    $post->user->notify(new \App\Notifications\PostLiked($user, $post));
                }
            } else {
                if ($post->user_id !== $user->id) {
                    $post->user->notifications()
                        ->where('type', \App\Notifications\PostLiked::class)
                        ->where('data->user_id', $user->id)
                        ->where('data->post_id', $post->id)
                        ->delete();
                }
            }
        } else {
            // If the user hasn't liked the post yet, create a new like
            Like::create([
                'user_id' => $user->id,
                'post_id' => $postId,
                'is_liked' => true,
            ]);

            if ($post->user_id !== $user->id) {
                $post->user->notify(new \App\Notifications\PostLiked($user, $post));
            }
        }

        // Update the likes count on the post


        $post->likes_count = $post->likes()->where('is_liked', true)->count();
        $post->save();

        return response()->json([
            'message' => 'Post liked/unliked successfully',
            'likes_count' => $post->likes_count,
        ]);
    }

    // public function unlikePost(Request $request, $postId)
    // {
    //     $user = $request->user();

    //     // Check if the post exists
    //     $post = Post::findOrFail($postId);

    //     // Check if the user has liked the post
    //     $like = Like::where('user_id', $user->id)->where('post_id', $postId)->first();

    //     if ($like) {
    //         // If the user has liked the post, delete the like
    //         $like->delete();
    //     }

    //     // Update the likes count on the post
    //     $post->likes_count = $post->likes()->where('is_liked', true)->count();
    //     $post->save();

    //     return response()->json([
    //         'message' => 'Post unliked successfully',
    //         'likes_count' => $post->likes_count,
    //     ]);
    // }
    // public function getUserLikes(Request $request)
    // {
    //     $user = $request->user();

    //     // Get the posts liked by the user
    //     $likes = Like::where('user_id', $user->id)->where('is_visible', true)->with('post')->get();

    //     return response()->json([
    //         'likes' => $likes,
    //     ]);
    // }

    public function userthatlikepost(Request $request, $postId)
    {
        $user = $request->user();

        // Check if the post exists
        $post = Post::findOrFail($postId);

        // Get the users who liked the post
        $likes = $post->likes()->with('user')->get();

        return response()->json([
            'likes' => $likes,
        ]);

    }
}
