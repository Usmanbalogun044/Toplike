<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait FileUploadTrait
{
    /**
     * Upload a file to the specified disk and path based on type.
     *
     * @param UploadedFile $file
     * @param string $type (e.g., 'avatar', 'post', 'verification')
     * @param string $disk
     * @return string|false
     */
    public function uploadFile(UploadedFile $file, string $type = 'general', string $disk = 'public'): string|false
    {
        // Determine folder based on type
        $folder = match ($type) {
            'avatar' => 'avatars',
            'post' => 'posts',
            'verification' => 'verifications',
            default => $type, // Fallback to using the type as the folder name
        };

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        $path = $file->storeAs($folder, $filename, $disk);

        return $path ? Storage::disk($disk)->url($path) : false;
    }

    /**
     * Get the full URL of a file.
     *
     * @param string|null $path
     * @param string $disk
     * @return string|null
     */
    public function getFileUrl(?string $path, string $disk = 'public'): ?string
    {
        return $path;
    }

    /**
     * Delete a file from the specified disk.
     *
     * @param string|null $path
     * @param string $disk
     * @return bool
     */
    public function deleteFile(?string $path, string $disk = 'public'): bool
    {
        if (! $path) {
            return false;
        }

        // Extract relative path if it's a full URL
        if (Str::startsWith($path, ['http://', 'https://', '/storage/'])) {
            // Try to remove the storage URL prefix
            $storageUrl = Storage::disk($disk)->url('');
            
            // Handle case where url() returns full URL or relative path
            if (Str::startsWith($path, $storageUrl)) {
                $path = Str::replaceFirst($storageUrl, '', $path);
            } else {
                // Fallback cleanup for common patterns
                $path = parse_url($path, PHP_URL_PATH);
                $path = ltrim($path, '/');
                if (Str::startsWith($path, 'storage/')) {
                    $path = Str::replaceFirst('storage/', '', $path);
                }
            }
        }

        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }
}
