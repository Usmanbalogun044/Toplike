<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserProfileRequest;
use App\Services\UserProfileService;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    protected UserProfileService $profileService;

    public function  __construct(UserProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Update the user's profile
     * Modify profile details and optionally upload an avatar.
     *
     * @tags Profile
     * @requestMediaType multipart/form-data
     */
    public function update(UserProfileRequest $request)
    {
        $user = $request->user();
        $updatedUser = $this->profileService->updateProfile($user, $request->validated());

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $updatedUser,
        ]);
    }
}
