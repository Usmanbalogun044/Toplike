<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Upload a file to the default configured Storage disk.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param string $resourceType 'image' or 'video' or 'auto'
     * @return string Public URL of the uploaded file
     */
    public function upload(UploadedFile $file, string $folder = 'posts', string $resourceType = 'auto')
    {
        // Generate a unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $folder . '/' . $filename;

        // Upload using the default disk (configured in .env via FILESYSTEM_DISK)
        // We specify 'public' visibility so it works for S3 or Public disk
        Storage::put($path, file_get_contents($file), 'public');

        // Return the full URL using the default disk
        return Storage::url($path);
    }

    /**
     * Determine resource type based on extension.
     */
    public function getResourceType(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return in_array(strtolower($extension), ['mp4', 'mov', 'avi']) ? 'video' : 'image';
    }
}
