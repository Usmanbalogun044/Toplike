<?php

namespace App\Http\Controllers;

use App\Models\User;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class userController extends Controller
{
        public function updateProfile(Request $request)
        {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

      $validator = Validator::make($request->all(), [
    'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
    'bio' => 'nullable|string|max:500',
    'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

         $data = [];
         try {

        if ($request->hasFile('profile_picture')) {
            if ($user->image_public_id) {
            Cloudinary::uploadApi()->destroy($user->image_public_id);
            }
            $upload = Cloudinary::uploadApi()->upload($request->file('profile_picture')->getRealPath(), [
            'folder' => 'profile_picture',
            ]);
            $data['profile_picture'] = $upload['secure_url'];
            $data['image_public_id'] = $upload['public_id'];
        }

        if ($request->filled('username')) {
            $data['username'] = $request->input('username');
        }
        if ($request->filled('bio')) {
            $data['bio'] = $request->input('bio');
        }
         }
         catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while updating the profile.'], 500);
         }


        $user->update($data);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user->fresh(),
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
