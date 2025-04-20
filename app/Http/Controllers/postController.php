<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class postController extends Controller
{
    public function createPost(Request $request)
    {
        Try {
            $request->validate([
                'caption' => 'nullable|string',
                'post_type' => 'required|in:image,video,mixed',
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

                $storedPath = $file->store('posts', 'public');
                $fullUrl = url('storage/' . $storedPath);
                $media[] = new PostMedia([
                    'type' => in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png']) ? 'image' : 'video',
                    'file_path' => $fullUrl,
                ]);
            }
    
            $post->media()->saveMany($media);
    
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

    }