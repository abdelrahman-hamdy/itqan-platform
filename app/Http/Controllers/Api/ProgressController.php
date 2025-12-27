<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\RecordedCourse;
use App\Models\StudentProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\SessionStatus;

class ProgressController extends Controller
{
    /**
     * Get course progress
     */
    public function getCourseProgress($courseId)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $course = RecordedCourse::findOrFail($courseId);
        $totalLessons = $course->lessons()->where('is_published', true)->count();

        $completedLessons = StudentProgress::where('user_id', $user->id)
            ->where('recorded_course_id', $courseId)
            ->where('is_completed', true)
            ->count();

        $progressPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        return response()->json([
            'success' => true,
            'progress_percentage' => $progressPercentage,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
        ]);
    }

    /**
     * Get lesson progress
     */
    public function getLessonProgress($courseId, $lessonId)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $lesson = Lesson::findOrFail($lessonId);
        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);

        return response()->json([
            'success' => true,
            'progress' => [
                'current_position_seconds' => $progress->current_position_seconds,
                'progress_percentage' => $progress->progress_percentage,
                'is_completed' => $progress->is_completed,
                'watch_time_seconds' => $progress->watch_time_seconds,
                'total_time_seconds' => $progress->total_time_seconds,
            ],
        ]);
    }

    /**
     * Update lesson progress
     */
    public function updateLessonProgress(Request $request, $courseId, $lessonId)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'current_time' => 'required|numeric|min:0',
            'total_time' => 'required|numeric|min:0',
            'progress_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $lesson = Lesson::findOrFail($lessonId);
        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);

        $progress->updateProgress(
            (int) $request->current_time,
            (int) $request->total_time
        );

        return response()->json([
            'success' => true,
            'progress' => [
                'current_position_seconds' => $progress->current_position_seconds,
                'progress_percentage' => $progress->progress_percentage,
                'is_completed' => $progress->is_completed,
            ],
        ]);
    }

    /**
     * Mark lesson as complete
     */
    public function markLessonComplete($courseId, $lessonId)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $lesson = Lesson::findOrFail($lessonId);
        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);
        $progress->markAsCompleted();

        return response()->json([
            'success' => true,
            'message' => 'Lesson marked as complete',
            'progress' => [
                'is_completed' => true,
                'completed_at' => $progress->completed_at,
            ],
        ]);
    }

    /**
     * Toggle lesson completion status
     */
    public function toggleLessonCompletion($courseId, $lessonId)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $lesson = Lesson::findOrFail($lessonId);
        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);

        if ($progress->is_completed) {
            // Mark as incomplete
            $progress->update([
                'is_completed' => false,
                'completed_at' => null,
            ]);
            $message = 'Lesson marked as incomplete';
        } else {
            // Mark as complete
            $progress->markAsCompleted();
            $message = 'Lesson marked as complete';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'progress' => [
                'is_completed' => $progress->is_completed,
                'progress_percentage' => $progress->progress_percentage,
                'completed_at' => $progress->completed_at,
            ],
        ]);
    }
}
