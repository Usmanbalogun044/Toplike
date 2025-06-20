<?php

namespace App\Http\Controllers;

use App\Models\User;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class userController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'bio' => 'nullable|string|max:1000',
            'profilepix' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('profilepix')) {
            if ($user->image_public_id) {
              Cloudinary::uploadApi()->destroy($user->image_public_id);
             
            }
    
            // Upload new image
            $upload = Cloudinary::uploadApi()->upload($request->file('profilepix')->getRealPath(), [
                'folder' => 'profile_picture',
            ]);
    
            $user->profile_picture = $upload['secure_url'];
            $user->image_public_id = $upload['public_id'];
        }

        $user->name = $request->input('name', $user->name);
        $user->username = $request->input('username', $user->username);
        $user->bio = $request->input('bio', $user->bio);
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }
    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'message' => 'User profile retrieved successfully',
            'user' => $user,
            'posts' => $user->posts()->with(['media', 'likes'])->latest()->paginate(10),
        ])->setStatusCode(200, 'User profile retrieved successfully');
    }
      
    /**
     * Get other user's profile
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Get other user's profile
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function otheruserprofile($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'message' => 'User profile retrieved successfully',
            'user' => $user,
            'posts' => $user->posts()->with(['media', 'likes'])->latest()->paginate(10),
        ])->setStatusCode(200, 'User profile retrieved successfully');
    }
  
}
