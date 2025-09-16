<?php

namespace App\Http\Controllers;

use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class userController extends Controller
{

        public function updateProfile(Request $request)
        {
            $user= Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $validator = Validator::make($request->all(), [
                'username' => ['sometimes', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
                'bio' => ['sometimes', 'string', 'max:500'],
                'profilepix' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            ]);
      

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }


            $validated = $validator->validated();

            $data = [];

            if ($request->hasFile('profilepix')) {
                try {
                    if (!empty($user->image_public_id)) {
                        Cloudinary::destroy($user->image_public_id);
                    }

                    $upload = Cloudinary::uploadFile(
                        $request->file('profilepix')->getRealPath(),
                        ['folder' => 'profile_picture', 'resource_type' => 'image']
                    );

                    $data['profile_picture'] = $upload->getSecurePath();
                    $data['image_public_id'] = $upload->getPublicId();
                } catch (\Throwable $e) {
                    Log::error('Cloudinary upload failed', ['error' => $e->getMessage()]);
                    return response()->json([
                        'message' => 'Image upload failed, please try again later.'
                    ], 500);
                }
            }

            if (array_key_exists('username', $validated)) {
                $data['username'] = $validated['username'];
            }
            if (array_key_exists('bio', $validated)) {
                $data['bio'] = $validated['bio'];
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
