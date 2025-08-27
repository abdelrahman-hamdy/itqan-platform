<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all lessons with video URLs that might have encoding issues
        $lessons = DB::table('lessons')
            ->whereNotNull('video_url')
            ->where('video_url', '!=', '')
            ->where('video_url', 'NOT LIKE', 'http%') // Exclude external URLs
            ->get();

        foreach ($lessons as $lesson) {
            // Check if the video_url contains non-ASCII characters (Arabic, emoji, etc.)
            if (!mb_check_encoding($lesson->video_url, 'ASCII')) {
                // Extract the file extension
                $extension = pathinfo($lesson->video_url, PATHINFO_EXTENSION);
                
                // Generate a clean filename
                $newFilename = sprintf(
                    'lesson_video_%s_%s_%s.%s',
                    $lesson->recorded_course_id,
                    $lesson->id,
                    time(),
                    strtolower($extension)
                );

                // Build the new path
                $newPath = 'lessons/videos/' . $newFilename;

                // Try to rename the physical file if it exists
                $oldPath = ltrim($lesson->video_url, '/');
                if (Storage::disk('public')->exists($oldPath)) {
                    try {
                        Storage::disk('public')->move($oldPath, $newPath);
                    } catch (\Exception $e) {
                        // If file move fails, just update the database with new name
                        // The file might not exist or might have permission issues
                        \Log::warning("Could not move file for lesson {$lesson->id}: " . $e->getMessage());
                    }
                }

                // Update the database with the new path
                DB::table('lessons')
                    ->where('id', $lesson->id)
                    ->update(['video_url' => $newPath]);

                echo "Updated lesson {$lesson->id}: {$lesson->video_url} -> {$newPath}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed as we don't store the original filenames
        // The original filenames with encoding issues would cause the same problems
        echo "This migration cannot be reversed due to encoding issues with original filenames.\n";
    }
};