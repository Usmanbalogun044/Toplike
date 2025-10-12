<?php

namespace App\Http\Controllers;


use App\Models\ChallengeEntry;
use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Auth;
use App\Models\Challenge;

class postController extends Controller
{
    /**
     * Create a new post.
     *
     * Handles the creation of a new post, including validation of input data,
     * processing of media uploads, and association with the authenticated user.
     * Returns a response or JSON payload indicating success or failure.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing post data.
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function createPost(Request $request)
    {
     $user = Auth::user();
    $currentWeek = now()->weekOfYear;
    $year = now()->year;

    $challenge = \App\Models\Challenge::where('week_number', $currentWeek)
                          ->where('year', $year)
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

    $entry = \App\Models\ChallengeEntry::where('user_id', $user->id)
                           ->where('challenge_id', $challenge->id)
                           ->first();

    if (!$entry || !$entry->has_paid) {
        return response()->json([
            'message' => 'You must pay for the challenge to create a post.',
        ], 403);
    }

    if ($entry->has_posted) {
        return response()->json([
            'message' => 'You have already posted for this week\'s challenge.',
        ], 403);
    }

        try {
            $request->validate([
                'caption' => 'nullable|string',
                'post_type' => 'required|in:image,video,mixed',
                'media' => 'required|array|min:1',
                'media.*' => 'required|file|mimes:jpg,jpeg,png,mp4,mov|max:51200',
                'music' => 'nullable|file|mimes:mp3,wav|max:10240',
            ]);

            $post = new Post();
            $post->user_id = $request->user()->id;
            $post->caption = $request->input('caption');
            $post->type = $request->input('post_type');
            $post->music = $request->file('music') ? $request->file('music')->store('music', 'public') : null;
            $post->save();

            $media = [];
            foreach ($request->file('media') as $file) {

                $upload = Cloudinary::uploadApi()->upload($file->getRealPath(), [
                    'folder' => 'posts',
                    'resource_type' => in_array($file->getClientOriginalExtension(), ['mp4', 'mov']) ? 'video' : 'image',
                ]);
                $media[] = new PostMedia([
                    'type' => in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png']) ? 'image' : 'video',
                    'file_path' => $upload['secure_url'],
                ]);
            }

            $post->media()->saveMany($media);
            $entry->has_posted = true;
            $entry->save();

            return response()->json([
                'message' => 'Post created successfully',
                'post' => $post->load('media'),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Post creation failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to create post.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Get a single post by its identifier.
     *
     * Retrieves the specified post from storage. May optionally include related
     * resources (e.g., author, comments) based on request parameters. Returns
     * a response or JSON payload with the post data. If the post does not exist,
     * a 404 response may be returned or a ModelNotFoundException thrown.
     *
     * @param int|string $id The unique identifier of the post to retrieve.
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the post cannot be found.
     */
    /
        public function getPosts()
        {
            $posts = Post::with(['media', 'user'])->where('is_visible', true)->latest()->paginate(10);

            return response()->json($posts);
        }
        public function getPost($id)
        {
            $post = Post::with(['media', 'user'])->findOrFail($id);

            return response()->json($post);
        }
        public function getUserPosts($id)
        {
            $posts = Post::with(['media', 'user'])->where('user_id', $id)->where('is_visible', true)->latest()->paginate(10);

            return response()->json($posts);
        }
        public function checkifuserhasposted(Request $request){
            $user = $request->user();
            $currentWeek = now()->weekOfYear;
        $year = now()->Challenge  $challenge = \App\Models\Challenge::where('week_number', $currentWeek)
                            ->where('year', $year)
                            ->first();

        if (!$challenge) {
            return response()->json([
                'message' => 'No active challenge this week.',
            ])->setStatusCode(404,'No active challenge this week');
        }

    $entry = \App\Models\ChallengeEntry::where('user_id', $user->id)
                            ->where('challenge_id', $challenge->id)
                            ->first();

        if (!$entry || !$entry->has_paid) {
            return response()->json([
                'message' => 'You must pay for the challenge to create a post.',
            ])->setStatusCode(403,'You must pay for the challenge to create a post');
        }
        return response()->json([
            'message' => 'continue to post ',
        ])->setStatusCode(200,'continue to post');

        }

    }
