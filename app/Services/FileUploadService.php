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
     * Update/replace an existing uploaded file with a new file.
     * Deletes the old file and uploads the new file to the target directory.
     *
     * Supports flexible arguments:
     * - update(UploadedFile $file, ?string $oldRelativePath, string $directory = 'uploads', ?string $prefix = null)
     * - update(UploadedFile $file, string $directory, ?string $prefix = null, ?string $oldRelativePath = null)
     *
     * @param UploadedFile $file The new uploaded file instance
     * @param string|null $oldRelativePath Path to old file to delete if replacing, or directory name
     * @param string|null $directory Target subfolder in public/ (e.g. 'student_pictures', 'staff_pictures')
     * @param string|null $prefix Optional filename prefix
     * @return string The relative path of the newly saved file
     */
    public function update(
        UploadedFile $file,
        ?string $oldRelativePath = null,
        ?string $directory = 'uploads',
        ?string $prefix = null
    ): string {
        $dir = $directory ?? 'uploads';
        $oldPath = $oldRelativePath;

        // If $oldRelativePath was passed as a simple directory name (e.g. 'student_pictures' without an extension or slash)
        // and $directory is default 'uploads' or null:
        if ($oldRelativePath !== null && !empty($oldRelativePath) && !str_contains($oldRelativePath, '.') && !str_contains($oldRelativePath, '/') && ($directory === null || $directory === 'uploads')) {
            $dir = $oldRelativePath;
            $oldPath = null;
        }

        return $this->upload($file, $dir, $prefix, $oldPath);
    }

    /**
     * Upload a file to a specific storage disk (e.g. 'public') and optionally delete old file.
     *
     * @param UploadedFile $file The uploaded file instance
     * @param string $directory Target subfolder in storage disk (e.g., 'application_pictures')
     * @param string $disk The storage disk (default: 'public')
     * @param string|null $oldRelativePath Path to old file to delete if replacing
     * @return string The relative path of the saved file
     */
    public function uploadToDisk(
        UploadedFile $file,
        string $directory = 'uploads',
        string $disk = 'public',
        ?string $oldRelativePath = null
    ): string {
        if (!empty($oldRelativePath)) {
            $this->delete($oldRelativePath);
        }

        return $file->store($directory, $disk);
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
