<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    /**
     * Upload a file to public path (or storage disk) and optionally delete the old file.
     *
     * @param UploadedFile $file The uploaded file instance
     * @param string $directory Target subfolder in public/ (e.g., 'uploads/staff')
     * @param string|null $prefix Optional filename prefix (e.g., 'staff_28')
     * @param string|null $oldRelativePath Path to old file to delete if replacing
     * @return string The relative path of the saved file (e.g., 'uploads/staff/staff_28_1729412345.png')
     */
    public function upload(
        UploadedFile $file,
        string $directory = 'uploads',
        ?string $prefix = null,
        ?string $oldRelativePath = null
    ): string {
        // Delete old file if provided
        if (!empty($oldRelativePath)) {
            $this->delete($oldRelativePath);
        }

        $targetDir = public_path($directory);
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true, true);
        }

        $extension = $file->getClientOriginalExtension();
        $safePrefix = $prefix ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $prefix) . '_' : '';
        $filename = $safePrefix . time() . '_' . uniqid() . '.' . $extension;

        $file->move($targetDir, $filename);

        return trim($directory, '/') . '/' . $filename;
    }

    /**
     * Delete a file safely from public path or storage.
     *
     * @param string|null $relativePath Relative path to file
     * @return bool True if deleted or file didn't exist
     */
    public function delete(?string $relativePath): bool
    {
        if (empty($relativePath)) {
            return true;
        }

        $fullPath = public_path($relativePath);
        if (File::exists($fullPath) && !File::isDirectory($fullPath)) {
            return File::delete($fullPath);
        }

        if (Storage::disk('public')->exists($relativePath)) {
            return Storage::disk('public')->delete($relativePath);
        }

        return false;
    }
}
