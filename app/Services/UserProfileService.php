<?php

namespace App\Services;

use App\Models\User;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;

class UserProfileService
{
    use FileUploadTrait;

    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // 1. Update User Core Data (e.g. username)
            if (isset($data['username'])) {
                $user->update(['username' => $data['username']]);
            }

            // 2. Handle Avatar Upload
            if (isset($data['avatar'])) {
                // Delete old avatar if exists
                if ($user->profile && $user->profile->avatar_url) {
                    $this->deleteFile($user->profile->avatar_url);
                }

                $path = $this->uploadFile($data['avatar'], 'avatars');
                $data['avatar_url'] = $path;
            }

            // 3. Update Profile Data
            // Remove keys that belong to the User model or are temporary
            $profileData = collect($data)
                ->except(['username', 'email', 'password', 'avatar'])
                ->toArray();
            
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );

            return $user->refresh()->load('profile');
        });
    }
}
