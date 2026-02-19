<?php

namespace App\Observers;

use App\Models\RecordedCourse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaObserver
{
    /**
     * Handle the Media "creating" event - this is called BEFORE saving to database
     */
    public function creating(Media $media): void
    {
        // Only process RecordedCourse media
        if ($media->model_type === RecordedCourse::class && ! empty($media->name)) {
            $media->name = $this->sanitizeMediaName($media->name);
        }
    }

    /**
     * Handle the Media "saving" event - this is called BEFORE saving to database
     */
    public function saving(Media $media): void
    {
        // Only process RecordedCourse media
        if ($media->model_type === RecordedCourse::class && ! empty($media->name)) {
            $media->name = $this->sanitizeMediaName($media->name);
        }
    }

    /**
     * Sanitize media filename to prevent encoding issues
     */
    private function sanitizeMediaName(string $filename): string
    {
        // Get the file extension and lowercase it immediately
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Validate the extension against an allowlist of safe file types
        $allowedExtensions = [
            // Video
            'mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v',
            // Audio
            'mp3', 'wav', 'ogg', 'm4a', 'aac',
            // Images
            'jpg', 'jpeg', 'png', 'gif', 'webp',
            // Documents
            'pdf', 'doc', 'docx',
            // Archives
            'zip',
        ];

        if (! empty($extension) && ! in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException("File type '{$extension}' is not allowed for course media.");
        }

        // Generate a safe filename with timestamp and unique ID
        $timestamp = date('YmdHis');
        $uniqueId = substr(uniqid(), -6);

        // Create the new filename
        $newFilename = sprintf(
            'course_media_%s_%s',
            $timestamp,
            $uniqueId
        );

        // Ensure the filename is not too long (max 200 characters)
        if (strlen($newFilename) > 200) {
            $newFilename = substr($newFilename, 0, 200);
        }

        // Add the lowercased extension
        if (! empty($extension)) {
            $newFilename .= '.'.$extension;
        }

        return $newFilename;
    }
}
